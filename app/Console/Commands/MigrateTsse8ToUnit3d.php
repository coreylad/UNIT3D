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

namespace App\Console\Commands;

use App\Services\DatabaseMigrationService;
use Illuminate\Console\Command;
use Throwable;

class MigrateTsse8ToUnit3d extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:tsse8
                            {--host= : Source MySQL host}
                            {--port=3306 : Source MySQL port}
                            {--database= : Source TSSE8 database name}
                            {--username= : Source MySQL username}
                            {--password= : Source MySQL password}
                            {--tables=users,torrents,peers,snatched,comments,forums,forum_threads,forum_posts : Comma-separated migration stages}
                            {--page-size=500 : Batch/page size for paginated stages}
                            {--offset=0 : Starting offset for paginated stages}
                            {--group-map= : Optional JSON source_group_id->unit3d_group_id map (users only)}
                            {--force : Reconcile existing users by username and update imported stats}
                            {--dry-run : Dry-run users/torrents only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One-stop TSSE8 -> UNIT3D migration runner using DatabaseMigrationService';

    public function __construct(private readonly DatabaseMigrationService $migrationService)
    {
        parent::__construct();
    }

    final public function handle(): int
    {
        $allowedTables = ['users', 'torrents', 'peers', 'snatched', 'comments', 'forums', 'forum_threads', 'forum_posts'];

        $sourceConfig = [
            'host'     => (string) $this->option('host'),
            'port'     => (int) $this->option('port'),
            'database' => (string) $this->option('database'),
            'username' => (string) $this->option('username'),
            'password' => (string) $this->option('password'),
        ];

        foreach (['host', 'database', 'username', 'password'] as $required) {
            if (($sourceConfig[$required] ?? '') === '') {
                $this->error("Missing required option --{$required}");

                return self::FAILURE;
            }
        }

        $tables = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('tables')))));
        if ($tables === []) {
            $this->error('No migration stages selected.');

            return self::FAILURE;
        }

        $invalid = array_values(array_diff($tables, $allowedTables));
        if ($invalid !== []) {
            $this->error('Unsupported tables/stages: ' . implode(', ', $invalid));

            return self::FAILURE;
        }

        $pageSize = max(10, min(5000, (int) $this->option('page-size')));
        $initialOffset = max(0, (int) $this->option('offset'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $groupMap = [];
        $rawGroupMap = (string) ($this->option('group-map') ?? '');
        if ($rawGroupMap !== '') {
            $decoded = json_decode($rawGroupMap, true);
            if (!is_array($decoded)) {
                $this->error('--group-map must be valid JSON object, e.g. {"1":2,"2":3}');

                return self::FAILURE;
            }

            foreach ($decoded as $sourceGroupId => $unit3dGroupId) {
                $groupMap[(int) $sourceGroupId] = (int) $unit3dGroupId;
            }
        }

        $this->line('Testing source DB connection ...');

        try {
            $this->migrationService->testConnection($sourceConfig);
        } catch (Throwable $e) {
            $this->error('Connection failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $driver = $this->migrationService->getActiveDriver() ?? 'unknown';
        $this->info("Connected to source using {$driver} driver.");

        try {
            foreach ($tables as $table) {
                $this->newLine();
                $this->line("=== Migrating {$table} ===");

                $result = null;

                if ($table === 'users') {
                    $offset = $initialOffset;
                    do {
                        $result = $this->migrationService->migrateUsers($sourceConfig, $offset, $pageSize, $groupMap, $dryRun, $force);
                        if (($result['success'] ?? false) !== true) {
                            $this->error(($result['error'] ?? 'User migration failed without message.'));

                            return self::FAILURE;
                        }

                        $this->info("users: migrated {$result['count']} (offset {$offset})");
                        gc_collect_cycles();
                        $offset += $pageSize;
                    } while (($result['done'] ?? true) !== true);

                    continue;
                }

                if ($table === 'torrents') {
                    $offset = $initialOffset;
                    do {
                        $result = $this->migrationService->migrateTorrents($sourceConfig, $offset, $pageSize, $dryRun);
                        if (($result['success'] ?? false) !== true) {
                            $this->error(($result['error'] ?? 'Torrent migration failed without message.'));

                            return self::FAILURE;
                        }

                        $this->info("torrents: migrated {$result['count']}, skipped {$result['skipped']} (offset {$offset})");
                        gc_collect_cycles();
                        $offset += $pageSize;
                    } while (($result['done'] ?? true) !== true);

                    continue;
                }

                if ($table === 'peers') {
                    $offset = $initialOffset;
                    do {
                        $result = $this->migrationService->migratePeers($sourceConfig, $offset, $pageSize);
                        if (($result['success'] ?? false) !== true) {
                            $this->error(($result['error'] ?? 'Peer migration failed without message.'));

                            return self::FAILURE;
                        }

                        $this->info("peers: migrated {$result['count']} (offset {$offset})");                        gc_collect_cycles();                        $offset += $pageSize;
                    } while (($result['done'] ?? true) !== true);

                    continue;
                }

                if ($table === 'snatched') {
                    $offset = $initialOffset;
                    do {
                        $result = $this->migrationService->migrateSnatched($sourceConfig, $offset, $pageSize);
                        if (($result['success'] ?? false) !== true) {
                            $this->error(($result['error'] ?? 'Snatched migration failed without message.'));

                            return self::FAILURE;
                        }

                        $this->info("snatched: migrated {$result['count']} (offset {$offset})");
                        gc_collect_cycles();
                        $offset += $pageSize;
                    } while (($result['done'] ?? true) !== true);

                    continue;
                }

                if ($table === 'comments') {
                    $offset = $initialOffset;
                    do {
                        $result = $this->migrationService->migrateComments($sourceConfig, $offset, $pageSize);
                        if (($result['success'] ?? false) !== true) {
                            $this->error(($result['error'] ?? 'Comments migration failed without message.'));

                            return self::FAILURE;
                        }

                        $this->info("comments: migrated {$result['count']} (offset {$offset})");
                        gc_collect_cycles();
                        $offset += $pageSize;
                    } while (($result['done'] ?? true) !== true);

                    continue;
                }

                if ($table === 'forums') {
                    $result = $this->migrationService->migrateForums($sourceConfig);
                }

                if ($table === 'forum_threads') {
                    $offset = $initialOffset;
                    do {
                        $result = $this->migrationService->migrateForumThreads($sourceConfig, $offset, $pageSize);
                        if (($result['success'] ?? false) !== true) {
                            $this->error(($result['error'] ?? 'Forum thread migration failed without message.'));

                            return self::FAILURE;
                        }

                        $this->info("forum_threads: migrated {$result['count']} (offset {$offset})");                        gc_collect_cycles();                        $offset += $pageSize;
                    } while (($result['done'] ?? true) !== true);

                    continue;
                }

                if ($table === 'forum_posts') {
                    $offset = $initialOffset;
                    do {
                        $result = $this->migrationService->migrateForumPosts($sourceConfig, $offset, $pageSize);
                        if (($result['success'] ?? false) !== true) {
                            $this->error(($result['error'] ?? 'Forum post migration failed without message.'));

                            return self::FAILURE;
                        }

                        $this->info("forum_posts: migrated {$result['count']} (offset {$offset})");                        gc_collect_cycles();                        $offset += $pageSize;
                    } while (($result['done'] ?? true) !== true);

                    continue;
                }

                if (($result['success'] ?? false) !== true) {
                    $this->error(($result['error'] ?? "{$table} migration failed without message."));

                    return self::FAILURE;
                }

                $this->info("{$table}: migrated {$result['count']}");
            }

            if (array_intersect($tables, ['forums', 'forum_threads', 'forum_posts']) !== []) {
                $this->newLine();
                $this->line('Finalizing forum statistics ...');
                $finalize = $this->migrationService->finalizeForumStats();
                if (($finalize['success'] ?? false) !== true) {
                    $this->error($finalize['error'] ?? 'Failed to finalize forum stats.');

                    return self::FAILURE;
                }
            }
        } finally {
            $this->migrationService->closeConnection();
        }

        $this->newLine();
        $this->info('TSSE8 -> UNIT3D migration completed successfully.');

        if ($dryRun) {
            $this->warn('Dry-run mode only applies to users and torrents stages.');
        }

        return self::SUCCESS;
    }
}
