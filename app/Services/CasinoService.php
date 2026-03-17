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

namespace App\Services;

use App\Helpers\StringHelper;
use App\Models\Bot;
use App\Models\CasinoWager;
use App\Models\Conversation;
use App\Models\PrivateMessage;
use App\Models\User;
use App\Repositories\ChatRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CasinoService
{
    public function __construct(private readonly ChatRepository $chatRepository)
    {
    }

    public function createWager(User $creator, int $amount, ?string $message = null): CasinoWager
    {
        $this->ensureEnabled();
        $this->ensureAccess($creator);

        return DB::transaction(function () use ($creator, $amount, $message): CasinoWager {
            /** @var User $creator */
            $creator = User::query()->lockForUpdate()->findOrFail($creator->id);

            $this->ensureEligible($creator, $amount);
            $this->guardWagerLimits($creator);

            $creator->decrement('uploaded', $amount);

            $wager = CasinoWager::create([
                'creator_id' => $creator->id,
                'amount'     => $amount,
                'message'    => $message,
                'status'     => CasinoWager::STATUS_OPEN,
            ]);

            if (config('casino.announce_to_chat')) {
                $this->announceToShoutbox(
                    sprintf(
                        '[url=%s]%s[/url] opened a casino wager for %s.',
                        href_profile($creator),
                        $creator->username,
                        StringHelper::formatBytes($amount, 2),
                    )
                );
            }

            return $wager;
        }, 5);
    }

    public function acceptWager(User $challenger, CasinoWager $wager): CasinoWager
    {
        $this->ensureEnabled();
        $this->ensureAccess($challenger);

        return DB::transaction(function () use ($challenger, $wager): CasinoWager {
            /** @var CasinoWager $lockedWager */
            $lockedWager = CasinoWager::query()->lockForUpdate()->findOrFail($wager->id);

            if (! $lockedWager->isOpen()) {
                throw ValidationException::withMessages([
                    'wager' => 'That wager is no longer open.',
                ]);
            }

            if ($lockedWager->creator_id === $challenger->id) {
                throw ValidationException::withMessages([
                    'wager' => 'You cannot accept your own wager.',
                ]);
            }

            /** @var User $creator */
            $creator = User::query()->lockForUpdate()->findOrFail($lockedWager->creator_id);
            /** @var User $challenger */
            $challenger = User::query()->lockForUpdate()->findOrFail($challenger->id);

            $this->ensureEligible($challenger, $lockedWager->amount);

            $challenger->decrement('uploaded', $lockedWager->amount);

            $winner = random_int(0, 1) === 1 ? $creator : $challenger;
            $loser = $winner->is($creator) ? $challenger : $creator;
            $pot = $lockedWager->amount * 2;

            $winner->increment('uploaded', $pot);

            $lockedWager->update([
                'challenger_id' => $challenger->id,
                'winner_id'     => $winner->id,
                'loser_id'      => $loser->id,
                'status'        => CasinoWager::STATUS_SETTLED,
                'accepted_at'   => now(),
                'settled_at'    => now(),
            ]);

            $this->sendSettlementMessages(
                wager: $lockedWager,
                creator: $creator,
                challenger: $challenger,
                winner: $winner,
                loser: $loser,
                pot: $pot,
            );

            if (config('casino.announce_to_chat')) {
                $this->announceToShoutbox(
                    sprintf(
                        '[url=%s]%s[/url] won %s from [url=%s]%s[/url] in the casino.',
                        href_profile($winner),
                        $winner->username,
                        StringHelper::formatBytes($pot, 2),
                        href_profile($loser),
                        $loser->username,
                    )
                );
            }

            return $lockedWager->fresh(['creator.group', 'challenger.group', 'winner.group', 'loser.group']);
        }, 5);
    }

    public function cancelWager(User $actor, CasinoWager $wager): CasinoWager
    {
        $this->ensureEnabled();
        $this->ensureAccess($actor, allowStaffBypass: true);

        return DB::transaction(function () use ($actor, $wager): CasinoWager {
            /** @var CasinoWager $lockedWager */
            $lockedWager = CasinoWager::query()->lockForUpdate()->findOrFail($wager->id);

            if (! $lockedWager->isOpen()) {
                throw ValidationException::withMessages([
                    'wager' => 'That wager is no longer open.',
                ]);
            }

            if (! $actor->group->is_modo && $lockedWager->creator_id !== $actor->id) {
                throw ValidationException::withMessages([
                    'wager' => 'You cannot cancel this wager.',
                ]);
            }

            /** @var User $creator */
            $creator = User::query()->lockForUpdate()->findOrFail($lockedWager->creator_id);

            $creator->increment('uploaded', $lockedWager->amount);

            $lockedWager->update([
                'status'       => CasinoWager::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            return $lockedWager;
        }, 5);
    }

    /**
     * @return array{totalWon:int,totalLost:int,net:int,completed:int,openExposure:int,openCount:int,winRate:float}
     */
    public function statsForUser(User $user): array
    {
        $completed = CasinoWager::query()
            ->settled()
            ->where(fn ($query) => $query
                ->where('creator_id', '=', $user->id)
                ->orWhere('challenger_id', '=', $user->id))
            ->count();

        $totalWon = (int) CasinoWager::query()
            ->settled()
            ->where('winner_id', '=', $user->id)
            ->sum('amount');

        $totalLost = (int) CasinoWager::query()
            ->settled()
            ->where('loser_id', '=', $user->id)
            ->sum('amount');

        $openExposure = (int) CasinoWager::query()
            ->open()
            ->where('creator_id', '=', $user->id)
            ->sum('amount');

        $wins = CasinoWager::query()
            ->settled()
            ->where('winner_id', '=', $user->id)
            ->count();

        return [
            'totalWon'     => $totalWon,
            'totalLost'    => $totalLost,
            'net'          => $totalWon - $totalLost,
            'completed'    => $completed,
            'openExposure' => $openExposure,
            'openCount'    => CasinoWager::query()->open()->where('creator_id', '=', $user->id)->count(),
            'winRate'      => $completed > 0 ? round(($wins / $completed) * 100, 2) : 0.0,
        ];
    }

    private function ensureEnabled(): void
    {
        abort_unless(config('casino.enabled'), 404);
    }

    public function ensureAccess(User $user, bool $allowStaffBypass = false): void
    {
        if ($allowStaffBypass && $user->group->is_modo) {
            return;
        }

        if ($user->group->level < (int) config('casino.minimum_group_level')) {
            throw ValidationException::withMessages([
                'casino' => 'Your group does not have permission to use the casino.',
            ]);
        }

        if ((bool) config('casino.require_can_download') && !($user->can_download ?? $user->group->can_download)) {
            throw ValidationException::withMessages([
                'casino' => 'Your account does not currently have casino permission.',
            ]);
        }
    }

    private function guardWagerLimits(User $user): void
    {
        $userOpenWagers = CasinoWager::query()->open()->where('creator_id', '=', $user->id)->count();
        $totalOpenWagers = CasinoWager::query()->open()->count();

        if ($userOpenWagers >= (int) config('casino.max_open_wagers_per_user')) {
            throw ValidationException::withMessages([
                'amount' => 'You already have the maximum number of open wagers allowed.',
            ]);
        }

        if ($totalOpenWagers >= (int) config('casino.max_open_wagers_total')) {
            throw ValidationException::withMessages([
                'amount' => 'The casino is at capacity right now. Please try again later.',
            ]);
        }
    }

    private function ensureEligible(User $user, int $amount): void
    {
        if ($user->uploaded < $amount) {
            throw ValidationException::withMessages([
                'amount' => 'You do not have enough upload credit for that wager.',
            ]);
        }

        if (! is_infinite($user->ratio) && $user->ratio < (float) config('casino.minimum_ratio')) {
            throw ValidationException::withMessages([
                'amount' => 'Your ratio is below the casino minimum.',
            ]);
        }
    }

    private function sendSettlementMessages(CasinoWager $wager, User $creator, User $challenger, User $winner, User $loser, int $pot): void
    {
        $stake = StringHelper::formatBytes($wager->amount, 2);
        $potFormatted = StringHelper::formatBytes($pot, 2);

        $this->sendSystemConversation(
            recipient: $winner,
            subject: 'Casino wager won',
            message: sprintf(
                "You won %s against %s.\n\nStake: %s\nTotal payout: %s\n\nYou can view open wagers here: %s",
                $stake,
                $winner->is($creator) ? $challenger->username : $creator->username,
                $stake,
                $potFormatted,
                route('casino.index'),
            ),
        );

        $this->sendSystemConversation(
            recipient: $loser,
            subject: 'Casino wager lost',
            message: sprintf(
                "You lost %s against %s.\n\nStake: %s\nWinner payout: %s\n\nYou can view open wagers here: %s",
                $stake,
                $winner->username,
                $stake,
                $potFormatted,
                route('casino.index'),
            ),
        );
    }

    private function sendSystemConversation(User $recipient, string $subject, string $message): void
    {
        $conversation = Conversation::create([
            'subject' => $subject,
        ]);

        $conversation->users()->sync([
            User::SYSTEM_USER_ID => ['read' => true],
            $recipient->id       => ['read' => false],
        ]);

        PrivateMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => User::SYSTEM_USER_ID,
            'message'         => $message,
        ]);
    }

    private function announceToShoutbox(string $message): void
    {
        $botId = $this->resolveCasinoBotId();

        $this->chatRepository->systemMessage($message, $botId);
    }

    private function resolveCasinoBotId(): ?int
    {
        $configuredCommand = (string) config('casino.bot_command', 'casinobot');
        $configuredName = (string) config('casino.bot_name', 'casinoBOT');

        $bot = Bot::query()
            ->where('active', '=', true)
            ->where(fn ($query) => $query
                ->whereRaw('LOWER(command) = ?', [mb_strtolower($configuredCommand)])
                ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($configuredName)]))
            ->first();

        if ($bot !== null) {
            return $bot->id;
        }

        $bot = Bot::query()->updateOrCreate(
            ['command' => $configuredCommand],
            [
                'name'         => $configuredName,
                'position'     => 3,
                'color'        => '#16a34a',
                'icon'         => 'fas fa-dice',
                'emoji'        => '1f3b2',
                'help'         => 'casinoBOT announces wager activity in the shoutbox.',
                'active'       => true,
                'is_protected' => false,
                'is_nerdbot'   => false,
                'is_systembot' => false,
            ]
        );

        return $bot?->id;
    }
}