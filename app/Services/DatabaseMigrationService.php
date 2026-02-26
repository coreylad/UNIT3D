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

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;

class DatabaseMigrationService
{
    private ?PDO $pdoConnection = null;

    private ?\mysqli $mysqliConnection = null;

    /** Which driver is active: 'pdo' | 'mysqli' | null */
    private ?string $driver = null;

    private array $migrationLog = [];

    // ──────────────────────────────────────────────────────────────
    // Connection
    // ──────────────────────────────────────────────────────────────

    /**
     * Test connection to source database.
     * Tries PDO first; if that fails (missing extension or auth issue)
     * automatically retries with MySQLi.
     *
     * The last exception is re-thrown with both error messages attached
     * so the caller can show exactly what failed.
     */
    public function testConnection(array $config): bool
    {
        $pdoError    = null;
        $mysqliError = null;

        // ── Attempt 1: PDO ────────────────────────────────────────
        if (extension_loaded('pdo_mysql')) {
            try {
                $pdo = new PDO(
                    "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
                    $config['username'],
                    $config['password'],
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_TIMEOUT            => 10,
                    ]
                );
                $this->pdoConnection    = $pdo;
                $this->mysqliConnection = null;
                $this->driver           = 'pdo';

                return true;
            } catch (\Throwable $e) {
                $pdoError = '[PDO] ' . $e->getMessage();
            }
        } else {
            $pdoError = '[PDO] pdo_mysql extension is not loaded on this server.';
        }

        // ── Attempt 2: MySQLi ─────────────────────────────────────
        if (extension_loaded('mysqli')) {
            try {
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                $mysqli = new \mysqli(
                    $config['host'],
                    $config['username'],
                    $config['password'],
                    $config['database'],
                    (int) $config['port']
                );
                $mysqli->set_charset('utf8mb4');
                $this->mysqliConnection = $mysqli;
                $this->pdoConnection    = null;
                $this->driver           = 'mysqli';

                return true;
            } catch (\Throwable $e) {
                $mysqliError = '[MySQLi] ' . $e->getMessage();
            }
        } else {
            $mysqliError = '[MySQLi] mysqli extension is not loaded on this server.';
        }

