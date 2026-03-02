<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            // Economy settings (from config/other.php)
            $table->boolean('freeleech')->default(false)->after('nerd_bot');
            $table->string('freeleech_until')->nullable()->after('freeleech');
            $table->boolean('doubleup')->default(false)->after('freeleech_until');
            $table->boolean('refundable')->default(false)->after('doubleup');
            $table->decimal('min_ratio', 5, 2)->default(0.40)->after('refundable');
            $table->bigInteger('bon_max_buffer')->unsigned()->nullable()->after('min_ratio');

            // Invite settings (from config/other.php)
            $table->integer('invite_expire')->default(14)->after('invite_only');
            $table->boolean('invites_restricted')->default(false)->after('invite_expire');
            $table->json('invite_groups')->nullable()->after('invites_restricted');
            $table->integer('max_unused_user_invites')->default(1)->after('invite_groups');
            $table->boolean('application_signups')->default(false)->after('max_unused_user_invites');

            // User defaults (from config/other.php)
            $table->unsignedBigInteger('default_upload')->default(53687091200)->after('default_download_slots');
            $table->unsignedBigInteger('default_download')->default(1073741824)->after('default_upload');
            $table->integer('default_style')->default(12)->after('default_download');
            $table->string('font_awesome', 10)->default('fas')->after('default_style');

            // Page URLs (from config/other.php)
            $table->string('rules_url')->nullable()->after('font_awesome');
            $table->string('faq_url')->nullable()->after('rules_url');
            $table->string('upload_guide_url')->nullable()->after('faq_url');

            // Staff forum (from config/other.php)
            $table->boolean('staff_forum_notify')->default(false)->after('upload_guide_url');
            $table->integer('staff_forum_id')->nullable()->after('staff_forum_notify');

            // Thanks system (from config/other.php)
            $table->boolean('thanks_enabled')->default(true)->after('staff_forum_id');

            // Mail rate limiting (from config/other.php)
            $table->integer('mail_rate_allow')->default(1)->after('thanks_enabled');
            $table->integer('mail_rate_every')->default(5)->after('mail_rate_allow');

            // External chat link (from config/unit3d.php)
            $table->string('chat_link_name', 50)->default('Discord')->after('mail_rate_every');
            $table->string('chat_link_icon', 50)->default('fab fa-discord')->after('chat_link_name');
            $table->string('chat_link_url')->nullable()->after('chat_link_icon');

            // Comment rate limit (from config/unit3d.php)
            $table->integer('comment_rate_limit')->default(3)->after('chat_link_url');

            // Hit & Run settings (from config/hitrun.php)
            $table->boolean('hitrun_enabled')->default(true)->after('comment_rate_limit');
            $table->integer('hitrun_seedtime')->default(604800)->after('hitrun_enabled');
            $table->integer('hitrun_max_warnings')->default(3)->after('hitrun_seedtime');
            $table->integer('hitrun_grace')->default(3)->after('hitrun_max_warnings');
            $table->integer('hitrun_buffer')->default(10)->after('hitrun_grace');
            $table->integer('hitrun_expire')->default(14)->after('hitrun_buffer');
            $table->integer('hitrun_prewarn')->default(1)->after('hitrun_expire');

            // Chat settings (from config/chat.php)
            $table->string('system_chatroom', 100)->default('General')->after('hitrun_prewarn');
            $table->integer('chat_message_limit')->default(100)->after('system_chatroom');

            // Torrent settings (from config/torrent.php)
            $table->boolean('torrent_download_check_page')->default(false)->after('chat_message_limit');
            $table->string('torrent_source', 100)->default('BAS3D')->after('torrent_download_check_page');
            $table->string('torrent_created_by')->default('Edited by BAS3D')->after('torrent_source');
            $table->boolean('torrent_created_by_append')->default(true)->after('torrent_created_by');
            $table->string('torrent_comment')->nullable()->after('torrent_created_by_append');
            $table->boolean('torrent_magnet')->default(false)->after('torrent_comment');

            // Donation settings (from config/donation.php)
            $table->boolean('donation_enabled')->default(true)->after('torrent_magnet');
            $table->integer('donation_monthly_goal')->default(100)->after('donation_enabled');
            $table->string('donation_currency', 10)->default('USD')->after('donation_monthly_goal');
            $table->string('donation_description')->nullable()->after('donation_currency');

            // Graveyard settings (from config/graveyard.php)
            $table->boolean('graveyard_enabled')->default(true)->after('donation_description');
            $table->integer('graveyard_time')->default(2592000)->after('graveyard_enabled');
            $table->integer('graveyard_reward')->default(5)->after('graveyard_time');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'freeleech', 'freeleech_until', 'doubleup', 'refundable', 'min_ratio', 'bon_max_buffer',
                'invite_expire', 'invites_restricted', 'invite_groups', 'max_unused_user_invites', 'application_signups',
                'default_upload', 'default_download', 'default_style', 'font_awesome',
                'rules_url', 'faq_url', 'upload_guide_url',
                'staff_forum_notify', 'staff_forum_id', 'thanks_enabled',
                'mail_rate_allow', 'mail_rate_every',
                'chat_link_name', 'chat_link_icon', 'chat_link_url', 'comment_rate_limit',
                'hitrun_enabled', 'hitrun_seedtime', 'hitrun_max_warnings', 'hitrun_grace',
                'hitrun_buffer', 'hitrun_expire', 'hitrun_prewarn',
                'system_chatroom', 'chat_message_limit',
                'torrent_download_check_page', 'torrent_source', 'torrent_created_by',
                'torrent_created_by_append', 'torrent_comment', 'torrent_magnet',
                'donation_enabled', 'donation_monthly_goal', 'donation_currency', 'donation_description',
                'graveyard_enabled', 'graveyard_time', 'graveyard_reward',
            ]);
        });
    }
};
