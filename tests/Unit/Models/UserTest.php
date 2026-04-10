<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserSetting;

describe('user model', function (): void {
    it('returns default settings when no settings row exists', function (): void {
        $user = User::factory()->create()->fresh();

        cache()->forget('user-settings:by-user-id:'.$user->id);

        expect($user->settings)
            ->toBeInstanceOf(UserSetting::class)
            ->and($user->settings->locale)->toBe(config('app.locale'));
    });

    it('repairs poisoned settings cache entries', function (): void {
        $user = User::factory()->create();

        cache()->forever('user-settings:by-user-id:'.$user->id, 'not found');

        $settings = User::query()->findOrFail($user->id)->settings;

        expect($settings)
            ->toBeInstanceOf(UserSetting::class)
            ->and($settings->locale)->toBe(config('app.locale'))
            ->and(cache()->get('user-settings:by-user-id:'.$user->id))->toBeInstanceOf(UserSetting::class);
    });
});