<?php

declare(strict_types=1);

/**
 * NOTICE OF LICENSE.
 *
 * BAS3D is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    BAS3D
 *
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

namespace App\Http\Controllers\API;

use App\Enums\ModerationStatus;
use App\Helpers\StringHelper;
use App\Models\Conversation;
use App\Models\Donation;
use App\Models\DonationPackage;
use App\Models\PrivateMessage;
use App\Models\User;
use App\Services\Unit3dAnnounce;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles automatic donation upgrades triggered by external payment processors.
 *
 * An external site or payment gateway posts to /api/donation/webhook with a
 * shared secret, the donor's user ID (or username/email), the chosen package
 * ID, and the payment transaction reference. The endpoint verifies the secret,
 * records the donation and immediately applies the package rewards so no
 * manual staff approval is required.
 */
class DonationWebhookController extends BaseController
{
    /**
     * Process an automatic donation from an external payment processor.
     *
     * Required POST fields:
     *   - secret      : shared secret matching DONATION_WEBHOOK_SECRET
     *   - user_id     : the site user's numeric ID (or use username / email)
     *   - package_id  : the ID of the DonationPackage being purchased
     *   - transaction : unique payment reference (tx hash, receipt number, etc.)
     *
     * Optional POST fields:
     *   - username    : looked up when user_id is absent
     *   - email       : looked up when user_id and username are absent
     */
    public function store(Request $request): JsonResponse
    {
        // --- Verify webhook secret ---
        $webhookSecret = config('donation.webhook_secret');

        if (empty($webhookSecret)) {
            return $this->sendError('Donation webhook is not configured.', [], 503);
        }

        if (!hash_equals((string) $webhookSecret, (string) $request->input('secret', ''))) {
            return $this->sendError('Unauthorized.', [], 401);
        }

        // --- Validate required fields ---
        $validated = $request->validate([
            'user_id'     => 'nullable|integer|exists:users,id',
            'username'    => 'nullable|string|exists:users,username',
            'email'       => 'nullable|email|exists:users,email',
            'package_id'  => 'required|integer|exists:donation_packages,id',
            'transaction' => 'required|string|max:500',
        ]);

        // --- Prevent duplicate transaction IDs (transaction field is encrypted, so check in PHP) ---
        $transaction = $validated['transaction'];
        $isDuplicate = Donation::lazy()->contains(fn (Donation $d): bool => $d->transaction === $transaction);

        if ($isDuplicate) {
            return $this->sendError('This transaction has already been processed.', [], 409);
        }

        // --- Resolve user ---
        $user = null;

        if (!empty($validated['user_id'])) {
            $user = User::find($validated['user_id']);
        } elseif (!empty($validated['username'])) {
            $user = User::where('username', $validated['username'])->first();
        } elseif (!empty($validated['email'])) {
            $user = User::where('email', $validated['email'])->first();
        }

        if ($user === null) {
            return $this->sendError('User not found. Provide user_id, username, or email.', [], 422);
        }

        // --- Load package ---
        $package = DonationPackage::where('is_active', true)->find($validated['package_id']);

        if ($package === null) {
            return $this->sendError('Donation package not found or inactive.', [], 422);
        }

        $now = now();

        // --- Record and immediately approve donation ---
        $donation = Donation::create([
            'status'      => ModerationStatus::APPROVED,
            'package_id'  => $package->id,
            'user_id'     => $user->id,
            'transaction' => $transaction,
            'starts_at'   => $now,
            'ends_at'     => $package->donor_value > 0 ? $now->copy()->addDays($package->donor_value) : null,
        ]);

        // --- Apply package rewards ---
        $user->invites    += $package->invite_value ?? 0;
        $user->uploaded   += $package->upload_value ?? 0;
        $user->is_donor    = true;
        $user->is_lifetime = $package->donor_value === null;
        $user->seedbonus  += $package->bonus_value ?? 0;
        $user->save();

        // --- Notify user via private message ---
        $currency = config('donation.currency', 'USD');
        $conversation = Conversation::create([
            'subject' => 'Your donation of '.$package->cost.' '.$currency.' has been automatically processed.',
        ]);
        $conversation->users()->sync([$user->id => ['read' => false]]);

        $validThrough = $donation->ends_at !== null
            ? $donation->ends_at->toDateString().' (YYYY-MM-DD)'
            : 'Lifetime';

        PrivateMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'message'         => '[b]Thank you for supporting '.config('app.name').'[/b]'."\n"
                .'Your donation has been automatically approved and is valid through: '.$validThrough."\n"
                .'A total of '.number_format($package->bonus_value ?? 0).' BON points, '.StringHelper::formatBytes($package->upload_value ?? 0).' upload and '.($package->invite_value ?? 0).' invites have been credited to your account.',
        ]);

        // --- Sync with announce server ---
        cache()->forget('user:'.$user->passkey);
        Unit3dAnnounce::addUser($user);

        return $this->sendResponse([
            'donation_id' => $donation->id,
            'user_id'     => $user->id,
            'package'     => $package->name,
            'valid_until' => $donation->ends_at?->toDateString() ?? 'Lifetime',
        ], 'Donation processed and user upgraded successfully.');
    }
}
