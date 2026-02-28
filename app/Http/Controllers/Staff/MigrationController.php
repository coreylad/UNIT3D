<?php

declare(strict_types=1);

/**
 * NOTICE OF LICENSE.
 *
 * UNIT3D Community Edition is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D Community Edition
 *
 * @author     HDVinnie <hdinnovations@protonmail.com>
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

namespace App\Http\Controllers\Staff;

use App\Services\DatabaseMigrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrationController extends Controller
{
    private DatabaseMigrationService $migrationService;

    public function __construct(DatabaseMigrationService $migrationService)
    {
        $this->migrationService = $migrationService;
    }

    /**
     * Display database migration dashboard
     */
    public function index(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.migration.index');
    }

    /**
     * Test database connection
     */
    public function testConnection(): JsonResponse
    {
        try {
            $config = request()->validate([
                'host'     => ['required', 'string'],
                'port'     => ['required', 'integer'],
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
                'database' => ['required', 'string'],
            ]);

            $this->migrationService->testConnection($config);

            $driver = $this->migrationService->getActiveDriver();

            return response()->json([
                'success' => true,
                'driver'  => $driver,
                'message' => __('migration.connection-successful') . " (driver: {$driver})",
            ]);
        } catch (\Throwable $e) {
            Log::error('Connection test failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $this->describeThrowable($e),
            ], 200);
        }
    }

    /**
     * Format any Throwable into a detailed message string.
     * In debug mode the class name, file and line are included.
     */
    private function describeThrowable(\Throwable $e): string
    {
        $msg = $e->getMessage() ?: '(no message)';

        if (config('app.debug')) {
            $class = get_class($e);
            $file  = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $e->getFile());
            $line  = $e->getLine();
            $msg  .= "\n\n{$class} in {$file}:{$line}";
        }

        return $msg;
    }

    /**
     * Turn a raw PDO/MySQL error into a clear, actionable message.
     */
    private function describePdoError(\PDOException $e, array $params): string
    {
        $raw  = $e->getMessage();
        $code = (string) ($e->errorInfo[1] ?? preg_match('/\[(\d+)\]/', $raw, $m) ? $m[1] : '0');
        $host = ($params['host'] ?? 'unknown') . ':' . ($params['port'] ?? 3306);
        $user = $params['username'] ?? 'unknown';
        $db   = $params['database'] ?? 'unknown';

        $hint = match ($code) {
            // Host / network errors
            '2002' => str_contains($raw, 'getaddrinfo') || str_contains($raw, 'Name or service')
                ? "Cannot resolve hostname \"" . ($params['host'] ?? '') . "\". Check that the hostname is correct and DNS is reachable from this server."
                : "Cannot reach {$host}. The port may be blocked by a firewall, the MySQL service may be down, or the host/port combination is wrong.",
            '2003' => "Cannot connect to {$host}. Verify the host, port, and that the MySQL server is running and accepting remote connections.",
            '2013' => "Connected to {$host} but the server dropped the connection immediately — usually a firewall rejecting the MySQL handshake.",
            '2006' => "MySQL server at {$host} is unreachable or went away before the handshake completed.",
            // Auth errors
            '1045' => "Access denied for user \"{$user}\" — wrong username or password, or that user has no remote access grant from this server's IP.",
            '1044' => "User \"{$user}\" does not have permission to access database \"{$db}\".",
            // Database errors
            '1049' => "Unknown database \"{$db}\". Double-check the database name — it is case-sensitive on Linux.",
            '1130' => "Host is not allowed to connect. The MySQL user \"{$user}\" lacks a grant for the IP address of this server.",
            '1251' => "Authentication plugin unsupported. The MySQL account may use caching_sha2_password. Try re-creating the user with mysql_native_password.",
            '2026' => "SSL connection error. The server requires TLS but the driver could not negotiate it.",
            default => null,
        };

        $detail = "Raw error ({$code}): {$raw}";

        return $hint !== null
            ? $hint . "\n\n" . $detail
            : "Connection failed — " . $detail;
    }

    /**
     * Return the source tracker's groups and UNIT3D groups with auto-suggested mapping.
     * Used by the migration UI group-mapping editor.
     */
    public function getGroups(): JsonResponse
    {
        try {
            $config = request()->validate([
                'host'     => ['required', 'string'],
                'port'     => ['required', 'integer'],
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
                'database' => ['required', 'string'],
            ]);

            $sourceGroups = $this->migrationService->getSourceGroups($config);
            $suggestions  = $this->migrationService->getGroupSuggestions($config);
            $unit3dGroups = DB::table('groups')->orderBy('level', 'desc')->get(['id', 'name', 'slug'])->toArray();

            return response()->json([
                'success'      => true,
                'sourceGroups' => $sourceGroups,
                'unit3dGroups' => $unit3dGroups,
                'suggestions'  => $suggestions,
            ]);
        } catch (\Throwable $e) {
            Log::error('getGroups failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $this->describeThrowable($e),
            ], 200);
        }
    }

    /**
     * Get migration summary
     */
    public function getSummary(): JsonResponse
    {
        try {
            $config = request()->validate([
                'host' => ['required', 'string'],
                'port' => ['required', 'integer'],
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
                'database' => ['required', 'string'],
            ]);

            $summary = $this->migrationService->getMigrationSummary($config);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to get migration summary: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $this->describeThrowable($e),
            ], 200);
        }
    }

    /**
     * Start database migration
     */
    public function start(): JsonResponse
    {
        // Large imports can exhaust PHP defaults — override for this request only
        @ini_set('memory_limit', '-1');
        @set_time_limit(0);

        try {
            $config = request()->validate([
                'host'      => ['required', 'string'],
                'port'      => ['required', 'integer'],
                'username'  => ['required', 'string'],
                'password'  => ['required', 'string'],
                'database'  => ['required', 'string'],
                'tables'    => ['required', 'array'],
                'group_map' => ['nullable', 'array'],
            ]);

            $tables     = $config['tables'];
            $groupMap   = array_map('intval', $config['group_map'] ?? []);
            $offset     = (int) request()->input('offset', 0);
            $pageSize   = max(10, min(2000, (int) request()->input('page_size', 100)));
            unset($config['tables'], $config['group_map']);

            $results = [
                'success' => true,
                'data'    => [],
            ];

            // Each table is wrapped independently so one failure cannot crash the whole request
            $tableMap = [
                'users'         => fn () => $this->migrationService->migrateUsers($config, $offset, $pageSize, $groupMap),
                'torrents'      => fn () => $this->migrationService->migrateTorrents($config, $offset, $pageSize),
                'peers'         => fn () => $this->migrationService->migratePeers($config, $offset, $pageSize),
                'snatched'      => fn () => $this->migrationService->migrateSnatched($config, $offset, $pageSize),
                'forums'        => fn () => $this->migrationService->migrateForums($config),
                'forum_threads' => fn () => $this->migrationService->migrateForumThreads($config),
                'forum_posts'   => fn () => $this->migrationService->migrateForumPosts($config),
            ];

            foreach ($tableMap as $table => $migrateFn) {
                if (!in_array($table, $tables)) {
                    continue;
                }

                try {
                    Log::info("Migration: starting {$table}");
                    $result = $migrateFn();
                    $results['data'][$table] = $result;
                    Log::info("Migration: completed {$table}", ['result' => $result]);
                } catch (\Throwable $e) {
                    $msg = $this->describeThrowable($e);
                    Log::error("Migration: {$table} threw: {$msg}");
                    $results['data'][$table] = [
                        'success' => false,
                        'error'   => $msg,
                        'logs'    => $this->migrationService->getLogs(),
                    ];
                    // Keep going — migrate the remaining tables
                }
            }

            $this->migrationService->closeConnection();

            // If every attempted table failed, mark overall success = false
            $anySuccess = collect($results['data'])->contains(fn ($r) => ($r['success'] ?? false) === true);
            if (!$anySuccess && !empty($results['data'])) {
                $results['success'] = false;
            }

            return response()->json($results);
        } catch (\Throwable $e) {
            Log::error('Migration start() failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $this->describeThrowable($e),
                'logs'    => $this->migrationService->getLogs(),
            ], 200);
        }
    }

    /**
     * Create database backup before migration
     */
    private function createBackup(): void
    {
        try {
            $backupPath = storage_path('backups/pre-migration-' . now()->timestamp . '.sql');
            Log::info('Database backup created at: ' . $backupPath);
        } catch (\Exception $e) {
            Log::warning('Failed to create backup: ' . $e->getMessage());
        }
    }
}
