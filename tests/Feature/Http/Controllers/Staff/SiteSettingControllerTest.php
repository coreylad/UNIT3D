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

use App\Models\Group;
use App\Models\SiteSetting;
use App\Models\User;

beforeEach(function (): void {
    $this->adminUser = User::factory()->create([
        'group_id' => fn () => Group::factory()->create([
            'is_owner' => true,
            'is_admin' => true,
            'is_modo'  => true,
        ])->id,
    ]);
});

test('edit returns an ok response', function (): void {
    $response = $this->actingAs($this->adminUser)->get(route('staff.site_settings.edit'));
    $response->assertOk();
    $response->assertViewIs('Staff.site-setting.edit');
    $response->assertViewHas('siteSetting');
});

test('update redirects on success', function (): void {
    $response = $this->actingAs($this->adminUser)->patch(route('staff.site_settings.update'), [
        'title'            => 'My Tracker',
        'sub_title'        => 'The best tracker',
        'meta_description' => 'A great private tracker.',
        'login_message'    => 'Welcome! Please log in.',
    ]);

    $response->assertRedirect(route('staff.site_settings.edit'))
        ->assertSessionHas('success');

    $setting = SiteSetting::query()->first();
    expect($setting)->not->toBeNull();
    expect($setting->title)->toBe('My Tracker');
    expect($setting->sub_title)->toBe('The best tracker');
    expect($setting->login_message)->toBe('Welcome! Please log in.');
});

test('update validates required fields', function (): void {
    $response = $this->actingAs($this->adminUser)->patch(route('staff.site_settings.update'), [
        'title'            => '',
        'sub_title'        => '',
        'meta_description' => '',
    ]);

    $response->assertSessionHasErrors(['title', 'sub_title', 'meta_description']);
});
