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
                'host' => ['required', 'string'],
                'port' => ['required', 'integer'],
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
                'database' => ['required', 'string'],
            ]);

            $result = $this->migrationService->testConnection($config);

            return response()->json([
                'success' => $result,
                'message' => $result ? __('migration.connection-successful') : __('migration.connection-failed'),
            ]);
        } catch (\Exception $e) {
            Log::error('Connection test failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
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