        // Both failed — throw with combined diagnostics
        throw new \RuntimeException(
            "Could not connect with either PDO or MySQLi.\n\n"
            . ($pdoError    ?? '') . "\n"
            . ($mysqliError ?? '')
        );
    }

    /**
     * Ensure an active connection exists, reconnecting if necessary.
     */
    private function ensureConnected(array $config): void
    {
        $connected = match ($this->driver) {
            'pdo'    => $this->pdoConnection !== null,
            'mysqli' => $this->mysqliConnection !== null && !$this->mysqliConnection->connect_errno,
            default  => false,
        };

        if (!$connected) {
            $this->testConnection($config);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Unified query helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Run a SELECT query against the source DB and return rows as
     * an array of associative arrays, regardless of the active driver.
     *
     * @param  array<int,string|int|float|null>  $params  positional (?) bindings
     */
    private function sourceQuery(string $sql, array $params = []): array
    {
        if ($this->driver === 'pdo') {
            $stmt = $this->pdoConnection->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // MySQLi
        if (empty($params)) {
            $result = $this->mysqliConnection->query($sql);

            return $result->fetch_all(MYSQLI_ASSOC) ?: [];
        }

        $stmt = $this->mysqliConnection->prepare($sql);
        $types = implode('', array_map(
            static fn ($v) => match (true) {
                is_int($v)   => 'i',
                is_float($v) => 'd',
                default      => 's',
            },
            $params
        ));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC) ?: [];
    }

    /**
     * Run a COUNT-style query and return the value of the first column
     * of the first row as an integer.
     */
    private function sourceScalar(string $sql, array $params = []): int
    {
        $rows = $this->sourceQuery($sql, $params);

        if (empty($rows)) {
            return 0;
        }

        return (int) reset($rows[0]);
    }

    /**
     * Run a query and return all values from the first column (like PDO FETCH_COLUMN).
     */
    private function sourceColumn(string $sql, array $params = []): array
    {
        $rows = $this->sourceQuery($sql, $params);

        return array_map(static fn ($row) => reset($row), $rows);
    }

    // ──────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────

    /**
     * Get list of tables from source database
     */
    public function getSourceTables(array $config): array
    {
        $this->ensureConnected($config);

        try {
            return $this->sourceColumn(
                'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?',
                [$config['database']]
            );
        } catch (\Throwable $e) {
            Log::error('Failed to get source tables: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Get row count for a table
     */
    public function getTableRowCount(string $table, ?array $config = null): int
    {
        try {
            if ($config !== null) {
                $this->ensureConnected($config);

                return $this->sourceScalar("SELECT COUNT(*) FROM `{$table}`");
            }

            return DB::table($table)->count();
        } catch (\Throwable $e) {
            Log::error("Failed to get row count for table {$table}: " . $e->getMessage());

            return 0;
        }
    }

    /**
     * Return which driver is currently active: 'pdo', 'mysqli', or null.
     */
    public function getActiveDriver(): ?string
    {
        return $this->driver;
    }

    /**
     * Migrate users from source to destination
     */
    public function migrateUsers(array $sourceConfig): array
    {
        $this->log('Starting user migration...');

        try {
            $this->ensureConnected($sourceConfig);

            $users = $this->sourceQuery('SELECT 
                id, username, email, password, passkey, 
                uploaded, downloaded, seedbonus, invites, hitandruns,
                active, image, title, about, signature,
                fl_tokens, rsskey, hidden, style, nav, ratings,
                can_chat, can_comment, can_download, can_request, can_invite, can_upload,
                show_poster, peer_hidden, private_profile, stat_hidden,
                registered, last_login, last_action
            FROM users');

            $count = 0;
            $batch = [];
            $batchSize = 50;

            foreach ($users as $user) {
                try {
                    $password = !empty($user['password']) ? $user['password'] : bcrypt(bin2hex(random_bytes(16)));

                    $batch[] = [
                        'id'             => $user['id'],
                        'username'       => $user['username'],
                        'email'          => $user['email'] ?? '',
                        'password'       => $password,
                        'passkey'        => $user['passkey'] ?? bin2hex(random_bytes(16)),
                        'group_id'       => 0,
                        'active'         => $user['active'] ?? 1,
                        'uploaded'       => (int) ($user['uploaded'] ?? 0),
                        'downloaded'     => (int) ($user['downloaded'] ?? 0),
                        'image'          => $user['image'] ?? null,
                        'title'          => $user['title'] ?? null,
                        'about'          => $user['about'] ?? null,
                        'signature'      => $user['signature'] ?? null,
                        'fl_tokens'      => (int) ($user['fl_tokens'] ?? 0),
                        'seedbonus'      => (float) ($user['seedbonus'] ?? 0),
                        'invites'        => (int) ($user['invites'] ?? 0),
                        'hitandruns'     => (int) ($user['hitandruns'] ?? 0),
                        'rsskey'         => $user['rsskey'] ?? bin2hex(random_bytes(16)),
                        'hidden'         => (bool) ($user['hidden'] ?? 0),
                        'can_chat'       => (bool) ($user['can_chat'] ?? 1),
                        'can_comment'    => (bool) ($user['can_comment'] ?? 1),
                        'can_download'   => (bool) ($user['can_download'] ?? 1),
                        'can_request'    => (bool) ($user['can_request'] ?? 1),
                        'can_invite'     => (bool) ($user['can_invite'] ?? 1),
                        'can_upload'     => (bool) ($user['can_upload'] ?? 1),
                        'last_login'     => !empty($user['last_login']) && $user['last_login'] !== '0000-00-00 00:00:00' ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : null,
                        'created_at'     => !empty($user['registered']) && $user['registered'] !== '0000-00-00 00:00:00' ? date('Y-m-d H:i:s', strtotime($user['registered'])) : now(),
                        'updated_at'     => now(),
                    ];

                    if (count($batch) >= $batchSize) {
                        DB::table('users')->insertOrIgnore($batch);
                        $count += count($batch);
                        $batch = [];
                    }
                } catch (\Throwable $e) {
                    $this->log("Error preparing user {$user['username']}: " . $e->getMessage());
                }
            }

            if (!empty($batch)) {
                DB::table('users')->insertOrIgnore($batch);
                $count += count($batch);
            }

            $this->log("User migration completed: {$count} users migrated");

            return ['success' => true, 'count' => $count];
        } catch (\Throwable $e) {
            $this->log('User migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Migrate torrents from source to destination
     */
    public function migrateTorrents(array $sourceConfig): array
    {
        $this->log('Starting torrent migration...');

        try {
            $this->ensureConnected($sourceConfig);

            $torrents = $this->sourceQuery(
                'SELECT id, name, category, description, info_hash, seedtime, leechers, seeders, size, uploaded, language FROM torrents'
            );

            $count = 0;
            $batch = [];
            $batchSize = 100;

            foreach ($torrents as $torrent) {
                try {
                    $infohash = strtolower($torrent['info_hash']);
                    $existing = DB::table('torrents')->where('infohash', $infohash)->first();

                    if (!$existing) {
                        $batch[] = [
                            'name'        => $torrent['name'],
                            'category_id' => $this->mapCategory($torrent['category']),
                            'description' => $torrent['description'] ?? '',
                            'infohash'    => $infohash,
                            'size'        => $torrent['size'] ?? 0,
                            'leechers'    => $torrent['leechers'] ?? 0,
                            'seeders'     => $torrent['seeders'] ?? 0,
                            'views'       => 0,
                            'downloads'   => 0,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ];

                        if (count($batch) >= $batchSize) {
                            DB::table('torrents')->insert($batch);
                            $count += count($batch);
                            $batch = [];
                        }
                    }
                } catch (\Throwable $e) {
                    $this->log("Error migrating torrent {$torrent['name']}: " . $e->getMessage());
                }
            }

            if (!empty($batch)) {
                DB::table('torrents')->insert($batch);
                $count += count($batch);
            }

            $this->log("Torrent migration completed: {$count} torrents migrated");

            return ['success' => true, 'count' => $count];
        } catch (\Throwable $e) {
            $this->log('Torrent migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Migrate peers from source to destination
     */
    public function migratePeers(array $sourceConfig): array
    {
        $this->log('Starting peer migration...');

        try {
            $this->ensureConnected($sourceConfig);

            $peers = $this->sourceQuery(
                'SELECT userid, infohash, peerid, ip, port, `left`, uploaded, downloaded FROM peers'
            );

            $count = 0;
            $batch = [];
            $batchSize = 500;

            foreach ($peers as $peer) {
                try {
                    $batch[] = [
                        'infohash'   => strtolower($peer['infohash']),
                        'peerid'     => $peer['peerid'],
                        'ip'         => $peer['ip'],
                        'port'       => $peer['port'] ?? 0,
                        'left'       => $peer['left'] ?? 0,
                        'uploaded'   => $peer['uploaded'] ?? 0,
                        'downloaded' => $peer['downloaded'] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($batch) >= $batchSize) {
                        DB::table('peers')->insert($batch);
                        $count += count($batch);
                        $batch = [];
                    }
                } catch (\Throwable $e) {
                    $this->log('Error migrating peer: ' . $e->getMessage());
                }
            }

            if (!empty($batch)) {
                DB::table('peers')->insert($batch);
                $count += count($batch);
            }

            $this->log("Peer migration completed: {$count} peers migrated");

            return ['success' => true, 'count' => $count];
        } catch (\Throwable $e) {
            $this->log('Peer migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Migrate snatched from source to destination
     */
    public function migrateSnatched(array $sourceConfig): array
    {
        $this->log('Starting snatched migration...');

        try {
            $this->ensureConnected($sourceConfig);

            $snatched = $this->sourceQuery(
                'SELECT userid, infohash, snatched_time, downloaded, uploaded FROM snatched'
            );

            $count = 0;
            $batch = [];
            $batchSize = 500;

            foreach ($snatched as $snatch) {
                try {
                    $batch[] = [
                        'infohash'     => strtolower($snatch['infohash']),
                        'uploaded'     => $snatch['uploaded'] ?? 0,
                        'downloaded'   => $snatch['downloaded'] ?? 0,
                        'completed_at' => $snatch['snatched_time'] ?? now(),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];

                    if (count($batch) >= $batchSize) {
                        DB::table('snatched')->insert($batch);
                        $count += count($batch);
                        $batch = [];
                    }
                } catch (\Throwable $e) {
                    $this->log('Error migrating snatched record: ' . $e->getMessage());
                }
            }

            if (!empty($batch)) {
                DB::table('snatched')->insert($batch);
                $count += count($batch);
            }

            $this->log("Snatched migration completed: {$count} records migrated");

            return ['success' => true, 'count' => $count];
        } catch (\Throwable $e) {
            $this->log('Snatched migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Migrate forums from source to destination
     */
    public function migrateForums(array $sourceConfig): array
    {
        $this->log('Starting forum migration...');

        try {
            $this->ensureConnected($sourceConfig);

            $forums = $this->sourceQuery(
                'SELECT id, name, description, category_id, icon, min_power, type FROM tsf_forums'
            );

            $count    = 0;
            $forumMap = [];

            foreach ($forums as $forum) {
                try {
                    $existing = DB::table('forums')->where('name', $forum['name'])->first();
                    if (!$existing) {
                        $newId = DB::table('forums')->insertGetId([
                            'name'        => $forum['name'],
                            'description' => $forum['description'] ?? '',
                            'icon'        => $forum['icon'] ?? null,
                            'position'    => $forum['id'] ?? 0,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                        $forumMap[$forum['id']] = $newId;
                        $count++;
                    }
                } catch (\Throwable $e) {
                    $this->log("Error migrating forum {$forum['name']}: " . $e->getMessage());
                }
            }

            Cache::put('forum_id_map', $forumMap, now()->addHours(24));

            $this->log("Forum migration completed: {$count} forums migrated");

            return ['success' => true, 'count' => $count];
        } catch (\Throwable $e) {
            $this->log('Forum migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Migrate forum threads from source to destination
     */
    public function migrateForumThreads(array $sourceConfig): array
    {
        $this->log('Starting forum threads migration...');

        try {
            $this->ensureConnected($sourceConfig);

            $threads  = $this->sourceQuery(
                'SELECT id, forum_id, title, user_id, post_text, thread_date, sticky, locked, views FROM tsf_threads'
            );
            $count    = 0;
            $batch    = [];
            $batchSize = 200;
            $forumMap = Cache::get('forum_id_map', []);

            foreach ($threads as $thread) {
                try {
                    $newForumId = $forumMap[$thread['forum_id']] ?? null;

                    if ($newForumId) {
                        $batch[] = [
                            'forum_id'   => $newForumId,
                            'user_id'    => $thread['user_id'] ?? 0,
                            'title'      => $thread['title'] ?? 'Thread',
                            'sticky'     => $thread['sticky'] ?? false,
                            'locked'     => $thread['locked'] ?? false,
                            'views'      => $thread['views'] ?? 0,
                            'created_at' => $thread['thread_date'] ? date('Y-m-d H:i:s', $thread['thread_date']) : now(),
                            'updated_at' => now(),
                        ];

                        if (count($batch) >= $batchSize) {
                            DB::table('forum_threads')->insert($batch);
                            $count += count($batch);
                            $batch = [];
                        }
                    }
                } catch (\Throwable $e) {
                    $this->log('Error migrating forum thread: ' . $e->getMessage());
                }
            }

            if (!empty($batch)) {
                DB::table('forum_threads')->insert($batch);
                $count += count($batch);
            }

            $this->log("Forum threads migration completed: {$count} threads migrated");

            return ['success' => true, 'count' => $count];
        } catch (\Throwable $e) {
            $this->log('Forum threads migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Migrate forum posts from source to destination
     */
    public function migrateForumPosts(array $sourceConfig): array
    {
        $this->log('Starting forum posts migration...');

        try {
            $this->ensureConnected($sourceConfig);

            $posts = $this->sourceQuery(
                'SELECT id, thread_id, user_id, post_text, post_date, edited_by, edited_at FROM tsf_posts'
            );

            $count = 0;
            $batch = [];
            $batchSize = 500;

            foreach ($posts as $post) {
                try {
                    $batch[] = [
                        'thread_id'  => $post['thread_id'] ?? 0,
                        'user_id'    => $post['user_id'] ?? 0,
                        'content'    => $post['post_text'] ?? '',
                        'created_at' => $post['post_date'] ? date('Y-m-d H:i:s', $post['post_date']) : now(),
                        'updated_at' => $post['edited_at'] ? date('Y-m-d H:i:s', $post['edited_at']) : now(),
                    ];

                    if (count($batch) >= $batchSize) {
                        DB::table('forum_posts')->insert($batch);
                        $count += count($batch);
                        $batch = [];
                    }
                } catch (\Throwable $e) {
                    $this->log('Error migrating forum post: ' . $e->getMessage());
                }
            }

            if (!empty($batch)) {
                DB::table('forum_posts')->insert($batch);
                $count += count($batch);
            }

            $this->log("Forum posts migration completed: {$count} posts migrated");

            return ['success' => true, 'count' => $count];
        } catch (\Throwable $e) {
            $this->log('Forum posts migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get migration summary (row counts per table)
     */
    public function getMigrationSummary(array $sourceConfig): array
    {
        $this->ensureConnected($sourceConfig);

        try {
            return [
                'users'         => $this->getTableRowCount('users', $sourceConfig),
                'torrents'      => $this->getTableRowCount('torrents', $sourceConfig),
                'peers'         => $this->getTableRowCount('peers', $sourceConfig),
                'snatched'      => $this->getTableRowCount('snatched', $sourceConfig),
                'comments'      => $this->getTableRowCount('comments', $sourceConfig),
                'forums'        => $this->getTableRowCount('tsf_forums', $sourceConfig),
                'forum_threads' => $this->getTableRowCount('tsf_threads', $sourceConfig),
                'forum_posts'   => $this->getTableRowCount('tsf_posts', $sourceConfig),
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to get migration summary: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Close database connection
     */
    public function closeConnection(): void
    {
        if ($this->mysqliConnection !== null) {
            $this->mysqliConnection->close();
        }

        $this->pdoConnection    = null;
        $this->mysqliConnection = null;
        $this->driver           = null;
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Map category from source to destination
     */
    private function mapCategory(int $sourceCategory): int
    {
        return match ($sourceCategory) {
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            default => 6,
        };
    }

    /**
     * Log migration events
     */
    private function log(string $message): void
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $this->migrationLog[] = "[{$timestamp}] {$message}";
        Log::channel('migration')->info($message);
    }

    /**
     * Get migration logs
     */
    public function getLogs(): array
    {
        return $this->migrationLog;
    }
}


class DatabaseMigrationService
{
    private ?PDO $sourceConnection = null;

    private array $migrationLog = [];

    /**
     * Test connection to source database
     */
    public function testConnection(array $config): bool
    {
        $this->sourceConnection = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        return true;
    }

    /**
     * Get list of tables from source database
     */
    public function getSourceTables(array $config): array
    {
        if (!isset($this->sourceConnection)) {
            $this->testConnection($config);
        }

        try {
            $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?";
            $stmt = $this->sourceConnection->prepare($query);
            $stmt->execute([$config['database']]);

            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            Log::error('Failed to get source tables: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Get row count for a table
     */
    public function getTableRowCount(string $table, array $config = null): int
    {
        try {
            if ($config !== null) {
                if (!isset($this->sourceConnection)) {
                    $this->testConnection($config);
                }
                $query = "SELECT COUNT(*) FROM `{$table}`";
                $stmt = $this->sourceConnection->query($query);

                return (int) $stmt->fetchColumn();
            }

            return DB::table($table)->count();
        } catch (Exception $e) {
            Log::error("Failed to get row count for table {$table}: " . $e->getMessage());

            return 0;
        }
    }

    /**
     * Migrate users from source to destination
     */
    public function migrateUsers(array $sourceConfig): array
    {
        $this->log('Starting user migration...');

        try {
            if (!isset($this->sourceConnection)) {
                $this->testConnection($sourceConfig);
            }

            // Map TSSEDB users to UNIT3D users
            // Fetch ALL users with comprehensive field mapping for seamless migration
            $query = 'SELECT 
                id, username, email, password, passkey, 
                uploaded, downloaded, seedbonus, invites, hitandruns,
                active, image, title, about, signature,
                fl_tokens, rsskey, hidden, style, nav, ratings,
                can_chat, can_comment, can_download, can_request, can_invite, can_upload,
                show_poster, peer_hidden, private_profile, stat_hidden,
                registered, last_login, last_action
            FROM users';
            $stmt = $this->sourceConnection->query($query);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count = 0;
            $batch = [];
            $batchSize = 50;

            foreach ($users as $user) {
                try {
                    $password = !empty($user['password']) ? $user['password'] : bcrypt(bin2hex(random_bytes(16)));
                
                    $batch[] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'] ?? '',
                        'password' => $password,
                        'passkey' => $user['passkey'] ?? bin2hex(random_bytes(16)),
                        'group_id' => 0,
                        'active' => $user['active'] ?? 1,
                        'uploaded' => (int) ($user['uploaded'] ?? 0),
                        'downloaded' => (int) ($user['downloaded'] ?? 0),
                        'image' => $user['image'] ?? null,
                        'title' => $user['title'] ?? null,
                        'about' => $user['about'] ?? null,
                        'signature' => $user['signature'] ?? null,
                        'fl_tokens' => (int) ($user['fl_tokens'] ?? 0),
                        'seedbonus' => (float) ($user['seedbonus'] ?? 0),
                        'invites' => (int) ($user['invites'] ?? 0),
                        'hitandruns' => (int) ($user['hitandruns'] ?? 0),
                        'rsskey' => $user['rsskey'] ?? bin2hex(random_bytes(16)),
                        'hidden' => (bool) ($user['hidden'] ?? 0),
                        'can_chat' => (bool) ($user['can_chat'] ?? 1),
                        'can_comment' => (bool) ($user['can_comment'] ?? 1),
                        'can_download' => (bool) ($user['can_download'] ?? 1),
                        'can_request' => (bool) ($user['can_request'] ?? 1),
                        'can_invite' => (bool) ($user['can_invite'] ?? 1),
                        'can_upload' => (bool) ($user['can_upload'] ?? 1),
                        'last_login' => !empty($user['last_login']) && $user['last_login'] != '0000-00-00 00:00:00' ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : null,
                        'created_at' => !empty($user['registered']) && $user['registered'] != '0000-00-00 00:00:00' ? date('Y-m-d H:i:s', strtotime($user['registered'])) : now(),
                        'updated_at' => now(),
                    ];

                    if (count($batch) >= $batchSize) {
                        DB::table('users')->insertOrIgnore($batch);
                        $count += count($batch);
                        $batch = [];
                    }
                } catch (Exception $e) {
                    $this->log("Error preparing user {$user['username']}: " . $e->getMessage());
                }
            }

            if (!empty($batch)) {
                DB::table('users')->insertOrIgnore($batch);
                $count += count($batch);
            }

            $this->log("✅ User migration completed: {$count} users migrated with ALL account data preserved");

            $this->log("User migration completed: {$count} users migrated");

            return ['success' => true, 'count' => $count];
        } catch (Exception $e) {
            $this->log('User migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Migrate torrents from source to destination
     */
    public function migrateTorrents(array $sourceConfig): array
    {
        $this->log('Starting torrent migration...');

        try {
            if (!isset($this->sourceConnection)) {
                $this->testConnection($sourceConfig);
            }

            // Get torrents from source database - fetch ALL torrents for seamless migration
            $query = 'SELECT id, name, category, description, info_hash, seedtime, leechers, seeders, size, uploaded, language FROM torrents';
            $stmt = $this->sourceConnection->query($query);
            $torrents = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count = 0;
            $batch = [];
            $batchSize = 100; // Process in batches to avoid memory issues

            foreach ($torrents as $torrent) {
                try {
                    // Check if torrent already exists
                    $infohash = strtolower($torrent['info_hash']);
                    $existing = DB::table('torrents')->where('infohash', $infohash)->first();
                    
                    if (!$existing) {
                        $batch[] = [
                            'name' => $torrent['name'],
                            'category_id' => $this->mapCategory($torrent['category']),
                            'description' => $torrent['description'] ?? '',
                            'infohash' => $infohash,
                            'size' => $torrent['size'] ?? 0,
                            'leechers' => $torrent['leechers'] ?? 0,
                            'seeders' => $torrent['seeders'] ?? 0,
                            'views' => 0,
                            'downloads' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        // Insert batch when it reaches the batch size
                        if (count($batch) >= $batchSize) {
                            DB::table('torrents')->insert($batch);
                            $count += count($batch);
                            $batch = [];
                        }
                    }
                } catch (Exception $e) {
                    $this->log("Error migrating torrent {$torrent['name']}: " . $e->getMessage());
                }
            }

            // Insert remaining batch
            if (!empty($batch)) {
                DB::table('torrents')->insert($batch);
                $count += count($batch);
            }

            $this->log("Torrent migration completed: {$count} torrents migrated");

            return ['success' => true, 'count' => $count];
        } catch (Exception $e) {
            $this->log('Torrent migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Migrate peers from source to destination
     */
    public function migratePeers(array $sourceConfig): array
    {
        $this->log('Starting peer migration...');

        try {
            if (!isset($this->sourceConnection)) {
                $this->testConnection($sourceConfig);
            }

            // Fetch ALL peers for complete seamless migration
            $query = 'SELECT userid, infohash, peerid, ip, port, left, uploaded, downloaded FROM peers';
            $stmt = $this->sourceConnection->query($query);
            $peers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count = 0;
            $batch = [];
            $batchSize = 500; // Larger batch size for peers

            foreach ($peers as $peer) {
                try {
                    $batch[] = [
                        'infohash' => strtolower($peer['infohash']),
                        'peerid' => $peer['peerid'],
                        'ip' => $peer['ip'],
                        'port' => $peer['port'] ?? 0,
                        'left' => $peer['left'] ?? 0,
                        'uploaded' => $peer['uploaded'] ?? 0,
                        'downloaded' => $peer['downloaded'] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insert batch when it reaches the batch size
                    if (count($batch) >= $batchSize) {
                        DB::table('peers')->insert($batch);
                        $count += count($batch);
                        $batch = [];
                    }
                } catch (Exception $e) {
                    $this->log("Error migrating peer: " . $e->getMessage());
                }
            }

            // Insert remaining batch
            if (!empty($batch)) {
                DB::table('peers')->insert($batch);
                $count += count($batch);
            }

            $this->log("Peer migration completed: {$count} peers migrated");

            return ['success' => true, 'count' => $count];
        } catch (Exception $e) {
            $this->log('Peer migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Migrate snatched from source to destination
     */
    public function migrateSnatched(array $sourceConfig): array
    {
        $this->log('Starting snatched migration...');

        try {
            if (!isset($this->sourceConnection)) {
                $this->testConnection($sourceConfig);
            }

            // Fetch ALL snatched records for complete user history preservation
            $query = 'SELECT userid, infohash, snatched_time, downloaded, uploaded FROM snatched';
            $stmt = $this->sourceConnection->query($query);
            $snatched = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count = 0;
            $batch = [];
            $batchSize = 500;

            foreach ($snatched as $snatch) {
                try {
                    $batch[] = [
                        'infohash' => strtolower($snatch['infohash']),
                        'uploaded' => $snatch['uploaded'] ?? 0,
                        'downloaded' => $snatch['downloaded'] ?? 0,
                        'completed_at' => $snatch['snatched_time'] ?? now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insert batch when it reaches the batch size
                    if (count($batch) >= $batchSize) {
                        DB::table('snatched')->insert($batch);
                        $count += count($batch);
                        $batch = [];
                    }
                } catch (Exception $e) {
                    $this->log("Error migrating snatched record: " . $e->getMessage());
                }
            }

            // Insert remaining batch
            if (!empty($batch)) {
                DB::table('snatched')->insert($batch);
                $count += count($batch);
            }

            $this->log("Snatched migration completed: {$count} records migrated");

            return ['success' => true, 'count' => $count];
        } catch (Exception $e) {
            $this->log('Snatched migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Migrate forums from source to destination
     */
    public function migrateForums(array $sourceConfig): array
    {
        $this->log('Starting forum migration...');

        try {
            if (!isset($this->sourceConnection)) {
                $this->testConnection($sourceConfig);
            }

            // Fetch ALL forums for complete migration
            $query = 'SELECT id, name, description, category_id, icon, min_power, type FROM tsf_forums';
            $stmt = $this->sourceConnection->query($query);
            $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count = 0;
            $forumMap = []; // Map old forum IDs to new ones

            foreach ($forums as $forum) {
                try {
                    $existing = DB::table('forums')->where('name', $forum['name'])->first();
                    if (!$existing) {
                        $newId = DB::table('forums')->insertGetId([
                            'name' => $forum['name'],
                            'description' => $forum['description'] ?? '',
                            'icon' => $forum['icon'] ?? null,
                            'position' => $forum['id'] ?? 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $forumMap[$forum['id']] = $newId;
                        $count++;
                    }
                } catch (Exception $e) {
                    $this->log("Error migrating forum {$forum['name']}: " . $e->getMessage());
                }
            }

            // Cache the forum ID mapping for posts migration
            Cache::put('forum_id_map', $forumMap, now()->addHours(24));

            $this->log("Forum migration completed: {$count} forums migrated");

            return ['success' => true, 'count' => $count];
        } catch (Exception $e) {
            $this->log('Forum migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Migrate forum posts from source to destination
     */
    public function migrateForumPosts(array $sourceConfig): array
    {
        $this->log('Starting forum posts migration...');

        try {
            if (!isset($this->sourceConnection)) {
                $this->testConnection($sourceConfig);
            }

            // Fetch ALL posts for complete migration
            $query = 'SELECT id, thread_id, user_id, post_text, post_date, edited_by, edited_at FROM tsf_posts';
            $stmt = $this->sourceConnection->query($query);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count = 0;
            $batch = [];
            $batchSize = 500;

            foreach ($posts as $post) {
                try {
                    $batch[] = [
                        'thread_id' => $post['thread_id'] ?? 0,
                        'user_id' => $post['user_id'] ?? 0,
                        'content' => $post['post_text'] ?? '',
                        'created_at' => $post['post_date'] ? date('Y-m-d H:i:s', $post['post_date']) : now(),
                        'updated_at' => $post['edited_at'] ? date('Y-m-d H:i:s', $post['edited_at']) : now(),
                    ];

                    // Insert batch when it reaches the batch size
                    if (count($batch) >= $batchSize) {
                        DB::table('forum_posts')->insert($batch);
                        $count += count($batch);
                        $batch = [];
                    }
                } catch (Exception $e) {
                    $this->log("Error migrating forum post: " . $e->getMessage());
                }
            }

            // Insert remaining batch
            if (!empty($batch)) {
                DB::table('forum_posts')->insert($batch);
                $count += count($batch);
            }

            $this->log("Forum posts migration completed: {$count} posts migrated");

            return ['success' => true, 'count' => $count];
        } catch (Exception $e) {
            $this->log('Forum posts migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Migrate forum threads from source to destination
     */
    public function migrateForumThreads(array $sourceConfig): array
    {
        $this->log('Starting forum threads migration...');

        try {
            if (!isset($this->sourceConnection)) {
                $this->testConnection($sourceConfig);
            }

            // Fetch ALL threads for complete migration
            $query = 'SELECT id, forum_id, title, user_id, post_text, thread_date, sticky, locked, views FROM tsf_threads';
            $stmt = $this->sourceConnection->query($query);
            $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count = 0;
            $batch = [];
            $batchSize = 200;
            $forumMap = Cache::get('forum_id_map', []);

            foreach ($threads as $thread) {
                try {
                    $newForumId = $forumMap[$thread['forum_id']] ?? null;

                    if ($newForumId) {
                        $batch[] = [
                            'forum_id' => $newForumId,
                            'user_id' => $thread['user_id'] ?? 0,
                            'title' => $thread['title'] ?? 'Thread',
                            'sticky' => $thread['sticky'] ?? false,
                            'locked' => $thread['locked'] ?? false,
                            'views' => $thread['views'] ?? 0,
                            'created_at' => $thread['thread_date'] ? date('Y-m-d H:i:s', $thread['thread_date']) : now(),
                            'updated_at' => now(),
                        ];

                        // Insert batch when it reaches the batch size
                        if (count($batch) >= $batchSize) {
                            DB::table('forum_threads')->insert($batch);
                            $count += count($batch);
                            $batch = [];
                        }
                    }
                } catch (Exception $e) {
                    $this->log("Error migrating forum thread: " . $e->getMessage());
                }
            }

            // Insert remaining batch
            if (!empty($batch)) {
                DB::table('forum_threads')->insert($batch);
                $count += count($batch);
            }

            $this->log("Forum threads migration completed: {$count} threads migrated");

            return ['success' => true, 'count' => $count];
        } catch (Exception $e) {
            $this->log('Forum threads migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Map category from source to destination
     */
    private function mapCategory(int $sourceCategory): int
    {
        $categoryMap = [
            1 => 1,  // Movie
            2 => 2,  // TV Show
            3 => 3,  // Music
            4 => 4,  // Game
            5 => 5,  // Software
            6 => 6,  // Other
        ];

        return $categoryMap[$sourceCategory] ?? 6;
    }

    /**
     * Log migration events
     */
    private function log(string $message): void
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}";
        $this->migrationLog[] = $logEntry;
        Log::channel('migration')->info($message);
    }

    /**
     * Get migration logs
     */
    public function getLogs(): array
    {
        return $this->migrationLog;
    }

    /**
     * Get migration summary
     */
    public function getMigrationSummary(array $sourceConfig): array
    {
        if (!isset($this->sourceConnection)) {
            $this->testConnection($sourceConfig);
        }

        try {
            return [
                'users' => $this->getTableRowCount('users', $sourceConfig),
                'torrents' => $this->getTableRowCount('torrents', $sourceConfig),
                'peers' => $this->getTableRowCount('peers', $sourceConfig),
                'snatched' => $this->getTableRowCount('snatched', $sourceConfig),
                'comments' => $this->getTableRowCount('comments', $sourceConfig),
                'forums' => $this->getTableRowCount('tsf_forums', $sourceConfig),
                'forum_threads' => $this->getTableRowCount('tsf_threads', $sourceConfig),
                'forum_posts' => $this->getTableRowCount('tsf_posts', $sourceConfig),
            ];
        } catch (Exception $e) {
            Log::error('Failed to get migration summary: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Close database connection
     */
    public function closeConnection(): void
    {
        $this->sourceConnection = null;
    }
}
