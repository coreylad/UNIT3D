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
                            {--source-torrent-path= : Path to TSSE8 torrent files directory (for --copy-files)}
                            {--source-images-path= : Path to TSSE8 images directory (for --copy-images)}
                            {--copy-files : Copy .torrent files from source during torrents migration}
                            {--copy-images : Copy torrent cover and banner images from source}
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
        $allowedTables = [
            'users',
            'torrents',
            'peers',
            'snatched',
            'comments',
            'forums',
            'forum_threads',
            'forum_posts',
            'cleanup_torrent_descriptions',
            'verify_torrent_files',
            'relink_torrent_files',
            'recover_torrent_files_by_source_id',
        ];

        $sourceConfig = [
            'host'     => (string) $this->option('host'),
            'port'     => (int) $this->option('port'),
            'database' => (string) $this->option('database'),
            'username' => (string) $this->option('username'),
            'password' => (string) $this->option('password'),
        ];

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

        // These stages operate on the local UNIT3D DB only — no source connection needed
        $standaloneStages = ['cleanup_torrent_descriptions', 'verify_torrent_files', 'relink_torrent_files'];

        $requiresSourceConnection = array_values(array_diff($tables, $standaloneStages)) !== [];

        if ($requiresSourceConnection) {
            foreach (['host', 'database', 'username', 'password'] as $required) {
                if (($sourceConfig[$required] ?? '') === '') {
                    $this->error("Missing required option --{$required}");

                    return self::FAILURE;
                }
            }
        }

        $pageSize      = max(10, min(5000, (int) $this->option('page-size')));
        $initialOffset = max(0, (int) $this->option('offset'));
        $dryRun        = (bool) $this->option('dry-run');
        $force         = (bool) $this->option('force');
        $copyFiles     = (bool) $this->option('copy-files');
        $copyImages    = (bool) $this->option('copy-images');

        $sourceTorrentPath = (string) ($this->option('source-torrent-path') ?? '');
        $sourceImagesPath  = (string) ($this->option('source-images-path') ?? '');

        if ($copyFiles && $sourceTorrentPath === '') {
            $this->error('--copy-files requires --source-torrent-path to be specified.');

            return self::FAILURE;
        }

        if ($copyImages && $sourceImagesPath === '') {
            $this->error('--copy-images requires --source-images-path to be specified.');

            return self::FAILURE;
        }

        if (in_array('recover_torrent_files_by_source_id', $tables, true) && $sourceTorrentPath === '') {
            $this->error('recover_torrent_files_by_source_id requires --source-torrent-path to be specified.');

            return self::FAILURE;
        }

        if ($copyFiles && !in_array('torrents', $tables, true)) {
            $this->warn('--copy-files ignored: torrents table not in migration stages.');
            $copyFiles = false;
        }

        if ($copyImages && !in_array('torrents', $tables, true)) {
            $this->warn('--copy-images ignored: torrents table not in migration stages.');
            $copyImages = false;
        }

        $groupMap    = [];
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

        if ($requiresSourceConnection) {
            $this->line('Testing source DB connection ...');

            try {
                $this->migrationService->testConnection($sourceConfig);
            } catch (Throwable $e) {
                $this->error('Connection failed: ' . $e->getMessage());

                return self::FAILURE;
            }

            $driver = $this->migrationService->getActiveDriver() ?? 'unknown';
            $this->info("Connected to source using {$driver} driver.");
        }

        try {
            foreach ($tables as $table) {
                $this->newLine();
                $this->line("=== Migrating {$table} ===");

                $result = null;

                // ── users ────────────────────────────────────────────────────────────
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

                // ── torrents ─────────────────────────────────────────────────────────
                if ($table === 'torrents') {
                    if ($copyFiles) {
                        $this->info('File copying enabled. Validating source directory ...');

                        if (!is_dir($sourceTorrentPath)) {
                            $this->error("Source torrent path not found: {$sourceTorrentPath}");

                            return self::FAILURE;
                        }

                        $this->info("Source directory verified: {$sourceTorrentPath}");
                    }

                    if ($copyImages) {
                        $this->info('Image copying enabled. Validating source directory ...');

                        if (!is_dir($sourceImagesPath)) {
                            $this->error("Source images path not found: {$sourceImagesPath}");

                            return self::FAILURE;
                        }

                        $this->info("Source images directory verified: {$sourceImagesPath}");
                    }

                    $offset = $initialOffset;

                    do {
                        $result = $this->migrationService->migrateTorrents(
                            $sourceConfig,
                            $offset,
                            $pageSize,
                            $dryRun,
                            $copyFiles ? $sourceTorrentPath : null,
                            $copyImages ? $sourceImagesPath : null
                        );

                        if (($result['success'] ?? false) !== true) {
                            $this->error(($result['error'] ?? 'Torrent migration failed without message.'));

                            return self::FAILURE;
                        }

                        $imported      = $result['count'] ?? 0;
                        $skipped       = $result['skipped'] ?? 0;
                        $filesCopied   = $result['files_copied'] ?? 0;
                        $filesMissing  = $result['files_missing'] ?? 0;
                        $imagesCopied  = $result['images_copied'] ?? 0;
                        $imagesMissing = $result['images_missing'] ?? 0;

                        $summary = "torrents: migrated {$imported}, skipped {$skipped}";

                        if ($copyFiles) {
                            $summary .= " | files: copied {$filesCopied}, missing {$filesMissing}";
                        }

                        if ($copyImages) {
                            $summary .= " | images: copied {$imagesCopied}, missing {$imagesMissing}";
                        }

                        $summary .= " (offset {$offset})";

                        $this->info($summary);
                        gc_collect_cycles();
                        $offset += $pageSize;
                    } while (($result['done'] ?? true) !== true);

                    continue;
                }

                // ── cleanup_torrent_descriptions ─────────────────────────────────────
                if ($table === 'cleanup_torrent_descriptions') {
                    $offset = $initialOffset;

                    do {
                        $result = $this->migrationService->cleanupImportedTorrentDescriptions($offset, $pageSize, $dryRun);

                        if (($result['success'] ?? false) !== true) {
                            $this->error(($result['error'] ?? 'Torrent description cleanup failed without message.'));

                            return self::FAILURE;
                        }

                        $this->info("cleanup_torrent_descriptions: updated {$result['count']}, skipped {$result['skipped']} (offset {$offset})");
                        gc_collect_cycles();
                        $offset += $pageSize;
                    } while (($result['done'] ?? true) !== true);

                    continue;
                }

                // ── verify_torrent_files ─────────────────────────────────────────────
                if ($table === 'verify_torrent_files') {
                    $this->info('Verifying torrent file integrity and info_hash consistency...');

                    $offset        = $initialOffset;
                    $totalValid    = 0;
                    $totalMissing  = 0;
                    $totalMismatch = 0;

                    do {
                        $result = $this->migrationService->verifyTorrentFileIntegrity($offset, $pageSize);

                        if (($result['success'] ?? false) !== true) {
                            $this->error(($result['error'] ?? 'Torrent verification failed without message.'));

                            return self::FAILURE;
                        }

                        $valid    = $result['valid'] ?? 0;
                        $missing  = $result['missing'] ?? 0;
                        $mismatch = $result['hash_mismatch'] ?? 0;

                        $totalValid    += $valid;
                        $totalMissing  += $missing;
                        $totalMismatch += $mismatch;

                        if ($missing > 0 || $mismatch > 0) {
                            $this->warn("verify_torrent_files: {$valid} valid, {$missing} MISSING, {$mismatch} HASH MISMATCHES (offset {$offset})");
                        } else {
                            $this->info("verify_torrent_files: {$valid} valid (offset {$offset})");
                        }

                        gc_collect_cycles();
                        $offset += $pageSize;
                    } while (($result['done'] ?? true) !== true);

                    $this->newLine();
                    $this->info('=== Verification Summary ===');
                    $this->info("✓ Valid torrents: {$totalValid}");

                    if ($totalMissing > 0) {
                        $this->error("✗ Missing files: {$totalMissing} (users CANNOT download/seed these torrents)");
                    }

                    if ($totalMismatch > 0) {
                        $this->error("✗ Hash mismatches: {$totalMismatch} (info_hash does NOT match file content)");
                    }

                    if ($totalMissing === 0 && $totalMismatch === 0) {
                        $this->info('🎉 All torrent files are valid and consistent!');
                    } else {
                        $this->warn('⚠️  Some torrent files have issues. Migration is incomplete.');
                        $this->line('See MIGRATION_TORRENT_FILES.md for recovery instructions.');
                    }

                    continue;
                }

                // ── relink_torrent_files ─────────────────────────────────────────────
                if ($table === 'relink_torrent_files') {
                    $this->info('Relinking torrent files by info_hash (resolves filename mismatches)...');
                    $this->info('Scanning ' . storage_path('app/files/torrents/files') . ' — this may take several minutes for large libraries.');

                    $result = $this->migrationService->relinkTorrentFiles($pageSize);

                    if (($result['success'] ?? false) !== true) {
                        $this->error($result['error'] ?? 'Relink failed without message.');

                        return self::FAILURE;
                    }

                    $linked    = $result['linked'] ?? 0;
                    $alreadyOk = $result['already_ok'] ?? 0;
                    $noMatch   = $result['no_match'] ?? 0;
                    $processed = $result['processed'] ?? 0;

                    $this->newLine();
                    $this->info('=== Relink Summary ===');
                    $this->info("Files scanned:      {$processed}");
                    $this->info("✓ Newly linked:     {$linked}");
                    $this->info("✓ Already correct:  {$alreadyOk}");
                    $this->line("  No DB match:      {$noMatch} (TSSE8 files not in migrated set, harmless)");

                    if (!empty($result['errors'] ?? [])) {
                        foreach (array_slice($result['errors'], 0, 10) as $err) {
                            $this->warn("  ⚠ {$err}");
                        }
                    }

                    if ($linked > 0) {
                        $this->info('Re-run --tables=verify_torrent_files to confirm all files are now valid.');
                    }

                    continue;
                }

                // ── recover_torrent_files_by_source_id ───────────────────────────────
                if ($table === 'recover_torrent_files_by_source_id') {
                    $this->info('Recovering UNIT3D torrent files using TSSE source id.torrent naming...');

                    if (!is_dir($sourceTorrentPath)) {
                        $this->error("Source torrent path not found: {$sourceTorrentPath}");

                        return self::FAILURE;
                    }

                    $result = $this->migrationService->recoverTorrentFilesBySourceId($sourceConfig, $sourceTorrentPath, $pageSize);

                    if (($result['success'] ?? false) !== true) {
                        $this->error($result['error'] ?? 'Recovery failed without message.');

                        return self::FAILURE;
                    }

                    $processed          = $result['processed'] ?? 0;
                    $linked             = $result['linked'] ?? 0;
                    $alreadyOk          = $result['already_ok'] ?? 0;
                    $sourceMissing      = $result['source_missing'] ?? 0;
                    $sourceNoHashMatch  = $result['source_no_hash_match'] ?? 0;
                    $copyErrors         = $result['copy_errors'] ?? 0;

                    $this->newLine();
                    $this->info('=== Source-ID Recovery Summary ===');
                    $this->info("UNIT3D torrents scanned: {$processed}");
                    $this->info("✓ Newly recovered:     {$linked}");
                    $this->info("✓ Already present:     {$alreadyOk}");
                    $this->line("  Missing on source:   {$sourceMissing}");
                    $this->line("  No hash match in TSSE DB: {$sourceNoHashMatch}");
                    $this->line("  Copy errors:         {$copyErrors}");

                    if (!empty($result['errors'] ?? [])) {
                        foreach (array_slice($result['errors'], 0, 10) as $err) {
                            $this->warn("  ⚠ {$err}");
                        }
                    }

                    $this->info('Re-run --tables=verify_torrent_files to confirm final file integrity.');

                    continue;
                }

                // ── peers ─────────────────────────────────────────────────────────────
                if ($table === 'peers') {
                    $offset = $initialOffset;

                    do {
                        $result = $this->migrationService->migratePeers($sourceConfig, $offset, $pageSize);

                        if (($result['success'] ?? false) !== true) {
                            $this->error(($result['error'] ?? 'Peer migration failed without message.'));

                            return self::FAILURE;
                        }

                        $this->info("peers: migrated {$result['count']} (offset {$offset})");
                        gc_collect_cycles();
                        $offset += $pageSize;
                    } while (($result['done'] ?? true) !== true);

                    continue;
                }

                // ── snatched ─────────────────────────────────────────────────────────
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

                // ── comments ─────────────────────────────────────────────────────────
                if ($table === 'comments') {
                    $offset = $initialOffset;

                    do {
                        $result = $this->migrationService->migrateComments($sourceConfig, $offset, $pageSize);

                        if (($result['success'] ?? false) !== true) {
                            $this->error(($result['error'] ?? 'Comment migration failed without message.'));

                            return self::FAILURE;
                        }

                        $this->info("comments: migrated {$result['count']} (offset {$offset})");
                        gc_collect_cycles();
                        $offset += $pageSize;
                    } while (($result['done'] ?? true) !== true);

                    continue;
                }

                // ── forums ────────────────────────────────────────────────────────────
                if ($table === 'forums') {
                    $result = $this->migrationService->migrateForums($sourceConfig);
                }

                // ── forum_threads ─────────────────────────────────────────────────────
                if ($table === 'forum_threads') {
                    $offset = $initialOffset;

                    do {
                        $result = $this->migrationService->migrateForumThreads($sourceConfig, $offset, $pageSize);

                        if (($result['success'] ?? false) !== true) {
                            $this->error(($result['error'] ?? 'Forum thread migration failed without message.'));

                            return self::FAILURE;
                        }

                        $this->info("forum_threads: migrated {$result['count']} (offset {$offset})");
                        gc_collect_cycles();
                        $offset += $pageSize;
                    } while (($result['done'] ?? true) !== true);

                    continue;
                }

                // ── forum_posts ───────────────────────────────────────────────────────
                if ($table === 'forum_posts') {
                    $offset = $initialOffset;

                    do {
                        $result = $this->migrationService->migrateForumPosts($sourceConfig, $offset, $pageSize);

                        if (($result['success'] ?? false) !== true) {
                            $this->error(($result['error'] ?? 'Forum post migration failed without message.'));

                            return self::FAILURE;
                        }

                        $this->info("forum_posts: migrated {$result['count']} (offset {$offset})");
                        gc_collect_cycles();
                        $offset += $pageSize;
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
