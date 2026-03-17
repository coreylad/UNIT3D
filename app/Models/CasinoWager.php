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

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use AllowDynamicProperties;

/**
 * App\Models\CasinoWager.
 *
 * @property int                             $id
 * @property int                             $creator_id
 * @property int|null                        $challenger_id
 * @property int|null                        $winner_id
 * @property int|null                        $loser_id
 * @property int                             $amount
 * @property string|null                     $message
 * @property string                          $status
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $settled_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
#[AllowDynamicProperties]
final class CasinoWager extends Model
{
    /** @use HasFactory<\Database\Factories\CasinoWagerFactory> */
    use HasFactory;

    final public const STATUS_OPEN = 'open';
    final public const STATUS_SETTLED = 'settled';
    final public const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * @return array{accepted_at: 'datetime', settled_at: 'datetime', cancelled_at: 'datetime'}
     */
    protected function casts(): array
    {
        return [
            'accepted_at'  => 'datetime',
            'settled_at'   => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function challenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'challenger_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function loser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loser_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '=', self::STATUS_OPEN);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSettled(Builder $query): Builder
    {
        return $query->where('status', '=', self::STATUS_SETTLED);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}