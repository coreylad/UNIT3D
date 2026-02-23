<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            // Mail / SMTP
            $table->string('smtp_host')->nullable()->after('header_image');
            $table->unsignedSmallInteger('smtp_port')->default(587)->after('smtp_host');
            $table->string('smtp_encryption')->nullable()->after('smtp_port');
            $table->string('smtp_username')->nullable()->after('smtp_encryption');
            $table->text('smtp_password')->nullable()->after('smtp_username');
            $table->string('smtp_from_address')->nullable()->after('smtp_password');
            $table->string('smtp_from_name')->nullable()->after('smtp_from_address');

            // Registration
            $table->boolean('registration_open')->default(true)->after('smtp_from_name');
            $table->boolean('invite_only')->default(false)->after('registration_open');
            $table->unsignedSmallInteger('default_download_slots')->default(8)->after('invite_only');

            // Tracker
            $table->unsignedInteger('announce_interval')->default(1800)->after('default_download_slots');
            $table->boolean('category_filter_enabled')->default(true)->after('announce_interval');

            // Social
            $table->string('discord_url')->nullable()->after('category_filter_enabled');
            $table->string('twitter_url')->nullable()->after('discord_url');
            $table->string('github_url')->nullable()->after('twitter_url');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username',
                'smtp_password', 'smtp_from_address', 'smtp_from_name',
                'registration_open', 'invite_only', 'default_download_slots',
                'announce_interval', 'category_filter_enabled',
                'discord_url', 'twitter_url', 'github_url',
            ]);
        });
    }
};
