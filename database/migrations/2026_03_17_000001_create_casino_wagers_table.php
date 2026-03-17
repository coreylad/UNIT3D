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
 * @author     GitHub Copilot
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('casino_wagers', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('creator_id');
            $table->unsignedInteger('challenger_id')->nullable();
            $table->unsignedInteger('winner_id')->nullable();
            $table->unsignedInteger('loser_id')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('status', 32)->default('open');
            $table->string('message', 255)->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['creator_id', 'status']);
            $table->index(['challenger_id', 'status']);
            $table->index(['winner_id', 'status']);
            $table->index(['loser_id', 'status']);

            $table->foreign('creator_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('challenger_id')->references('id')->on('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreign('winner_id')->references('id')->on('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreign('loser_id')->references('id')->on('users')->cascadeOnUpdate()->nullOnDelete();
        });
    }
};