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

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CasinoWager;
use App\Models\User;
use App\Services\CasinoService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CasinoController extends Controller
{
    public function __construct(private readonly CasinoService $casinoService)
    {
    }

    public function index(Request $request, User $user): Factory|View
    {
        abort_unless($request->user()->is($user) || $request->user()->group->is_modo, 403);
        abort_unless(config('casino.enabled'), 404);

        try {
            $this->casinoService->ensureAccess($user, allowStaffBypass: $request->user()->group->is_modo);
        } catch (ValidationException) {
            abort(403);
        }

        return view('user.casino.index', [
            'casinoWagers' => CasinoWager::query()
                ->where(fn ($query) => $query
                    ->where('creator_id', '=', $user->id)
                    ->orWhere('challenger_id', '=', $user->id))
                ->with(['creator.group', 'challenger.group', 'winner.group', 'loser.group'])
                ->latest()
                ->paginate(25),
            'stats'        => $this->casinoService->statsForUser($user),
            'user'         => $user,
        ]);
    }
}