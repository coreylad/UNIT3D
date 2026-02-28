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

    /** Group column name discovered in the source users table */
    private ?string $resolvedGroupCol = null;

    /** Cached result of buildGroupMap() to avoid re-querying on every page */
    private ?array $cachedGroupMap = null;

    /** user_id → source_group_id lookup from pivot table (when no group col on users) */
    private ?array $userGroupLookup = null;

    /** Cached source category_id → UNIT3D category_id map */
    private ?array $cachedCategoryMap = null;

    /**
     * source_user_id → unit3d_user_id
     * Only populated when a TSSE user ID collides with an existing UNIT3D user.
     * All other users are inserted with their original ID so FK references remain valid.
     */
    private array $userIdRemap = [];

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
    /**
     * @param  array<int|string, int>  $userOverrides  srcGroupId => unit3dGroupId
     */
    private function buildGroupMap(array $config, array $userOverrides = []): array
    {
        // ── 1. Load all UNIT3D destination groups ────────────────────────
        $unit3dGroups = DB::table('groups')
            ->get(['id', 'name', 'slug']);

        $bySlug = $unit3dGroups->keyBy('slug');
        $byName = $unit3dGroups->keyBy(fn ($g) => strtolower($g->name));

        $fallbackId = $bySlug->get('user')?->id
            ?? $unit3dGroups->first()?->id
            ?? 1;

        $this->log('UNIT3D groups available: ' . $unit3dGroups->map(fn ($g) => "#{$g->id} {$g->name}")->implode(', '));

        // ── 2. Keyword → UNIT3D group id table ───────────────────────────
        // Ordered longest-first so "power user" matches before "user"
        $keywordRules = [
            // ── Exact staff role matches (longest / most-specific first) ──────
            ['keywords' => ['sysop', 'sys op', 'root', 'siteop'],              'slug' => 'administrator'],
            ['keywords' => ['owner', 'founder', 'co-owner', 'coowner'],       'slug' => 'owner'],
            ['keywords' => ['super admin', 'superadmin'],                      'slug' => 'administrator'],
            ['keywords' => ['team leader', 'team lead'],                       'slug' => 'administrator'],
            ['keywords' => ['security team', 'sec team'],                      'slug' => 'administrator'],
            ['keywords' => ['admin', 'administrator', 'staff admin'],          'slug' => 'administrator'],
            ['keywords' => ['technical team', 'tech team'],                    'slug' => 'moderator'],
            ['keywords' => ['torrent mod', 'torrent moderator', 'torrentmod'], 'slug' => 'torrent-moderator'],
            ['keywords' => ['forum mod', 'forum moderator', 'forummod'],      'slug' => 'moderator'],
            ['keywords' => ['super mod', 'supermod', 'senior mod', 'smod'],   'slug' => 'moderator'],
            ['keywords' => ['mod', 'moderator', 'modo', 'global mod'],        'slug' => 'moderator'],
            ['keywords' => ['staff'],                                          'slug' => 'moderator'],
            // ── Upload / content staff ────────────────────────────────────────
            ['keywords' => ['site artist', 'artist'],                          'slug' => 'uploader'],
            ['keywords' => ['encoder', 'internal'],                            'slug' => 'uploader'],
            ['keywords' => ['uploader', 'upload'],                             'slug' => 'uploader'],
            // ── Elevated users ────────────────────────────────────────────────
            ['keywords' => ['trustee', 'trusted', 'trusted user'],            'slug' => 'trustee'],
            ['keywords' => ['beta test', 'beta tester', 'beta'],              'slug' => 'poweruser'],
            ['keywords' => ['ex vip', 'exvip', 'former vip', 'ex-vip'],      'slug' => 'poweruser'],
            ['keywords' => ['insane', 'insaneuser'],                           'slug' => 'insaneuser'],
            ['keywords' => ['extreme', 'extremeuser'],                         'slug' => 'extremeuser'],
            ['keywords' => ['super user', 'superuser'],                        'slug' => 'superuser'],
            ['keywords' => ['power user', 'poweruser', 'powerpeer', 'elite'], 'slug' => 'poweruser'],
            ['keywords' => ['veteran', 'vet'],                                 'slug' => 'veteran'],
            ['keywords' => ['seeder', 'top seeder'],                           'slug' => 'seeder'],
            ['keywords' => ['archivist'],                                      'slug' => 'archivist'],
            // ── Donor / VIP tiers ─────────────────────────────────────────────
            ['keywords' => ['btc', 'bitcoin', 'crypto', 'legend'],            'slug' => 'vip'],
            ['keywords' => ['vip', 'donator', 'donor', 'supporter', 'premium'], 'slug' => 'vip'],
            // ── Restricted / lifecycle ────────────────────────────────────────
            ['keywords' => ['ban', 'banned', 'suspended'],                    'slug' => 'banned'],
            ['keywords' => ['disabled', 'deactivated', 'locked'],             'slug' => 'disabled'],
            ['keywords' => ['pruned', 'deleted', 'removed', 'purged'],        'slug' => 'pruned'],
            ['keywords' => ['validating', 'pending', 'unvalidated', 'inactive', 'unconfirmed', 'awaiting', 'not confirmed', 'parked'], 'slug' => 'validating'],
            ['keywords' => ['leech', 'leecher'],                               'slug' => 'leech'],
            ['keywords' => ['user', 'member', 'registered', 'normal', 'default', 'regular', 'guest', 'basic'], 'slug' => 'user'],
        ];

        // Numeric class → UNIT3D slug (common TBDev/xbt/UNIT3D-classic ordering)
        $classRules = [
            0 => 'user',           // Regular user
            1 => 'poweruser',      // Power User
            2 => 'superuser',      // Super User / VIP
            3 => 'uploader',       // Uploader
            4 => 'moderator',      // Moderator
            5 => 'moderator',      // Senior Moderator
            6 => 'administrator',  // Administrator
            7 => 'administrator',  // Sysop / Owner
            // Also map common banned/disabled sentinels
            -1 => 'banned',
            -2 => 'disabled',
        ];

        $resolveByKeyword = function (string $srcName) use ($keywordRules, $bySlug, $byName, $fallbackId): int {
            $lower = strtolower(trim($srcName));

            // Exact slug match
            if ($bySlug->has($lower)) {
                return $bySlug->get($lower)->id;
            }
            // Exact name match (case-insensitive)
            if ($byName->has($lower)) {
                return $byName->get($lower)->id;
            }
            // Slugified match: "Power User" → "power-user"
            $slugified = preg_replace('/[^a-z0-9]+/', '-', $lower);
            $slugified = trim($slugified, '-');
            if ($bySlug->has($slugified)) {
                return $bySlug->get($slugified)->id;
            }
            // Also try without separator: "poweruser"
            $compact = str_replace([' ', '-', '_'], '', $lower);
            $compactSlug = $bySlug->first(fn ($g) => str_replace('-', '', $g->slug) === $compact);
            if ($compactSlug) {
                return $compactSlug->id;
            }
            // Keyword substring match (rules are ordered longest-first)
            foreach ($keywordRules as $rule) {
                foreach ($rule['keywords'] as $kw) {
                    if (str_contains($lower, $kw)) {
                        return $bySlug->get($rule['slug'])?->id ?? $fallbackId;
                    }
                }
            }

            return $fallbackId;
        };

        // ── 3. Discover the group column used in the source users table ──
        // We ask the DB directly rather than guessing
        $groupColCandidates = ['usergroup', 'group_id', 'class', 'rank', 'role_id', 'permission_id', 'user_class', 'level'];
        $groupCol           = null;

        try {
            $userCols = $this->sourceColumn(
                'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [$config['database'], 'users']
            );

            foreach ($groupColCandidates as $candidate) {
                if (in_array($candidate, $userCols, true)) {
                    $groupCol = $candidate;
                    break;
                }
            }
        } catch (\Throwable $e) {
            $this->log('Could not inspect users columns: ' . $e->getMessage());
        }

        $this->log("Source group column in users table: " . ($groupCol ?? '(none found — will use 0)'));

        // Collect the distinct group values actually used in the users table
        $distinctGroupValues = [];
        if ($groupCol !== null) {
            try {
                $distinctGroupValues = array_map(
                    'intval',
                    $this->sourceColumn("SELECT DISTINCT `{$groupCol}` FROM `users` WHERE `{$groupCol}` IS NOT NULL ORDER BY `{$groupCol}`")
                );
                $this->log('Distinct source group values: ' . implode(', ', $distinctGroupValues));
            } catch (\Throwable $e) {
                $this->log('Could not read distinct group values: ' . $e->getMessage());
            }
        }

        // ── 4. Try to find a named groups table in the source DB ─────────
        $sourceGroupsTable = $this->tryResolveSourceTable(
            ['groups', 'user_groups', 'member_groups', 'permissions', 'usergroups',
             'ranks', 'classes', 'user_classes', 'roles', 'user_roles',
             'tsf_groups', 'tsf_user_groups', 'tsf_classes', 'tsf_roles'],
            $config
        );

        $map = [];  // source_group_id => unit3d_group_id

        if ($sourceGroupsTable !== null) {
            $this->log("Source groups table found: `{$sourceGroupsTable}`");

            try {
                $sourceGroups = $this->sourceQuery("SELECT * FROM `{$sourceGroupsTable}`");
                $this->log('Source groups rows: ' . count($sourceGroups));

                foreach ($sourceGroups as $sg) {
                    $srcId = $sg['id'] ?? $sg['group_id'] ?? $sg['gid'] ?? $sg['class_id'] ?? null;
                    if ($srcId === null) {
                        continue;
                    }
                    $srcId = (int) $srcId;

                    $srcName = (string) ($sg['name'] ?? $sg['group_name'] ?? $sg['title'] ?? $sg['class_name'] ?? '');

                    $destId   = $resolveByKeyword($srcName);
                    $destName = $unit3dGroups->first(fn ($g) => $g->id === $destId)?->name ?? "id={$destId}";
                    $map[$srcId] = $destId;

                    $this->log("  src #{$srcId} '{$srcName}' → UNIT3D '{$destName}' (id={$destId})");
                }

                $this->log('Group map built from `' . $sourceGroupsTable . '`: ' . count($map) . ' source groups mapped.');
            } catch (\Throwable $e) {
                $this->log('Could not read source groups table: ' . $e->getMessage());
                $sourceGroupsTable = null;  // fall through to numeric mapping
            }
        }

        // ── 5. Fill in any missing group values using numeric class rules ─
        // Covers: values in users that don't appear in the groups table,
        // OR trackers that have no groups table at all (TBDev class-based).
        $missingValues = array_diff($distinctGroupValues, array_keys($map));
        if (!empty($missingValues)) {
            $this->log('Group values in users not covered by groups table: ' . implode(', ', $missingValues) . ' — applying numeric class fallback');

            foreach ($missingValues as $classVal) {
                $slug   = $classRules[$classVal] ?? 'user';
                $destId = $bySlug->get($slug)?->id ?? $fallbackId;
                $map[$classVal] = $destId;
                $destName = $unit3dGroups->first(fn ($g) => $g->id === $destId)?->name ?? "id={$destId}";
                $this->log("  class {$classVal} → UNIT3D '{$destName}' (slug={$slug}, id={$destId})");
            }
        }

        if (empty($map)) {
            $this->log('No group mappings resolved — all users will use the fallback User group.');
        }

        // ── 6. If no group column on users, find the user→group pivot table ─
        // Many trackers (xBtit, TBSource, etc.) store user groups in a
        // junction/pivot table rather than as a column on users.
        $userGroupLookup = [];
        if ($groupCol === null && !empty($map)) {
            $pivotCandidates = [
                // table_name => [user_col, group_col]
                'users_groups'       => [['user_id', 'userid', 'uid'], ['group_id', 'groupid', 'gid']],
                'user_group'         => [['user_id', 'userid', 'uid'], ['group_id', 'groupid', 'gid']],
                'users_usergroups'   => [['user_id', 'userid', 'uid'], ['group_id', 'groupid', 'gid', 'usergroup_id']],
                'user_usergroup'     => [['user_id', 'userid', 'uid'], ['group_id', 'groupid', 'gid', 'usergroup_id']],
                'xbt_users_groups'   => [['user_id', 'userid', 'uid'], ['group_id', 'groupid', 'gid']],
                'tsf_users_groups'   => [['user_id', 'userid', 'uid'], ['group_id', 'groupid', 'gid']],
            ];

            // Also check if the discovered groups table itself has user_id (acts as pivot)
            if ($sourceGroupsTable !== null && !isset($pivotCandidates[$sourceGroupsTable])) {
                // Already loaded its rows above — check if any row has a user_id column
                $firstRow = $sourceGroups[0] ?? [];
                if (isset($firstRow['user_id']) || isset($firstRow['userid']) || isset($firstRow['uid'])) {
                    $pivotCandidates = [$sourceGroupsTable => [['user_id', 'userid', 'uid'], ['group_id', 'groupid', 'gid']]] + $pivotCandidates;
                }
            }

            foreach ($pivotCandidates as $pivotTable => $colSets) {
                $foundTable = $this->tryResolveSourceTable([$pivotTable], $config);
                if ($foundTable === null) {
                    continue;
                }

                // Discover actual column names in this pivot table
                try {
                    $pivotCols = $this->sourceColumn(
                        'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                        [$config['database'], $foundTable]
                    );

                    $userCol  = null;
                    $groupCol2 = null;
                    foreach ($colSets[0] as $c) {
                        if (in_array($c, $pivotCols, true)) { $userCol = $c; break; }
                    }
                    foreach ($colSets[1] as $c) {
                        if (in_array($c, $pivotCols, true)) { $groupCol2 = $c; break; }
                    }

                    if ($userCol && $groupCol2) {
                        $this->log("User→group pivot table found: `{$foundTable}` ({$userCol}, {$groupCol2})");
                        $pivotRows = $this->sourceQuery(
                            "SELECT `{$userCol}` AS uid, `{$groupCol2}` AS gid FROM `{$foundTable}`"
                        );
                        foreach ($pivotRows as $pr) {
                            // Keep the highest-privilege group (lowest id often = more privileged in many trackers,
                            // but we'll just take the first non-1 match or the first one)
                            $uid = (int) $pr['uid'];
                            $gid = (int) $pr['gid'];
                            if (!isset($userGroupLookup[$uid])) {
                                $userGroupLookup[$uid] = $gid;
                            } else {
                                // Prefer higher group id as it's often more privileged (admin=6, mod=5...)
                                // But actually prefer the one that maps to a non-User group
                                $existingDest = $map[$userGroupLookup[$uid]] ?? $fallbackId;
                                $newDest      = $map[$gid] ?? $fallbackId;
                                if ($existingDest === $fallbackId && $newDest !== $fallbackId) {
                                    $userGroupLookup[$uid] = $gid;
                                }
                            }
                        }
                        $this->log("Pivot lookup built: " . count($userGroupLookup) . " user→group entries");
                        break; // found, stop looking
                    }
                } catch (\Throwable $e) {
                    $this->log("Error reading pivot `{$foundTable}`: " . $e->getMessage());
                }
            }

            if (empty($userGroupLookup)) {
                $this->log('No user→group pivot table found — will fall back to User group for all.');
            }
        }

        $this->userGroupLookup = !empty($userGroupLookup) ? $userGroupLookup : null;

        // Store the resolved group column so migrateUsers can use it
        $this->resolvedGroupCol = $groupCol;

        // ── 7. Apply user-supplied overrides (highest priority) ───────────────
        if (!empty($userOverrides)) {
            foreach ($userOverrides as $srcId => $destId) {
                $map[(int) $srcId] = (int) $destId;
            }
            $this->log('Applied ' . count($userOverrides) . ' user-defined group overrides.');
        }

        return ['map' => $map, 'fallback' => $fallbackId, 'groupCol' => $groupCol];
    }

    /**
     * Migrate users from source to destination
     */
    /**
     * Return all rows from the source tracker's group/rank table.
     * Used by the migration UI to build the group mapping editor.
     */
    public function getSourceGroups(array $config): array
    {
        $this->ensureConnected($config);

        $table = $this->tryResolveSourceTable(
            ['usergroups', 'groups', 'user_groups', 'member_groups', 'permissions', 'ranks', 'classes'],
            $config
        );

        if ($table === null) {
            return [];
        }

        return $this->sourceQuery("SELECT * FROM `{$table}`");
    }

    /**
     * Return a suggested srcGroupId → unit3dGroupId map using the auto-detection
     * logic in buildGroupMap (no user overrides applied).
     * Used by the UI to pre-populate the group mapping editor dropdowns.
     *
     * @return array<int, int>  srcGroupId => unit3dGroupId
     */
    public function getGroupSuggestions(array $config): array
    {
        $result = $this->buildGroupMap($config);

        return $result['map'];
    }

    /**
     * @param  array<int|string, int>  $groupOverrides  srcGroupId => unit3dGroupId (user-defined overrides)
     */
    public function migrateUsers(array $sourceConfig, int $offset = 0, int $limit = 100, array $groupOverrides = []): array
    {
        $this->log("Starting user migration (offset={$offset}, limit={$limit})...");

        try {
            $this->ensureConnected($sourceConfig);

            $users   = $this->sourceQuery("SELECT * FROM users ORDER BY id LIMIT {$limit} OFFSET {$offset}");
            $fetched = count($users);

            // Build group map once; cache for subsequent paginated calls.
            // On the first page, $groupOverrides are applied and stored in the cache
            // so all subsequent pages use the same resolved map.
            if ($this->cachedGroupMap === null) {
                $this->cachedGroupMap = $this->buildGroupMap($sourceConfig, $groupOverrides);
            }
            ['map' => $groupMap, 'fallback' => $fallbackGroupId, 'groupCol' => $groupCol] = $this->cachedGroupMap;

            $count = 0;
            $batch = [];
            $batchSize = 50;
            $unmappedCount = 0;

            // Handle date fields safely — NULL or invalid dates/timestamps become null
            $parseDate = static function (mixed $v): ?string {
                if (empty($v) || $v === '0000-00-00 00:00:00' || $v === '0') {
                    return null;
                }
                // Could be a Unix timestamp (integer) or a date string
                $ts = is_numeric($v) ? (int) $v : @strtotime((string) $v);
                return ($ts && $ts > 0) ? date('Y-m-d H:i:s', $ts) : null;
            };

            foreach ($users as $user) {  // generator — one row at a time
                try {
                    // Use the column discovered by buildGroupMap, or the pivot lookup
                    $srcGroupId = null;
                    if ($groupCol !== null && isset($user[$groupCol])) {
                        $srcGroupId = (int) $user[$groupCol];
                    } elseif ($this->userGroupLookup !== null) {
                        $uid = (int) $user['id'];
                        $srcGroupId = $this->userGroupLookup[$uid] ?? null;
                    }
                    // Final fallback chain if neither pivot nor column worked
                    if ($srcGroupId === null) {
                        $srcGroupId = (int) ($user['usergroup'] ?? $user['group_id'] ?? $user['class'] ?? $user['rank'] ?? $user['role_id'] ?? 0);
                    }
                    $destGroupId = $groupMap[$srcGroupId] ?? $fallbackGroupId;
                    if (!isset($groupMap[$srcGroupId])) {
                        $unmappedCount++;
                        if ($unmappedCount <= 3) {
                            $this->log("  User #{$user['id']} srcGroup={$srcGroupId} not in map → fallback group {$fallbackGroupId}");
                        }
                    }

                    // TSSE legacy password bridge:
                    // passhash = md5(secret + plaintext + secret) — do NOT rehash here.
                    // Set password to '!' (impossible bcrypt match) and store the raw
                    // MD5 hash + secret so FortifyServiceProvider can rehash on first login.
                    $legacyPasshash = $user['passhash'] ?? null;
                    $legacySecret   = $user['secret']   ?? null;
                    $isLegacy       = $legacyPasshash !== null && $legacySecret !== null;

                    // TSSE email verification: status='confirmed' → verified, 'pending' → null
                    $emailVerifiedAt = ($user['status'] ?? 'pending') === 'confirmed'
                        ? now()->toDateTimeString()
                        : null;

                    // TSSE donor flag stored as 'yes'/'no'
                    $isDonor = ($user['donor'] ?? 'no') === 'yes' ? 1 : 0;

                    $batch[] = [
                        // Identity
                        'id'       => (int) $user['id'],
                        'username' => (string) ($user['username'] ?? $user['user_name'] ?? $user['name'] ?? 'user_' . $user['id']),
                        'email'    => (string) ($user['email'] ?? $user['email_address'] ?? $user['mail'] ?? ''),

                        // Auth — legacy bridge; real password written on first successful login
                        'password'        => '!',
                        'legacy_passhash' => $legacyPasshash,
                        'legacy_secret'   => $legacySecret,
                        'legacy'          => $isLegacy,

                        // Torrent passkey — TSSE uses torrent_pass
                        'passkey' => (string) ($user['torrent_pass'] ?? $user['passkey'] ?? $user['announce_key'] ?? bin2hex(random_bytes(16))),
                        'rsskey'  => (string) ($user['rsskey'] ?? $user['rss_key'] ?? $user['feed_key'] ?? bin2hex(random_bytes(16))),

                        // Group (mapped from source usergroup)
                        'group_id' => $destGroupId,

                        // Stats
                        'uploaded'   => (int) ($user['uploaded'] ?? $user['upload'] ?? 0),
                        'downloaded' => (int) ($user['downloaded'] ?? $user['download'] ?? 0),
                        'seedbonus'  => (float) ($user['seedbonus'] ?? $user['bonus'] ?? $user['points'] ?? 0),
                        'fl_tokens'  => (int) ($user['fl_tokens'] ?? $user['freelech_tokens'] ?? 0),
                        'invites'    => (int) ($user['invites'] ?? 0),
                        'hitandruns' => (int) ($user['hitandruns'] ?? $user['hnr'] ?? 0),

                        // Profile — TSSE stores bio in `page`
                        'image'     => $user['avatar'] ?? $user['image'] ?? $user['profile_pic'] ?? null,
                        'title'     => $user['title'] ?? $user['custom_title'] ?? null,
                        'about'     => $user['page'] ?? $user['about'] ?? $user['profile'] ?? $user['bio'] ?? null,
                        'signature' => $user['signature'] ?? $user['sig'] ?? null,

                        // Donor status
                        'is_donor' => $isDonor,

                        // Permissions — TSSE uses can_leech for download permission
                        'can_chat'     => isset($user['can_chat'])    ? (int) (bool) $user['can_chat']    : null,
                        'can_download' => isset($user['can_leech'])   ? (int) (bool) $user['can_leech']   : (isset($user['can_download']) ? (int) (bool) $user['can_download'] : 1),
                        'can_request'  => isset($user['can_request']) ? (int) (bool) $user['can_request'] : null,
                        'can_invite'   => isset($user['can_invite'])  ? (int) (bool) $user['can_invite']  : null,
                        'can_upload'   => isset($user['can_upload'])  ? (int) (bool) $user['can_upload']  : null,

                        // Email verification
                        'email_verified_at' => $emailVerifiedAt,

                        // Timestamps — TSSE uses `added` for registration, `last_access` for activity
                        'last_login'  => $parseDate($user['last_login'] ?? $user['lastvisit'] ?? $user['last_seen'] ?? null),
                        'last_action' => $parseDate($user['last_access'] ?? $user['last_action'] ?? null),
                        'created_at'  => $parseDate($user['added'] ?? $user['registered'] ?? $user['created_at'] ?? $user['joindate'] ?? $user['join_date'] ?? null) ?? now(),
                        'updated_at'  => now(),
                    ];

                    if (count($batch) >= $batchSize) {
                        $inserted = $this->insertNewUsers($batch);
                        $count   += $inserted;
                        $batch    = [];
                    }
                } catch (\Throwable $e) {
                    $uid = $user['id'] ?? '?';
                    $uname = $user['username'] ?? '?';
                    $this->log("Skipped user #{$uid} ({$uname}): " . $e->getMessage());
                }
            }

            if (!empty($batch)) {
                $count += $this->insertNewUsers($batch);
            }

            $this->log("User migration completed: {$count} users migrated" . ($unmappedCount > 0 ? " ({$unmappedCount} used fallback group)" : ' (all groups matched)'));

            return ['success' => true, 'count' => $count, 'done' => $fetched < $limit, 'logs' => $this->migrationLog];
        } catch (\Throwable $e) {
            $this->log('User migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'done' => true, 'logs' => $this->migrationLog];
        }
    }

    /**
     * Build a map of source category IDs → UNIT3D category IDs.
     * Matches by name (exact → partial → keyword → fallback to first UNIT3D category).
     */
    private function buildCategoryMap(array $config): array
    {
        if ($this->cachedCategoryMap !== null) {
            return $this->cachedCategoryMap;
        }

        // Load source categories
        $sourceCategories = [];

        try {
            $rows = $this->sourceQuery('SELECT id, name FROM categories ORDER BY id');

            foreach ($rows as $row) {
                $sourceCategories[(int) $row['id']] = strtolower(trim((string) ($row['name'] ?? '')));
            }
        } catch (\Throwable $e) {
            $this->log('buildCategoryMap: could not read source categories — ' . $e->getMessage());
        }

        // Load UNIT3D categories (name → id, lowercase)
        $unit3dCategories = DB::table('categories')
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($c) => [strtolower(trim($c->name)) => (int) $c->id])
            ->all();

        $fallbackId = (int) (DB::table('categories')->value('id') ?? 1);

        $map = [];

        foreach ($sourceCategories as $srcId => $srcName) {
            // 1. Exact name match
            if (isset($unit3dCategories[$srcName])) {
                $map[$srcId] = $unit3dCategories[$srcName];

                continue;
            }

            // 2. Partial match — source name contains unit3d name or vice versa
            $matched = null;

            foreach ($unit3dCategories as $u3dName => $u3dId) {
                if (str_contains($srcName, $u3dName) || str_contains($u3dName, $srcName)) {
                    $matched = $u3dId;

                    break;
                }
            }

            if ($matched !== null) {
                $map[$srcId] = $matched;

                continue;
            }

            // 3. Keyword-based fallback
            $keyword = match (true) {
                str_contains($srcName, 'movie') || str_contains($srcName, 'film')
                    || str_contains($srcName, '4k') || str_contains($srcName, 'uhd')
                    || str_contains($srcName, 'bluray') || str_contains($srcName, 'blu-ray') => 'movie',
                str_contains($srcName, 'tv show') || str_contains($srcName, 'tv pack')
                    || str_contains($srcName, 'episode') || str_contains($srcName, 'series')
                    || str_contains($srcName, 'season') => 'tv show',
                str_contains($srcName, 'music') || str_contains($srcName, 'album')
                    || str_contains($srcName, 'discograph') => 'music',
                str_contains($srcName, 'anime') => 'anime',
                str_contains($srcName, 'ebook') || str_contains($srcName, 'e-book')
                    || str_contains($srcName, 'comic') || str_contains($srcName, 'audiobook') => 'ebook',
                str_contains($srcName, 'game') || str_contains($srcName, 'xbox')
                    || str_contains($srcName, 'playstation') || str_contains($srcName, 'nintendo') => 'game',
                str_contains($srcName, 'app') || str_contains($srcName, 'software') => 'software',
                str_contains($srcName, 'sport') || str_contains($srcName, 'fitness') => 'sport',
                default => null,
            };

            if ($keyword !== null) {
                foreach ($unit3dCategories as $u3dName => $u3dId) {
                    if (str_contains($u3dName, $keyword)) {
                        $map[$srcId] = $u3dId;

                        break;
                    }
                }
            }

            if (!isset($map[$srcId])) {
                $map[$srcId] = $fallbackId;
            }
        }

        $this->log('buildCategoryMap: mapped ' . count($map) . ' source categories');

        $this->cachedCategoryMap = $map;

        return $map;
    }

    public function migrateTorrents(array $sourceConfig, int $offset = 0, int $limit = 500): array
    {
        $this->log("Starting torrent migration (offset={$offset}, limit={$limit})...");

        try {
            $this->ensureConnected($sourceConfig);

            // Build category map once per migration run (cached after first call)
            $categoryMap   = $this->buildCategoryMap($sourceConfig);
            $fallbackCatId = (int) (DB::table('categories')->value('id') ?? 1);
            $defaultTypeId = (int) (DB::table('types')->value('id') ?? 3);

            $torrents = $this->sourceQuery(
                "SELECT id, info_hash, name, filename, descr, category, size, added,
                        numfiles, leechers, seeders, times_completed, hits,
                        visible, banned, owner, free, anonymous, sticky
                 FROM torrents
                 WHERE banned = 'no'
                 ORDER BY id
                 LIMIT {$limit} OFFSET {$offset}"
            );

            $fetched   = count($torrents);
            $count     = 0;
            $skipped   = 0;
            $batch     = [];
            $batchSize = 100;

            $parseDate = static function (?string $value): ?string {
                if ($value === null || $value === '' || $value === '0000-00-00 00:00:00') {
                    return null;
                }

                try {
                    return (new \DateTime($value))->format('Y-m-d H:i:s');
                } catch (\Throwable) {
                    return null;
                }
            };

            foreach ($torrents as $torrent) {
                try {
                    // Skip invisible torrents (banned already filtered in SQL)
                    if (($torrent['visible'] ?? 'yes') === 'no') {
                        $skipped++;

                        continue;
                    }

                    // TSSE stores info_hash as BINARY(20) — convert to 40-char hex string
                    $rawHash = $torrent['info_hash'] ?? null;

                    if ($rawHash === null || $rawHash === '') {
                        $skipped++;

                        continue;
                    }

                    $infoHash = (strlen($rawHash) === 40 && ctype_xdigit($rawHash))
                        ? strtolower($rawHash)
                        : strtolower(bin2hex($rawHash));

                    if (strlen($infoHash) !== 40) {
                        $this->log("Skipping torrent id={$torrent['id']}: invalid info_hash");
                        $skipped++;

                        continue;
                    }

                    $srcCatId   = (int) ($torrent['category'] ?? 0);
                    $categoryId = $categoryMap[$srcCatId] ?? $fallbackCatId;

                    $name = (string) ($torrent['name'] ?? 'Unknown');
                    $slug = \Illuminate\Support\Str::slug($name) ?: ('torrent-' . $torrent['id']);

                    // No source id — let UNIT3D auto-increment assign a new id
                    $batch[$infoHash] = [
                        'name'            => $name,
                        'slug'            => $slug,
                        'description'     => (string) ($torrent['descr'] ?? ''),
                        'info_hash'       => $infoHash,
                        'file_name'       => (string) ($torrent['filename'] ?? ($slug . '.torrent')),
                        'num_file'        => max(1, (int) ($torrent['numfiles'] ?? 1)),
                        'size'            => (float) ($torrent['size'] ?? 0),
                        'leechers'        => max(0, (int) ($torrent['leechers'] ?? 0)),
                        'seeders'         => max(0, (int) ($torrent['seeders'] ?? 0)),
                        'times_completed' => max(0, (int) ($torrent['times_completed'] ?? 0)),
                        'category_id'     => $categoryId,
                        'type_id'         => $defaultTypeId,
                        'user_id'         => $this->applyUserRemap(max(1, (int) ($torrent['owner'] ?? 1))),
                        'imdb'            => '0',
                        'tvdb'            => '0',
                        'tmdb'            => '0',
                        'mal'             => '0',
                        'igdb'            => '0',
                        'free'            => ($torrent['free'] ?? 'no') === 'yes' ? 1 : 0,
                        'anon'            => ($torrent['anonymous'] ?? 'no') === 'yes' ? 1 : 0,
                        'sticky'          => ($torrent['sticky'] ?? 'no') === 'yes' ? 1 : 0,
                        'featured'        => ($torrent['sticky'] ?? 'no') === 'yes' ? 1 : 0,
                        'status'          => 1, // ModerationStatus::APPROVED
                        'moderated_at'    => now()->toDateTimeString(),
                        'moderated_by'    => 1,
                        'stream'          => 0,
                        'doubleup'        => 0,
                        'highspeed'       => 0,
                        'sd'              => 0,
                        'internal'        => 0,
                        'created_at'      => $parseDate($torrent['added'] ?? null) ?? now()->toDateTimeString(),
                        'updated_at'      => now()->toDateTimeString(),
                    ];

                    if (count($batch) >= $batchSize) {
                        $inserted = $this->insertNewTorrents($batch);
                        $count   += $inserted;
                        $skipped += count($batch) - $inserted;
                        $batch    = [];
                    }
                } catch (\Throwable $e) {
                    $this->log("Error on torrent id={$torrent['id']}: " . $e->getMessage());
                }
            }

            if (!empty($batch)) {
                $inserted = $this->insertNewTorrents($batch);
                $count   += $inserted;
                $skipped += count($batch) - $inserted;
            }

            $this->log("Torrent migration: {$count} inserted, {$skipped} skipped (offset={$offset})");

            return [
                'success' => true,
                'count'   => $count,
                'skipped' => $skipped,
                'done'    => $fetched < $limit,
                'logs'    => $this->migrationLog,
            ];
        } catch (\Throwable $e) {
            $this->log('Torrent migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'done' => true, 'logs' => $this->migrationLog];
        }
    }

    /**
     * Insert users, preserving source IDs wherever possible so that all foreign-key
     * references in torrents, peers, and snatched remain valid after migration.
     *
     * When a source ID collides with an existing UNIT3D user (e.g. admin ID=1),
     * the TSSE user is inserted without an explicit ID so MySQL assigns a new one,
     * and the mapping is stored in $this->userIdRemap for downstream use.
     *
     * @param  array<int, array>  $batch
     * @return int  number of rows actually inserted
     */
    private function insertNewUsers(array $batch): int
    {
        if (empty($batch)) {
            return 0;
        }

        // Pre-check which IDs, usernames, and emails already exist in UNIT3D
        $ids       = array_column($batch, 'id');
        $usernames = array_filter(array_column($batch, 'username'));
        $emails    = array_filter(array_column($batch, 'email'));

        $existingIds = DB::table('users')
            ->whereIn('id', $ids)
            ->pluck('id')->flip()->all();

        $existingUsernames = DB::table('users')
            ->whereIn('username', $usernames)
            ->pluck('username')->flip()->all();

        $existingEmails = DB::table('users')
            ->whereIn('email', $emails)
            ->pluck('email')->flip()->all();

        $inserted = 0;

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($batch as $row) {
            $srcId    = $row['id'];
            $username = $row['username'] ?? '(unknown)';
            $email    = $row['email'] ?? '';

            // Skip username/email conflicts up-front with a clear message
            if (isset($existingUsernames[$username])) {
                $this->log("Skipped user TSSE #{$srcId} ({$username}): username already exists in UNIT3D");
                // Track them so downstream FK references still resolve
                $existingId = DB::table('users')->where('username', $username)->value('id');
                if ($existingId && $existingId !== $srcId) {
                    $this->userIdRemap[$srcId] = $existingId;
                }

                continue;
            }

            if ($email && isset($existingEmails[$email])) {
                $this->log("Skipped user TSSE #{$srcId} ({$username}): email already exists in UNIT3D");

                continue;
            }

            if (isset($existingIds[$srcId])) {
                // ID conflict → insert without ID so MySQL assigns a new one
                $rowWithoutId = $row;
                unset($rowWithoutId['id']);

                try {
                    $newId = DB::table('users')->insertGetId($rowWithoutId);
                    $this->userIdRemap[$srcId] = $newId;
                    $this->log("User ID conflict: TSSE #{$srcId} ({$username}) → UNIT3D #{$newId} (auto-remapped)");
                    $inserted++;
                } catch (\Throwable $e) {
                    $this->log("Failed to insert remapped user TSSE #{$srcId} ({$username}): " . $e->getMessage());
                }
            } else {
                // No conflict → insert with original ID preserved
                try {
                    DB::table('users')->insert($row);
                    $inserted++;
                } catch (\Throwable $e) {
                    $this->log("Skipped user TSSE #{$srcId} ({$username}): " . $e->getMessage());
                }
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return $inserted;
    }

    /**
     * Resolve a TSSE user_id to its UNIT3D equivalent.
     * Returns the same value unless that ID was remapped due to a collision.
     */
    private function applyUserRemap(int $srcId): int
    {
        return $this->userIdRemap[$srcId] ?? $srcId;
    }

    /**
     * Insert torrents that don't already exist (keyed by info_hash).
     * Existing UNIT3D torrents are never modified.
     *
     * @param  array<string, array>  $batch  info_hash → row data
     * @return int  number of rows actually inserted
     */
    private function insertNewTorrents(array $batch): int
    {
        if (empty($batch)) {
            return 0;
        }

        // Find which info_hashes already exist in UNIT3D
        $existing = DB::table('torrents')
            ->whereIn('info_hash', array_keys($batch))
            ->pluck('info_hash')
            ->flip()
            ->all();

        $toInsert = array_values(
            array_filter($batch, fn ($row) => !isset($existing[$row['info_hash']]))
        );

        if (empty($toInsert)) {
            return 0;
        }

        DB::table('torrents')->insert($toInsert);

        return count($toInsert);
    }

    /**
     * Migrate peers from source to destination
     */
    public function migratePeers(array $sourceConfig, int $offset = 0, int $limit = 100): array
    {
        $this->log("Starting peer migration (offset={$offset}, limit={$limit})...");

        try {
            $this->ensureConnected($sourceConfig);

            // Abort early if torrents haven't been migrated yet
            $unit3dTorrentCount = DB::table('torrents')->count();
            if ($unit3dTorrentCount === 0) {
                $this->log('WARNING: No torrents found in UNIT3D — migrate torrents first, then re-run peers.');

                return ['success' => false, 'error' => 'No torrents in UNIT3D. Run torrent migration first.', 'done' => true, 'logs' => $this->migrationLog];
            }

            $peers   = $this->sourceQuery("SELECT * FROM peers ORDER BY userid LIMIT {$limit} OFFSET {$offset}");
            $fetched = count($peers);

            $count        = 0;
            $skipped      = 0;
            $batch        = [];
            $batchSize    = 500;

            // Build a hex info_hash → torrent_id map for this page in one query
            $rawHashes = [];
            foreach ($peers as $peer) {
                $raw = $peer['infohash'] ?? $peer['info_hash'] ?? $peer['torrent_hash'] ?? null;
                if ($raw !== null) {
                    $hex = (strlen($raw) === 40 && ctype_xdigit($raw))
                        ? strtolower($raw)
                        : strtolower(bin2hex($raw));
                    $rawHashes[$hex] = true;
                }
            }

            $torrentMap = DB::table('torrents')
                ->whereIn('info_hash', array_keys($rawHashes))
                ->pluck('id', 'info_hash')
                ->all(); // hash → id

            foreach ($peers as $peer) {
                try {
                    // TSSE peers.infohash is BINARY(20) — convert to hex for lookup
                    $rawHash = $peer['infohash'] ?? $peer['info_hash'] ?? $peer['torrent_hash'] ?? null;
                    if ($rawHash === null) {
                        $skipped++;
                        continue;
                    }

                    $infoHash = (strlen($rawHash) === 40 && ctype_xdigit($rawHash))
                        ? strtolower($rawHash)
                        : strtolower(bin2hex($rawHash));

                    $torrentId = $torrentMap[$infoHash] ?? null;
                    if ($torrentId === null) {
                        $skipped++;
                        continue; // torrent not migrated — skip peer
                    }

                    $srcUserId = (int) ($peer['userid'] ?? $peer['user_id'] ?? 0);

                    $batch[] = [
                        'peer_id'    => $peer['peer_id'] ?? $peer['peerid'] ?? null,
                        'hash'       => $infoHash,
                        'ip'         => $peer['ip'] ?? null,
                        'port'       => (int) ($peer['port'] ?? 0),
                        'left'       => (int) ($peer['left'] ?? 0),
                        'uploaded'   => (int) ($peer['uploaded'] ?? 0),
                        'downloaded' => (int) ($peer['downloaded'] ?? 0),
                        'seeder'     => ($peer['left'] ?? 1) == 0 ? 1 : 0,
                        'torrent_id' => $torrentId,
                        'user_id'    => $this->applyUserRemap($srcUserId) ?: null,
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
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

            $this->log("Peer migration completed: {$count} peers migrated, {$skipped} skipped (torrent not found)");

            return ['success' => true, 'count' => $count, 'done' => $fetched < $limit, 'logs' => $this->migrationLog];
        } catch (\Throwable $e) {
            $this->log('Peer migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'done' => true, 'logs' => $this->migrationLog];
        }
    }

    /**
     * Migrate snatched from source to destination
     */
    public function migrateSnatched(array $sourceConfig, int $offset = 0, int $limit = 100): array
    {
        $this->log("Starting snatched migration (offset={$offset}, limit={$limit})...");

        try {
            $this->ensureConnected($sourceConfig);

            $snatched = $this->sourceQuery(
                "SELECT userid, infohash, uploaded, downloaded, snatched_time, completedtime
                 FROM snatched
                 ORDER BY userid
                 LIMIT {$limit} OFFSET {$offset}"
            );
            $fetched  = count($snatched);

            $count = 0;
            $batch = [];
            $batchSize = 500;

            foreach ($snatched as $snatch) {
                try {
                    // TSSE snatched.infohash is BINARY(20) — convert to hex
                    $rawHash = $snatch['infohash'] ?? $snatch['info_hash'] ?? null;
                    if ($rawHash === null) {
                        continue;
                    }

                    $infoHash = (strlen($rawHash) === 40 && ctype_xdigit($rawHash))
                        ? strtolower($rawHash)
                        : strtolower(bin2hex($rawHash));

                    $srcUserId    = (int) ($snatch['userid'] ?? $snatch['user_id'] ?? 0);
                    $uploaded     = (int) ($snatch['uploaded'] ?? 0);
                    $downloaded   = (int) ($snatch['downloaded'] ?? 0);
                    $completedRaw = $snatch['snatched_time'] ?? $snatch['completedtime'] ?? null;
                    $completedAt  = ($completedRaw && $completedRaw !== '0000-00-00 00:00:00')
                        ? date('Y-m-d H:i:s', is_numeric($completedRaw) ? (int) $completedRaw : strtotime($completedRaw))
                        : null;

                    // UNIT3D tracks snatch history in the `history` table
                    $batch[] = [
                        'user_id'           => $this->applyUserRemap($srcUserId),
                        'info_hash'         => $infoHash,
                        'uploaded'          => $uploaded,
                        'actual_uploaded'   => $uploaded,
                        'client_uploaded'   => $uploaded,
                        'downloaded'        => $downloaded,
                        'actual_downloaded' => $downloaded,
                        'client_downloaded' => $downloaded,
                        'seeder'            => 0,
                        'active'            => 0,
                        'seedtime'          => 0,
                        'completed_at'      => $completedAt,
                        'created_at'        => now()->toDateTimeString(),
                        'updated_at'        => now()->toDateTimeString(),
                    ];

                    if (count($batch) >= $batchSize) {
                        DB::table('history')->insert($batch);
                        $count += count($batch);
                        $batch = [];
                    }
                } catch (\Throwable $e) {
                    $this->log('Error migrating snatched record: ' . $e->getMessage());
                }
            }

            if (!empty($batch)) {
                DB::table('history')->insert($batch);
                $count += count($batch);
            }

            $this->log("Snatched migration completed: {$count} records migrated to history");

            return ['success' => true, 'count' => $count, 'done' => $fetched < $limit, 'logs' => $this->migrationLog];
        } catch (\Throwable $e) {
            $this->log('Snatched migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'done' => true, 'logs' => $this->migrationLog];
        }
    }

    /**
     * Migrate torrent comments from source to UNIT3D's polymorphic comments table.
     */
    public function migrateComments(array $sourceConfig, int $offset = 0, int $limit = 100): array
    {
        $this->log("Starting comments migration (offset={$offset}, limit={$limit})...");

        try {
            $this->ensureConnected($sourceConfig);

            // TSSE comments reference torrents by their internal ID.
            // We join with TSSE torrents to get the info_hash, then resolve to the UNIT3D torrent_id.
            $rows = $this->sourceQuery(
                "SELECT c.id, c.text, c.userid, c.added, c.anonymous,
                        t.info_hash
                 FROM comments c
                 LEFT JOIN torrents t ON t.id = c.torrent
                 LIMIT {$limit} OFFSET {$offset}"
            );
            $fetched = count($rows);

            // Build hex hash → UNIT3D torrent_id lookup for this page in one query
            $rawHashes = [];
            foreach ($rows as $row) {
                $raw = $row['info_hash'] ?? null;
                if ($raw !== null) {
                    $hex = (strlen($raw) === 40 && ctype_xdigit($raw))
                        ? strtolower($raw)
                        : strtolower(bin2hex($raw));
                    $rawHashes[$hex] = true;
                }
            }

            $torrentMap = DB::table('torrents')
                ->whereIn('info_hash', array_keys($rawHashes))
                ->pluck('id', 'info_hash')
                ->all();

            $count   = 0;
            $skipped = 0;
            $batch   = [];

            foreach ($rows as $row) {
                $raw = $row['info_hash'] ?? null;
                if ($raw === null) {
                    $skipped++;
                    continue;
                }

                $infoHash  = (strlen($raw) === 40 && ctype_xdigit($raw)) ? strtolower($raw) : strtolower(bin2hex($raw));
                $torrentId = $torrentMap[$infoHash] ?? null;
                if ($torrentId === null) {
                    $skipped++;
                    continue; // torrent not migrated
                }

                $addedRaw = $row['added'] ?? null;
                $addedAt  = ($addedRaw && $addedRaw !== '0000-00-00 00:00:00')
                    ? date('Y-m-d H:i:s', is_numeric($addedRaw) ? (int) $addedRaw : strtotime($addedRaw))
                    : now()->toDateTimeString();

                $batch[] = [
                    'content'          => $row['text'] ?? '',
                    'anon'             => ($row['anonymous'] ?? 0) ? 1 : 0,
                    'commentable_id'   => $torrentId,
                    'commentable_type' => 'App\Models\Torrent',
                    'user_id'          => $this->applyUserRemap((int) ($row['userid'] ?? 0)) ?: null,
                    'created_at'       => $addedAt,
                    'updated_at'       => $addedAt,
                ];
            }

            if (!empty($batch)) {
                DB::table('comments')->insert($batch);
                $count = count($batch);
            }

            $this->log("Comments migration completed: {$count} inserted, {$skipped} skipped (torrent not found)");

            return ['success' => true, 'count' => $count, 'done' => $fetched < $limit, 'logs' => $this->migrationLog];
        } catch (\Throwable $e) {
            $this->log('Comments migration failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'done' => true, 'logs' => $this->migrationLog];
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
