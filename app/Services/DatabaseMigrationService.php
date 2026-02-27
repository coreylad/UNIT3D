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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Connection
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
        // Trim every value - invisible whitespace is a common paste mistake
        $config = array_map(static fn ($v) => is_string($v) ? trim($v) : $v, $config);

        // On Linux, MySQL treats "localhost" as a Unix socket connection,
        // but most users are granted access via "127.0.0.1" (TCP).
        // If the user typed "localhost" we automatically also try "127.0.0.1".
        $hosts = [$config['host']];
        if (strtolower($config['host']) === 'localhost') {
            $hosts[] = '127.0.0.1';
        }

        $pdoError    = null;
        $mysqliError = null;

        foreach ($hosts as $host) {
            $cfg = array_merge($config, ['host' => $host]);

            // Attempt: PDO
            if (extension_loaded('pdo_mysql')) {
                try {
                    $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset=utf8mb4";
                    $pdo = new PDO(
                        $dsn,
                        $cfg['username'],
                        $cfg['password'],
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
                    $pdoError = "[PDO@{$host}] " . $e->getMessage();
                }
            } else {
                $pdoError = '[PDO] pdo_mysql extension is not loaded on this server.';
            }

            // Attempt: MySQLi
            if (extension_loaded('mysqli')) {
                try {
                    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                    $mysqli = new \mysqli(
                        $cfg['host'],
                        $cfg['username'],
                        $cfg['password'],
                        $cfg['database'],
                        (int) $cfg['port']
                    );
                    $mysqli->set_charset('utf8mb4');
                    $this->mysqliConnection = $mysqli;
                    $this->pdoConnection    = null;
                    $this->driver           = 'mysqli';

                    return true;
                } catch (\Throwable $e) {
                    $mysqliError = "[MySQLi@{$host}] " . $e->getMessage();
                }
            } else {
                $mysqliError = '[MySQLi] mysqli extension is not loaded on this server.';
            }
        }

        // All hosts and drivers failed - build an actionable error message
        $localhostHint = strtolower($config['host']) === 'localhost'
            ? "\n\nTip: On Linux \"localhost\" uses a Unix socket while MySQL grants are often"
              . " defined for \"127.0.0.1\" (TCP). Both hosts were tried and both failed."
              . " Verify: SHOW GRANTS FOR '{$config['username']}'@'127.0.0.1';"
            : '';

        throw new \RuntimeException(
            "Could not connect with either PDO or MySQLi."
            . $localhostHint . "\n\n"
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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Unified query helpers
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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


    /**
     * Iterate over a large source table in pages (LIMIT/OFFSET) so we never
     * load the entire table into PHP memory at once.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function chunkSourceQuery(string $sql, array $params = [], int $chunkSize = 300): \Generator
    {
        $offset = 0;

        while (true) {
            $chunkedSql = "{$sql} LIMIT {$chunkSize} OFFSET {$offset}";
            $rows       = $this->sourceQuery($chunkedSql, $params);

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                yield $row;
            }

            if (count($rows) < $chunkSize) {
                break;  // last page fetched
            }

            $offset += $chunkSize;
        }
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Public API
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
    public function getTableRowCount(?string $table, ?array $config = null): int
    {
        if ($table === null) {
            return 0;
        }

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
    /**
     * Build a map of source_group_id => unit3d_group_id.
     *
     * Strategy:
     *  1. Load all UNIT3D groups indexed by slug and by lowercased name.
     *  2. Try to find a groups/user_groups table in the source DB.
     *  3. If found, match each source group name to a UNIT3D group via:
     *       - exact slug match
     *       - exact name match (case-insensitive)
     *       - keyword match (admin, mod, banned, owner, uploader, validated, disabled, pruned)
     *  4. Unmapped source groups fall back to the UNIT3D "User" group.
     */
    private function buildGroupMap(array $config): array
    {
        // ── 1. Load UNIT3D destination groups ────────────────────────────
        $unit3dGroups = DB::table('groups')
            ->get(['id', 'name', 'slug'])
            ->keyBy('slug');          // keyed by slug for easy lookup

        // Resolve the fallback "regular user" group id
        $fallbackId = $unit3dGroups->get('user')?->id
            ?? $unit3dGroups->first()?->id
            ?? 1;

        // Build a keyword → unit3d_group_id lookup table
        $keywordMap = [];
        foreach ([
            ['keywords' => ['admin', 'administrator', 'staff'],   'slug' => 'administrator'],
            ['keywords' => ['owner', 'founder'],                   'slug' => 'owner'],
            ['keywords' => ['mod', 'moderator', 'modo'],           'slug' => 'moderator'],
            ['keywords' => ['torrent mod', 'torrent moderator'],   'slug' => 'torrent-moderator'],
            ['keywords' => ['uploader', 'encoder', 'internal'],    'slug' => 'uploader'],
            ['keywords' => ['trustee', 'trusted'],                 'slug' => 'trustee'],
            ['keywords' => ['banned', 'ban'],                      'slug' => 'banned'],
            ['keywords' => ['disabled'],                           'slug' => 'disabled'],
            ['keywords' => ['pruned', 'deleted'],                  'slug' => 'pruned'],
            ['keywords' => ['validating', 'pending', 'unvalidated', 'inactive'], 'slug' => 'validating'],
            ['keywords' => ['poweruser', 'power user', 'power'],   'slug' => 'poweruser'],
            ['keywords' => ['superuser', 'super user'],            'slug' => 'superuser'],
            ['keywords' => ['extremeuser', 'extreme'],             'slug' => 'extremeuser'],
            ['keywords' => ['insane', 'insaneuser'],               'slug' => 'insaneuser'],
            ['keywords' => ['veteran'],                            'slug' => 'veteran'],
            ['keywords' => ['seeder'],                             'slug' => 'seeder'],
            ['keywords' => ['archivist'],                          'slug' => 'archivist'],
            ['keywords' => ['leech', 'leecher'],                   'slug' => 'leech'],
            ['keywords' => ['user', 'member', 'registered'],       'slug' => 'user'],
        ] as $rule) {
            $destId = $unit3dGroups->get($rule['slug'])?->id ?? $fallbackId;
            foreach ($rule['keywords'] as $kw) {
                $keywordMap[$kw] = $destId;
            }
        }

        // ── 2. Try to read source groups table ───────────────────────────
        $sourceGroupsTable = $this->tryResolveSourceTable(
            ['groups', 'user_groups', 'member_groups', 'permissions', 'usergroups', 'ranks'],
            $config
        );

        $map = [];  // source_group_id => unit3d_group_id

        if ($sourceGroupsTable !== null) {
            try {
                $sourceGroups = $this->sourceQuery("SELECT * FROM `{$sourceGroupsTable}`");

                foreach ($sourceGroups as $sg) {
                    // Find the numeric id column in source group row
                    $srcId = $sg['id'] ?? $sg['group_id'] ?? $sg['gid'] ?? null;
                    if ($srcId === null) {
                        continue;
                    }

                    // Find the name column
                    $srcName = strtolower(
                        $sg['name'] ?? $sg['group_name'] ?? $sg['title'] ?? ''
                    );

                    // Exact slug match
                    if ($unit3dGroups->has($srcName)) {
                        $map[(int) $srcId] = $unit3dGroups->get($srcName)->id;
                        continue;
                    }

                    // Exact name match against UNIT3D group names
                    $nameMatch = $unit3dGroups->first(
                        fn ($g) => strtolower($g->name) === $srcName
                    );
                    if ($nameMatch) {
                        $map[(int) $srcId] = $nameMatch->id;
                        continue;
                    }

                    // Keyword / substring match
                    $matched = false;
                    foreach ($keywordMap as $kw => $destId) {
                        if (str_contains($srcName, $kw)) {
                            $map[(int) $srcId] = $destId;
                            $matched = true;
                            break;
                        }
                    }

                    if (!$matched) {
                        $map[(int) $srcId] = $fallbackId;
                    }
                }

                $this->log("Group map built from `{$sourceGroupsTable}`: " . count($map) . " source groups mapped.");
            } catch (\Throwable $e) {
                $this->log("Could not read source groups table: " . $e->getMessage());
            }
        } else {
            $this->log("No source groups table found — all users will be assigned the User group.");
        }

        return ['map' => $map, 'fallback' => $fallbackId];
    }

    /**
     * Migrate users from source to destination
     */
    public function migrateUsers(array $sourceConfig): array
    {
        $this->log('Starting user migration...');

        try {
            $this->ensureConnected($sourceConfig);

            // Streamed in chunks — avoids loading all rows into RAM
            $users = $this->chunkSourceQuery('SELECT * FROM users ORDER BY id', [], 300);

            // Build group map: source_group_id => unit3d_group_id
            ['map' => $groupMap, 'fallback' => $fallbackGroupId] = $this->buildGroupMap($sourceConfig);

            $count = 0;
            $batch = [];
            $batchSize = 50;

            foreach ($users as $user) {  // generator — one row at a time
                try {
                    // Support 'password', 'passwd', 'pass_hash', 'user_password', etc.
                    $rawPass  = $user['password'] ?? $user['passwd'] ?? $user['pass_hash'] ?? $user['user_password'] ?? null;
                    $password = !empty($rawPass) ? $rawPass : bcrypt(bin2hex(random_bytes(16)));

                    // Resolve group: use the mapped group if we have a source group_id,
                    // otherwise try to guess from the user row itself (e.g. 'class', 'rank')
                    $srcGroupId = (int) ($user['group_id'] ?? $user['class'] ?? $user['rank'] ?? $user['role_id'] ?? 0);
                    $destGroupId = $groupMap[$srcGroupId] ?? $fallbackGroupId;

                    // Handle date fields safely — NULL or invalid dates/timestamps become null
                    $parseDate = static function (mixed $v): ?string {
                        if (empty($v) || $v === '0000-00-00 00:00:00' || $v === '0') {
                            return null;
                        }
                        // Could be a Unix timestamp (integer) or a date string
                        $ts = is_numeric($v) ? (int) $v : @strtotime((string) $v);
                        return ($ts && $ts > 0) ? date('Y-m-d H:i:s', $ts) : null;
                    };

                    $batch[] = [
                        // Identity
                        'id'          => (int) $user['id'],
                        'username'    => (string) ($user['username'] ?? $user['user_name'] ?? $user['name'] ?? 'user_' . $user['id']),
                        'email'       => (string) ($user['email'] ?? $user['email_address'] ?? $user['mail'] ?? ''),
                        'password'    => $password,
                        'passkey'     => (string) ($user['passkey'] ?? $user['torrent_pass'] ?? $user['announce_key'] ?? bin2hex(random_bytes(16))),
                        'rsskey'      => (string) ($user['rsskey'] ?? $user['rss_key'] ?? $user['feed_key'] ?? bin2hex(random_bytes(16))),
                        // Group (mapped from source)
                        'group_id'    => $destGroupId,
                        // Stats
                        'uploaded'    => (int) ($user['uploaded'] ?? $user['upload'] ?? 0),
                        'downloaded'  => (int) ($user['downloaded'] ?? $user['download'] ?? 0),
                        'seedbonus'   => (float) ($user['seedbonus'] ?? $user['bonus'] ?? $user['points'] ?? 0),
                        'fl_tokens'   => (int) ($user['fl_tokens'] ?? $user['freelech_tokens'] ?? 0),
                        'invites'     => (int) ($user['invites'] ?? 0),
                        'hitandruns'  => (int) ($user['hitandruns'] ?? $user['hnr'] ?? 0),
                        // Profile
                        'image'       => $user['image'] ?? $user['avatar'] ?? $user['profile_pic'] ?? null,
                        'title'       => $user['title'] ?? $user['custom_title'] ?? null,
                        'about'       => $user['about'] ?? $user['profile'] ?? $user['bio'] ?? null,
                        'signature'   => $user['signature'] ?? $user['sig'] ?? null,
                        // Permissions (nullable in UNIT3D — null inherits from group)
                        'can_chat'     => isset($user['can_chat'])     ? (int) (bool) $user['can_chat']     : null,
                        'can_download' => isset($user['can_download'])  ? (int) (bool) $user['can_download'] : 1,
                        'can_request'  => isset($user['can_request'])   ? (int) (bool) $user['can_request']  : null,
                        'can_invite'   => isset($user['can_invite'])    ? (int) (bool) $user['can_invite']   : null,
                        'can_upload'   => isset($user['can_upload'])    ? (int) (bool) $user['can_upload']   : null,
                        // Timestamps
                        'last_login'  => $parseDate($user['last_login'] ?? $user['lastvisit'] ?? $user['last_seen'] ?? null),
                        'created_at'  => $parseDate($user['registered'] ?? $user['created_at'] ?? $user['joindate'] ?? $user['join_date'] ?? null) ?? now(),
                        'updated_at'  => now(),
                    ];

                    if (count($batch) >= $batchSize) {
                        DB::table('users')->insertOrIgnore($batch);
                        $count += count($batch);
                        $batch = [];
                    }
                } catch (\Throwable $e) {
                    $uid = $user['id'] ?? '?';
                    $uname = $user['username'] ?? '?';
                    $this->log("Skipped user #{$uid} ({$uname}): " . $e->getMessage());
                }
            }

            if (!empty($batch)) {
                DB::table('users')->insertOrIgnore($batch);
                $count += count($batch);
            }

            $this->log("User migration completed: {$count} users migrated");

            return ['success' => true, 'count' => $count, 'logs' => $this->migrationLog];
        } catch (\Throwable $e) {
            $this->log('User migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'logs' => $this->migrationLog];
        }
    }

    public function migrateTorrents(array $sourceConfig): array
    {
        $this->log('Starting torrent migration...');

        try {
            $this->ensureConnected($sourceConfig);

            $torrents = $this->chunkSourceQuery('SELECT * FROM torrents ORDER BY id', [], 300);

            $count = 0;
            $batch = [];
            $batchSize = 100;

            foreach ($torrents as $torrent) {  // generator
                try {
                    // Support both 'info_hash' and 'infohash' column names
                    $rawHash  = $torrent['info_hash'] ?? $torrent['infohash'] ?? $torrent['torrent_hash'] ?? null;
                    $infohash = $rawHash !== null ? strtolower($rawHash) : null;

                    if ($infohash === null) {
                        continue;
                    }

                    $existing = DB::table('torrents')->where('infohash', $infohash)->first();

                    if (!$existing) {
                        $batch[] = [
                            'name'        => $torrent['name'] ?? $torrent['torrent_name'] ?? 'Unknown',
                            'category_id' => $this->mapCategory((int) ($torrent['category'] ?? $torrent['cat_id'] ?? $torrent['category_id'] ?? 0)),
                            'description' => $torrent['description'] ?? $torrent['details'] ?? $torrent['torrent_description'] ?? '',
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

            return ['success' => true, 'count' => $count, 'logs' => $this->migrationLog];
        } catch (\Throwable $e) {
            $this->log('Torrent migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'logs' => $this->migrationLog];
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

            $peers = $this->chunkSourceQuery('SELECT * FROM peers ORDER BY userid', [], 500);

            $count = 0;
            $batch = [];
            $batchSize = 500;

            foreach ($peers as $peer) {  // generator
                try {
                    $peerHash = $peer['infohash'] ?? $peer['info_hash'] ?? $peer['torrent_hash'] ?? null;
                    if ($peerHash === null) {
                        continue;
                    }

                    $batch[] = [
                        'infohash'   => strtolower($peerHash),
                        'peerid'     => $peer['peerid'] ?? $peer['peer_id'] ?? '',
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

            return ['success' => true, 'count' => $count, 'logs' => $this->migrationLog];
        } catch (\Throwable $e) {
            $this->log('Peer migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'logs' => $this->migrationLog];
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

            $snatched = $this->chunkSourceQuery('SELECT * FROM snatched ORDER BY userid', [], 500);

            $count = 0;
            $batch = [];
            $batchSize = 500;

            foreach ($snatched as $snatch) {  // generator
                try {
                    $snatchHash = $snatch['infohash'] ?? $snatch['info_hash'] ?? $snatch['torrent_hash'] ?? null;
                    if ($snatchHash === null) {
                        continue;
                    }

                    $batch[] = [
                        'infohash'     => strtolower($snatchHash),
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

            return ['success' => true, 'count' => $count, 'logs' => $this->migrationLog];
        } catch (\Throwable $e) {
            $this->log('Snatched migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'logs' => $this->migrationLog];
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

            $forumsTable = $this->resolveSourceTable(
                ['tsf_forums', 'forums', 'forum_categories', 'forum_sections', 'categories'],
                $sourceConfig
            );
            $this->log("Forum source table resolved: {$forumsTable}");
            $forums = $this->sourceQuery("SELECT * FROM `{$forumsTable}`");

            $count    = 0;
            $forumMap = [];

            foreach ($forums as $forum) {
                try {
                    $forumName = $forum['name'] ?? $forum['forum_name'] ?? 'Forum';
                    $existing  = DB::table('forums')->where('name', $forumName)->first();
                    if (!$existing) {
                        $newId = DB::table('forums')->insertGetId([
                            'name'        => $forumName,
                            'description' => $forum['description'] ?? $forum['forum_desc'] ?? '',
                            'icon'        => $forum['icon'] ?? null,
                            'position'    => $forum['id'] ?? $forum['fid'] ?? $forum['forum_id'] ?? 0,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                        $sourceId           = $forum['id'] ?? $forum['fid'] ?? $forum['forum_id'] ?? 0;
                        $forumMap[$sourceId] = $newId;
                        $count++;
                    }
                } catch (\Throwable $e) {
                    $this->log("Error migrating forum {$forum['name']}: " . $e->getMessage());
                }
            }

            Cache::put('forum_id_map', $forumMap, now()->addHours(24));
            Cache::put('forum_source_db', $sourceConfig['database'], now()->addHours(24));

            $this->log("Forum migration completed: {$count} forums migrated");

            return ['success' => true, 'count' => $count, 'logs' => $this->migrationLog];
        } catch (\Throwable $e) {
            $this->log('Forum migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'logs' => $this->migrationLog];
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

            $threadsTable = $this->resolveSourceTable(
                ['tsf_threads', 'forum_threads', 'threads', 'topics', 'forum_topics'],
                $sourceConfig
            );
            $this->log("Thread source table resolved: {$threadsTable}");
            $threads = $this->sourceQuery("SELECT * FROM `{$threadsTable}`");
            $count    = 0;
            $batch    = [];
            $batchSize = 200;
            $forumMap = Cache::get('forum_id_map', []);

            foreach ($threads as $thread) {
                try {
                    $sourceFid  = $thread['forum_id'] ?? $thread['fid'] ?? 0;
                    $newForumId = $forumMap[$sourceFid] ?? null;

                    if ($newForumId) {
                        $threadDate = $thread['thread_date'] ?? $thread['created_at'] ?? $thread['post_date'] ?? null;
                        $batch[] = [
                            'forum_id'   => $newForumId,
                            'user_id'    => $thread['user_id'] ?? $thread['uid'] ?? $thread['author_id'] ?? 0,
                            'title'      => $thread['title'] ?? $thread['thread_title'] ?? $thread['subject'] ?? 'Thread',
                            'sticky'     => (bool) ($thread['sticky'] ?? $thread['pinned'] ?? false),
                            'locked'     => (bool) ($thread['locked'] ?? $thread['closed'] ?? false),
                            'views'      => (int) ($thread['views'] ?? 0),
                            'created_at' => $threadDate ? date('Y-m-d H:i:s', (int) $threadDate) : now(),
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

            return ['success' => true, 'count' => $count, 'logs' => $this->migrationLog];
        } catch (\Throwable $e) {
            $this->log('Forum threads migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'logs' => $this->migrationLog];
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

            $postsTable = $this->resolveSourceTable(
                ['tsf_posts', 'forum_posts', 'posts', 'messages', 'forum_messages'],
                $sourceConfig
            );
            $this->log("Post source table resolved: {$postsTable}");
            $posts = $this->sourceQuery("SELECT * FROM `{$postsTable}`");

            $count = 0;
            $batch = [];
            $batchSize = 500;

            foreach ($posts as $post) {
                try {
                    $postDate   = $post['post_date'] ?? $post['created_at'] ?? $post['date_posted'] ?? null;
                    $editedDate = $post['edited_at'] ?? $post['edited_time'] ?? $post['updated_at'] ?? null;
                    $batch[] = [
                        'thread_id'  => $post['thread_id'] ?? $post['tid'] ?? 0,
                        'user_id'    => $post['user_id'] ?? $post['uid'] ?? $post['author_id'] ?? 0,
                        'content'    => $post['post_text'] ?? $post['message'] ?? $post['content'] ?? $post['body'] ?? '',
                        'created_at' => $postDate   ? date('Y-m-d H:i:s', (int) $postDate)   : now(),
                        'updated_at' => $editedDate ? date('Y-m-d H:i:s', (int) $editedDate) : now(),
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

            return ['success' => true, 'count' => $count, 'logs' => $this->migrationLog];
        } catch (\Throwable $e) {
            $this->log('Forum posts migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'logs' => $this->migrationLog];
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
                'forums'        => $this->getTableRowCount(
                    $this->tryResolveSourceTable(['tsf_forums', 'forums', 'forum_categories', 'forum_sections', 'categories'], $sourceConfig),
                    $sourceConfig
                ),
                'forum_threads' => $this->getTableRowCount(
                    $this->tryResolveSourceTable(['tsf_threads', 'forum_threads', 'threads', 'topics', 'forum_topics'], $sourceConfig),
                    $sourceConfig
                ),
                'forum_posts'   => $this->getTableRowCount(
                    $this->tryResolveSourceTable(['tsf_posts', 'forum_posts', 'posts', 'messages', 'forum_messages'], $sourceConfig),
                    $sourceConfig
                ),
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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Private helpers
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
     * Resolve which table name to use on the SOURCE database.
     * Tries each candidate in order and returns the first that exists.
     * Throws RuntimeException if none exist.
     *
     * @param  string[]  $candidates
     */
    private function resolveSourceTable(array $candidates, array $config): string
    {
        foreach ($candidates as $table) {
            try {
                $rows = $this->sourceQuery(
                    'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                    [$config['database'], $table]
                );
                if (!empty($rows)) {
                    return $table;
                }
            } catch (\Throwable) {
                // driver error — try next candidate
            }
        }

        throw new \RuntimeException(
            'Could not find a matching source table. Tried: ' . implode(', ', $candidates)
        );
    }

    /**
     * Like resolveSourceTable() but returns null instead of throwing when
     * none of the candidates exist. Safe to call in summary queries.
     *
     * @param  string[]  $candidates
     */
    private function tryResolveSourceTable(array $candidates, array $config): ?string
    {
        foreach ($candidates as $table) {
            try {
                $rows = $this->sourceQuery(
                    'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                    [$config['database'], $table]
                );
                if (!empty($rows)) {
                    return $table;
                }
            } catch (\Throwable) {
                // continue
            }
        }

        return null;
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
