<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('torrents', function (Blueprint $table): void {
            if (! Schema::hasColumn('torrents', 'freetorrent')) {
                $table->unsignedTinyInteger('freetorrent')->default(0)->after('free');
            }

            if (! Schema::hasColumn('torrents', 'Snatched')) {
                $table->unsignedInteger('Snatched')->default(0)->after('times_completed');
            }

            if (! Schema::hasColumn('torrents', 'last_action')) {
                $table->dateTime('last_action')->nullable()->after('updated_at');
            }
        });

        DB::unprepared('DROP TRIGGER IF EXISTS trg_torrents_compat_before_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_torrents_compat_before_update');

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_torrents_compat_before_insert
            BEFORE INSERT ON torrents
            FOR EACH ROW
            BEGIN
                IF NEW.Snatched IS NULL THEN
                    SET NEW.Snatched = COALESCE(NEW.times_completed, 0);
                END IF;

                IF NEW.times_completed IS NULL THEN
                    SET NEW.times_completed = COALESCE(NEW.Snatched, 0);
                END IF;

                IF NEW.freetorrent IS NULL THEN
                    SET NEW.freetorrent = CASE
                        WHEN COALESCE(NEW.free, 0) = 0 THEN 0
                        WHEN NEW.free >= 100 THEN 1
                        ELSE 2
                    END;
                ELSE
                    SET NEW.free = CASE
                        WHEN NEW.freetorrent = 0 THEN 0
                        WHEN NEW.freetorrent = 1 THEN 100
                        ELSE 50
                    END;
                END IF;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_torrents_compat_before_update
            BEFORE UPDATE ON torrents
            FOR EACH ROW
            BEGIN
                IF NEW.Snatched <> OLD.Snatched THEN
                    SET NEW.times_completed = NEW.Snatched;
                ELSEIF NEW.times_completed <> OLD.times_completed THEN
                    SET NEW.Snatched = NEW.times_completed;
                END IF;

                IF NEW.freetorrent <> OLD.freetorrent THEN
                    SET NEW.free = CASE
                        WHEN NEW.freetorrent = 0 THEN 0
                        WHEN NEW.freetorrent = 1 THEN 100
                        ELSE 50
                    END;
                ELSEIF NEW.free <> OLD.free THEN
                    SET NEW.freetorrent = CASE
                        WHEN COALESCE(NEW.free, 0) = 0 THEN 0
                        WHEN NEW.free >= 100 THEN 1
                        ELSE 2
                    END;
                END IF;
            END
        SQL);

        DB::unprepared('DROP VIEW IF EXISTS users_main');
        DB::unprepared(<<<'SQL'
            CREATE VIEW users_main AS
            SELECT
                users.id AS ID,
                users.passkey AS torrent_pass,
                users.can_download AS can_leech,
                users.uploaded AS Uploaded,
                users.downloaded AS Downloaded,
                CAST('1' AS CHAR(1)) AS Visible,
                CAST('0.0.0.0' AS CHAR(45)) AS IP,
                CAST(
                    CASE
                        WHEN users.disabled_at IS NULL AND users.deleted_at IS NULL THEN '1'
                        ELSE '0'
                    END AS CHAR(1)
                ) AS Enabled
            FROM users
        SQL);

        Schema::create('xbt_client_whitelist', function (Blueprint $table): void {
            $table->string('peer_id', 20)->primary();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('xbt_files_users', function (Blueprint $table): void {
            $table->unsignedInteger('uid');
            $table->unsignedInteger('fid');
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('uploaded')->default(0);
            $table->unsignedBigInteger('downloaded')->default(0);
            $table->unsignedBigInteger('upspeed')->default(0);
            $table->unsignedBigInteger('downspeed')->default(0);
            $table->unsignedBigInteger('remaining')->default(0);
            $table->unsignedBigInteger('corrupt')->default(0);
            $table->unsignedInteger('timespent')->default(0);
            $table->unsignedInteger('announced')->default(0);
            $table->string('ip', 45)->nullable();
            $table->binary('peer_id', length: 20, fixed: true);
            $table->string('useragent', 255)->nullable();
            $table->unsignedBigInteger('mtime')->nullable();

            $table->primary(['uid', 'fid', 'peer_id']);
            $table->index(['fid', 'active']);
            $table->index('announced');
        });

        Schema::create('xbt_snatched', function (Blueprint $table): void {
            $table->unsignedInteger('uid');
            $table->unsignedInteger('fid');
            $table->unsignedBigInteger('tstamp');
            $table->string('IP', 45)->nullable();

            $table->index(['uid', 'fid']);
            $table->index('tstamp');
        });

        Schema::create('users_freeleeches', function (Blueprint $table): void {
            $table->unsignedInteger('UserID');
            $table->unsignedInteger('TorrentID');
            $table->unsignedBigInteger('Downloaded')->default(0);
            $table->boolean('Expired')->default(false);
            $table->timestamps();

            $table->primary(['UserID', 'TorrentID']);
            $table->index('Expired');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_freeleeches');
        Schema::dropIfExists('xbt_snatched');
        Schema::dropIfExists('xbt_files_users');
        Schema::dropIfExists('xbt_client_whitelist');

        DB::unprepared('DROP VIEW IF EXISTS users_main');

        DB::unprepared('DROP TRIGGER IF EXISTS trg_torrents_compat_before_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_torrents_compat_before_update');

        Schema::table('torrents', function (Blueprint $table): void {
            if (Schema::hasColumn('torrents', 'last_action')) {
                $table->dropColumn('last_action');
            }

            if (Schema::hasColumn('torrents', 'Snatched')) {
                $table->dropColumn('Snatched');
            }

            if (Schema::hasColumn('torrents', 'freetorrent')) {
                $table->dropColumn('freetorrent');
            }
        });
    }
};
