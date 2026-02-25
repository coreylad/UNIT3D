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

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Unit3dAnnounce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ConfirmPasskeyResetController extends Controller
{
    /**
     * Show form to confirm passkey reset.
     */
    public function show(string $token): \Illuminate\Contracts\View\Factory|\Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $userId = Cache::get('passkey_reset_'.$token);

        if (!$userId) {
            return redirect('/login')->with(['error' => 'Invalid or expired passkey reset link']);
        }

        $user = User::findOrFail($userId);

        return view('auth.confirm-passkey-reset', [
            'user'  => $user,
            'token' => $token,
        ]);
    }

    /**
     * Confirm and reset the passkey.
     */
    public function update(Request $request, string $token): \Illuminate\Http\RedirectResponse
    {
        $userId = Cache::get('passkey_reset_'.$token);

        if (!$userId) {
            return redirect('/login')->with(['error' => 'Invalid or expired passkey reset link']);
        }

        $user = User::findOrFail($userId);

        // Verify the user's email matches for security
        $request->validate([
            'email' => 'required|email|in:'.$user->email,
        ]);

        // Generate new passkey
        $newPasskey = md5(random_bytes(60).$user->password);

        DB::transaction(static function () use ($user, $newPasskey): void {
            // Mark old passkey as deleted
            $user->passkeys()->latest()->first()?->update(['deleted_at' => now()]);

            // Update user with new passkey
            $user->update([
                'passkey' => $newPasskey,
            ]);

            // Create new passkey record
            $user->passkeys()->create(['content' => $user->passkey]);

            // Update announce tracker
            Unit3dAnnounce::addUser($user, $newPasskey);
        }, 5);

        // Clear the reset token
        Cache::forget('passkey_reset_'.$token);

        return redirect('/login')->with(['success' => 'Your passkey has been reset successfully! You can now login with your new passkey.']);
    }
}
