<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Insert the casinoBOT record if it does not already exist, or update it
     * if it was previously seeded with different values.
     * Safe to re-run — updateOrInsert is idempotent.
     */
    public function up(): void
    {
        DB::table('bots')->updateOrInsert(
            ['command' => 'casinobot'],
            [
                'name'         => 'casinoBOT',
                'position'     => 3,
                'color'        => '#16a34a',
                'icon'         => 'fas fa-dice',
                'emoji'        => '1f3b2',
                'help'         => 'casinoBOT announces wager activity in the shoutbox.',
                'active'       => true,
                'is_protected' => false,
                'is_nerdbot'   => false,
                'is_systembot' => false,
                'updated_at'   => now(),
                'created_at'   => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('bots')->where('command', 'casinobot')->delete();
    }
};
