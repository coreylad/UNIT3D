"""
Make all large source queries chunked so they never load the entire table into memory.
Add getLogs() return value to every migrate*() result.
"""
import re

FILE = r"d:\UNIT3D\app\Services\DatabaseMigrationService.php"

with open(FILE, encoding="utf-8") as f:
    src = f.read()

# ──────────────────────────────────────────────────────────────────────────────
# Add a chunkSourceQuery() helper right after sourceColumn()
# ──────────────────────────────────────────────────────────────────────────────
CHUNK_HELPER = '''
    /**
     * Iterate over a source table in chunks, yielding each row.
     * Uses LIMIT/OFFSET so we never hold the whole table in RAM.
     *
     * @param  int  $chunkSize   rows per page (default 500)
     * @return \\Generator<array>
     */
    private function chunkSourceQuery(string $sql, array $params = [], int $chunkSize = 500): \\Generator
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
                break;
            }

            $offset += $chunkSize;
        }
    }

'''

# Insert before getMigrationSummary (the first public method after the helpers)
ANCHOR = '    // ─'
# Insert after sourceColumn closing brace
src = src.replace(
    "    // â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€\n    // Public API\n    // â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€",
    CHUNK_HELPER +
    "    // â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€\n    // Public API\n    // â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€"
)

# ──────────────────────────────────────────────────────────────────────────────
# migrateUsers: use chunkSourceQuery instead of sourceQuery
# ──────────────────────────────────────────────────────────────────────────────
src = src.replace(
    "            $users = $this->sourceQuery('SELECT * FROM users');",
    "            // Use chunked iteration — avoids loading all users into RAM at once\n"
    "            $usersIterator = $this->chunkSourceQuery('SELECT * FROM users ORDER BY id', [], 200);"
)

src = src.replace(
    "            foreach ($users as $user) {",
    "            foreach ($usersIterator as $user) {"
)

# ──────────────────────────────────────────────────────────────────────────────
# migrateUsers: return logs
# ──────────────────────────────────────────────────────────────────────────────
src = src.replace(
    "            $this->log(\"User migration completed: {$count} users migrated\");\n\n"
    "            return ['success' => true, 'count' => $count];\n"
    "        } catch (\\Throwable $e) {\n"
    "            $this->log('User migration failed: ' . $e->getMessage());\n\n"
    "            return ['success' => false, 'error' => $e->getMessage()];\n"
    "        }\n"
    "    }\n"
    "\n"
    "    /**\n"
    "     * Migrate torrents from source to destination\n"
    "     */",
    "            $this->log(\"User migration completed: {$count} users migrated\");\n\n"
    "            return ['success' => true, 'count' => $count, 'logs' => $this->migrationLog];\n"
    "        } catch (\\Throwable $e) {\n"
    "            $this->log('User migration failed: ' . $e->getMessage());\n\n"
    "            return ['success' => false, 'error' => $e->getMessage(), 'logs' => $this->migrationLog];\n"
    "        }\n"
    "    }\n"
    "\n"
    "    /**\n"
    "     * Migrate torrents from source to destination\n"
    "     */"
)

# ──────────────────────────────────────────────────────────────────────────────
# migrateTorrents: use chunkSourceQuery + return logs
# ──────────────────────────────────────────────────────────────────────────────
src = src.replace(
    "            $torrents = $this->sourceQuery('SELECT * FROM torrents');",
    "            $torrentsIterator = $this->chunkSourceQuery('SELECT * FROM torrents ORDER BY id', [], 200);"
)
src = src.replace(
    "            foreach ($torrents as $torrent) {",
    "            foreach ($torrentsIterator as $torrent) {"
)
src = src.replace(
    "            $this->log(\"Torrent migration completed: {$count} torrents migrated\");\n\n"
    "            return ['success' => true, 'count' => $count];\n"
    "        } catch (\\Throwable $e) {\n"
    "            $this->log('Torrent migration failed: ' . $e->getMessage());\n\n"
    "            return ['success' => false, 'error' => $e->getMessage()];\n"
    "        }",
    "            $this->log(\"Torrent migration completed: {$count} torrents migrated\");\n\n"
    "            return ['success' => true, 'count' => $count, 'logs' => $this->migrationLog];\n"
    "        } catch (\\Throwable $e) {\n"
    "            $this->log('Torrent migration failed: ' . $e->getMessage());\n\n"
    "            return ['success' => false, 'error' => $e->getMessage(), 'logs' => $this->migrationLog];\n"
    "        }"
)

