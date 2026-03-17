<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Set Command Center (style 17) as the active theme for all existing users.
     *
     * The config('other.default_style') was changed to 17, so new users will
     * already land on Command Center. This migration brings every existing
     * user_settings record in line with that default.
     */
    public function up(): void
    {
        DB::table('user_settings')->update(['style' => 17]);
    }

    /**
     * There is no meaningful rollback here – we cannot know what style each
     * user was on before this migration ran. Reverting to the old config
     * default (12) is the closest approximation, but this is intentionally a
     * no-op to avoid unexpected theme resets.
     */
    public function down(): void
    {
        // intentional no-op
    }
};
