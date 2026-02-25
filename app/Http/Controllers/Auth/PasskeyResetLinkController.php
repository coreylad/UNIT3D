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
use App\Notifications\PasskeyResetRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PasskeyResetLinkController extends Controller
{
    /**
     * Show form to submit to receive passkey reset link.
     */
    public function create(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('auth.reset-passkey');
    }

    /**
     * Send a new passkey reset link.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        // Generate a reset token valid for 1 hour
        $token = Str::random(64);
        Cache::put('passkey_reset_'.$token, $user->id, now()->addHour());

        // Send reset notification
        $user->notify(new PasskeyResetRequest($token));

        // Return successful status
        return back()->with(['status' => __('auth.passkey-reset-sent')]);
    }
}
