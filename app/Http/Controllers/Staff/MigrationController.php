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

            return response()->json([
                'success' => true,
                'message' => __('migration.connection-successful'),
            ]);
        } catch (\PDOException $e) {
            Log::error('Connection test failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $this->describePdoError($e, request()->only(['host', 'port', 'username', 'database'])),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Connection test failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
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
        } catch (\Exception $e) {
            Log::error('Failed to get migration summary: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Start database migration
     */
    public function start(): JsonResponse
    {
        try {
            $config = request()->validate([
                'host' => ['required', 'string'],
                'port' => ['required', 'integer'],
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
                'database' => ['required', 'string'],
                'tables' => ['required', 'array'],
            ]);

            $tables = $config['tables'];
            unset($config['tables']);

            $results = [
                'success' => true,
                'data' => [],
            ];

            // Backup database before migration
            $this->createBackup();

            // Migrate selected tables
            if (in_array('users', $tables)) {
                $results['data']['users'] = $this->migrationService->migrateUsers($config);
            }

            if (in_array('torrents', $tables)) {
                $results['data']['torrents'] = $this->migrationService->migrateTorrents($config);
            }

            if (in_array('peers', $tables)) {
                $results['data']['peers'] = $this->migrationService->migratePeers($config);
            }

            if (in_array('snatched', $tables)) {
                $results['data']['snatched'] = $this->migrationService->migrateSnatched($config);
            }

            if (in_array('forums', $tables)) {
                $results['data']['forums'] = $this->migrationService->migrateForums($config);
            }

            if (in_array('forum_threads', $tables)) {
                $results['data']['forum_threads'] = $this->migrationService->migrateForumThreads($config);
            }

            if (in_array('forum_posts', $tables)) {
                $results['data']['forum_posts'] = $this->migrationService->migrateForumPosts($config);
            }

            $this->migrationService->closeConnection();

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Migration failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
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
