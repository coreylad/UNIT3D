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

namespace App\Http\Controllers;

use App\Http\Requests\StoreCasinoWagerRequest;
use App\Models\CasinoWager;
use App\Services\CasinoService;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CasinoController extends Controller
{
    public function __construct(private readonly CasinoService $casinoService)
    {
    }

    public function index(Request $request): Factory|View
    {
        abort_unless(config('casino.enabled'), 404);

        try {
            $this->casinoService->ensureAccess($request->user());
        } catch (ValidationException) {
            abort(403);
        }

        return view('casino.index', [
            'allowedAmounts' => config('casino.allowed_amounts'),
            'openWagers'     => CasinoWager::query()
                ->open()
                ->with(['creator.group'])
                ->latest()
                ->paginate(20, ['*'], 'open_wagers'),
            'recentWagers'   => CasinoWager::query()
                ->settled()
                ->with(['winner.group', 'loser.group'])
                ->latest('settled_at')
                ->limit(10)
                ->get(),
            'stats'          => $this->casinoService->statsForUser($request->user()),
            'user'           => $request->user(),
        ]);
    }

    public function store(StoreCasinoWagerRequest $request): RedirectResponse
    {
        $this->casinoService->createWager(
            creator: $request->user(),
            amount: $request->integer('amount'),
            message: $request->string('message')->toString() ?: null,
        );

        return to_route('casino.index')->with('success', 'Wager opened successfully.');
    }

    public function accept(Request $request, CasinoWager $casinoWager): RedirectResponse
    {
        $this->casinoService->acceptWager($request->user(), $casinoWager);

        return to_route('casino.index')->with('success', 'Wager accepted and settled.');
    }

    public function cancel(Request $request, CasinoWager $casinoWager): RedirectResponse
    {
        $this->casinoService->cancelWager($request->user(), $casinoWager);

        return to_route('casino.index')->with('success', 'Wager cancelled and refunded.');
    }
}