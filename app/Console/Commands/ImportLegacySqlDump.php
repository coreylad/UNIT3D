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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ImportLegacySqlDump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import-legacy
                            {path : Absolute path to legacy SQL dump}
                            {--skip-migrate : Skip running php artisan migrate --force before import}
                            {--fresh : Run php artisan migrate:fresh --force before import}
                            {--truncate : Truncate each destination table before first insert}
                            {--allow-unknown-tables : Skip inserts for tables not in UNIT3D schema}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import legacy SQL data into current UNIT3D schema using a streaming INSERT parser';

    /**
     * Execute the console command.
     */
    final public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("Cannot read SQL dump at: {$path}");

            return self::FAILURE;
        }

        if ((bool) $this->option('fresh')) {
            $this->warn('Running migrate:fresh --force before import ...');
            $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);

            if (0 !== $exitCode) {
                $this->error(trim(Artisan::output()));

                return self::FAILURE;
            }
        } elseif (! (bool) $this->option('skip-migrate')) {
            $this->warn('Running migrate --force before import ...');
            $exitCode = Artisan::call('migrate', ['--force' => true]);

            if (0 !== $exitCode) {
                $this->error(trim(Artisan::output()));

                return self::FAILURE;
            }
        }

        $tableNames = $this->existingTables();

        $allowUnknownTables = (bool) $this->option('allow-unknown-tables');
        $truncate = (bool) $this->option('truncate');

        $insertStatements = 0;
        $skippedStatements = 0;
        $truncatedTables = [];
        $unknownTables = [];

        $handle = fopen($path, 'rb');
        if (false === $handle) {
            $this->error("Failed to open file: {$path}");

            return self::FAILURE;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $statementBuffer = '';

            while (! feof($handle)) {
                $line = fgets($handle);
                if (false === $line) {
                    continue;
                }

                $statementBuffer .= $line;
                if (! str_ends_with(rtrim($line), ';')) {
                    continue;
                }

                $statement = trim($statementBuffer);
                $statementBuffer = '';

                if (! str_starts_with(strtoupper($statement), 'INSERT INTO')) {
                    continue;
                }

                if (! preg_match('/^INSERT INTO\s+`?([a-zA-Z0-9_]+)`?/i', $statement, $matches)) {
                    $skippedStatements++;

                    continue;
                }

                $table = $matches[1];

                if (! isset($tableNames[$table])) {
                    $unknownTables[$table] = true;

                    if (! $allowUnknownTables) {
                        $this->error("Unknown table encountered: {$table}");

                        return self::FAILURE;
                    }

                    $skippedStatements++;

                    continue;
                }

                if ($truncate && ! isset($truncatedTables[$table])) {
                    DB::statement("TRUNCATE TABLE `{$table}`");
                    $truncatedTables[$table] = true;
                }

                DB::unprepared($statement);
                $insertStatements++;

                if (0 === $insertStatements % 100) {
                    $this->line("Imported {$insertStatements} INSERT statements ...");
                }
            }
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            fclose($handle);
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $unknownCount = count($unknownTables);
        $unknownSummary = 0 !== $unknownCount
            ? ' Unknown tables skipped: '.implode(', ', array_slice(array_keys($unknownTables), 0, 20)).(20 < $unknownCount ? ' ...' : '')
            : '';

        $this->info("Import complete. INSERT statements: {$insertStatements}. Skipped statements: {$skippedStatements}.{$unknownSummary}");

        return self::SUCCESS;
    }

    /**
     * @return array<string, true>
     */
    private function existingTables(): array
    {
        $tables = DB::select('SHOW TABLES');
        $tableNames = [];

        foreach ($tables as $table) {
            $values = array_values((array) $table);
            if ([] === $values) {
                continue;
            }

            $tableNames[(string) $values[0]] = true;
        }

        return $tableNames;
    }
}
