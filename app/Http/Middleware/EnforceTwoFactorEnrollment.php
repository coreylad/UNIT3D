<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTwoFactorEnrollment
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! config('fortify.force_2fa')) {
            return $next($request);
        }

        $user = auth()->user();

        if (! empty($user->two_factor_secret) && $user->two_factor_confirmed_at !== null) {
            return $next($request);
        }

        if (
            $request->routeIs('users.two_factor_auth.*')
            || $request->routeIs('logout')
            || $request->routeIs('two-factor.*')
            || $request->is('livewire/*')
        ) {
            return $next($request);
        }

        return redirect()
            ->route('users.two_factor_auth.edit', ['user' => $user])
            ->with('warning', 'Two-factor authentication is required. Please enable and confirm 2FA to continue.');
    }
}