# ──────────────────────────────────────────────────────────────────────────────
# migratePeers: chunked + return logs
# ──────────────────────────────────────────────────────────────────────────────
src = src.replace(
    "            $peers = $this->sourceQuery('SELECT * FROM peers');",
    "            $peersIterator = $this->chunkSourceQuery('SELECT * FROM peers ORDER BY userid, infohash', [], 500);"
)
src = src.replace(
    "            foreach ($peers as $peer) {",
    "            foreach ($peersIterator as $peer) {"
)
src = src.replace(
    "            $this->log(\"Peer migration completed: {$count} peers migrated\");\n\n"
    "            return ['success' => true, 'count' => $count];\n"
    "        } catch (\\Throwable $e) {\n"
    "            $this->log('Peer migration failed: ' . $e->getMessage());\n\n"
    "            return ['success' => false, 'error' => $e->getMessage()];\n"
    "        }",
    "            $this->log(\"Peer migration completed: {$count} peers migrated\");\n\n"
    "            return ['success' => true, 'count' => $count, 'logs' => $this->migrationLog];\n"
    "        } catch (\\Throwable $e) {\n"
    "            $this->log('Peer migration failed: ' . $e->getMessage());\n\n"
    "            return ['success' => false, 'error' => $e->getMessage(), 'logs' => $this->migrationLog];\n"
    "        }"
)

# ──────────────────────────────────────────────────────────────────────────────
# migrateSnatched: chunked + return logs
# ──────────────────────────────────────────────────────────────────────────────
src = src.replace(
    "            $snatched = $this->sourceQuery('SELECT * FROM snatched');",
    "            $snatchedIterator = $this->chunkSourceQuery('SELECT * FROM snatched ORDER BY userid', [], 500);"
)
src = src.replace(
    "            foreach ($snatched as $snatch) {",
    "            foreach ($snatchedIterator as $snatch) {"
)
src = src.replace(
    "            $this->log(\"Snatched migration completed: {$count} records migrated\");\n\n"
    "            return ['success' => true, 'count' => $count];\n"
    "        } catch (\\Throwable $e) {\n"
    "            $this->log('Snatched migration failed: ' . $e->getMessage());\n\n"
    "            return ['success' => false, 'error' => $e->getMessage()];\n"
    "        }",
    "            $this->log(\"Snatched migration completed: {$count} records migrated\");\n\n"
    "            return ['success' => true, 'count' => $count, 'logs' => $this->migrationLog];\n"
    "        } catch (\\Throwable $e) {\n"
    "            $this->log('Snatched migration failed: ' . $e->getMessage());\n\n"
    "            return ['success' => false, 'error' => $e->getMessage(), 'logs' => $this->migrationLog];\n"
    "        }"
)

# ──────────────────────────────────────────────────────────────────────────────
# migrateForums return logs
# ──────────────────────────────────────────────────────────────────────────────
src = src.replace(
    "            $this->log(\"Forum migration completed: {$count} forums migrated\");\n\n"
    "            return ['success' => true, 'count' => $count];\n"
    "        } catch (\\Throwable $e) {\n"
    "            $this->log('Forum migration failed: ' . $e->getMessage());\n\n"
    "            return ['success' => false, 'error' => $e->getMessage()];\n"
    "        }",
    "            $this->log(\"Forum migration completed: {$count} forums migrated\");\n\n"
    "            return ['success' => true, 'count' => $count, 'logs' => $this->migrationLog];\n"
    "        } catch (\\Throwable $e) {\n"
    "            $this->log('Forum migration failed: ' . $e->getMessage());\n\n"
    "            return ['success' => false, 'error' => $e->getMessage(), 'logs' => $this->migrationLog];\n"
    "        }"
)

# ──────────────────────────────────────────────────────────────────────────────
# migrateForumThreads: chunked + return logs
# ──────────────────────────────────────────────────────────────────────────────
src = src.replace(
    "            $threads = $this->sourceQuery(\"SELECT * FROM `{$threadsTable}`\");",
    "            $threads = $this->chunkSourceQuery(\"SELECT * FROM `{$threadsTable}` ORDER BY id\", [], 500);"
)
src = src.replace(
    "            $this->log(\"Forum threads migration completed: {$count} threads migrated\");\n\n"
    "            return ['success' => true, 'count' => $count];\n"
    "        } catch (\\Throwable $e) {\n"
    "            $this->log('Forum threads migration failed: ' . $e->getMessage());\n\n"
    "            return ['success' => false, 'error' => $e->getMessage()];\n"
    "        }",
    "            $this->log(\"Forum threads migration completed: {$count} threads migrated\");\n\n"
    "            return ['success' => true, 'count' => $count, 'logs' => $this->migrationLog];\n"
    "        } catch (\\Throwable $e) {\n"
    "            $this->log('Forum threads migration failed: ' . $e->getMessage());\n\n"
    "            return ['success' => false, 'error' => $e->getMessage(), 'logs' => $this->migrationLog];\n"
    "        }"
)

# ──────────────────────────────────────────────────────────────────────────────
# migrateForumPosts: chunked + return logs
# ──────────────────────────────────────────────────────────────────────────────
src = src.replace(
    "            $posts = $this->sourceQuery(\"SELECT * FROM `{$postsTable}`\");",
    "            $posts = $this->chunkSourceQuery(\"SELECT * FROM `{$postsTable}` ORDER BY id\", [], 500);"
)
src = src.replace(
    "            $this->log(\"Forum posts migration completed: {$count} posts migrated\");\n\n"
    "            return ['success' => true, 'count' => $count];\n"
    "        } catch (\\Throwable $e) {\n"
    "            $this->log('Forum posts migration failed: ' . $e->getMessage());\n\n"
    "            return ['success' => false, 'error' => $e->getMessage()];\n"
    "        }",
    "            $this->log(\"Forum posts migration completed: {$count} posts migrated\");\n\n"
    "            return ['success' => true, 'count' => $count, 'logs' => $this->migrationLog];\n"
    "        } catch (\\Throwable $e) {\n"
    "            $this->log('Forum posts migration failed: ' . $e->getMessage());\n\n"
    "            return ['success' => false, 'error' => $e->getMessage(), 'logs' => $this->migrationLog];\n"
    "        }"
)

# ──────────────────────────────────────────────────────────────────────────────
# Write back
# ──────────────────────────────────────────────────────────────────────────────
with open(FILE, "w", encoding="utf-8", newline="\n") as f:
    f.write(src)

# Verify
checks = [
    ("chunkSourceQuery",     "chunkSourceQuery helper added"),
    ("\\Generator",          "Generator return type"),
    ("$usersIterator",       "users chunked"),
    ("$torrentsIterator",    "torrents chunked"),
    ("$peersIterator",       "peers chunked"),
    ("$snatchedIterator",    "snatched chunked"),
    ("'logs' => $this->migrationLog", "logs in return values"),
]
all_ok = True
for needle, label in checks:
    found = needle in src
    print(f"  {'OK  ' if found else 'MISS'} {label}")
    if not found:
        all_ok = False

print()
print("All checks passed!" if all_ok else "Some checks FAILED.")
