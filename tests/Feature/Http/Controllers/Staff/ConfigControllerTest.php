<?php

declare(strict_types=1);

use App\Models\Group;
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

it('index shows list of config tools', function (): void {
    $response = $this->actingAs($this->adminUser)->get(route('staff.config.index'));
    $response->assertOk();
    $response->assertViewIs('Staff.config.index');
    $response->assertViewHas('tools');
    // page heading should use our translation key
    $response->assertSee(__('staff.config-manager'));
});

it('a non-admin cannot view configuration', function (): void {
    $normal = User::factory()->create();
    $this->actingAs($normal)->get(route('staff.config.index'))->assertForbidden();
});

it('show returns the configuration for a tool', function (): void {
    $response = $this->actingAs($this->adminUser)->get(route('staff.config.show', ['tool' => 'chat']));
    $response->assertOk();
    $response->assertViewIs('Staff.config.show');
    $response->assertViewHas('config');
});

it('show returns 404 for invalid tool', function (): void {
    $this->actingAs($this->adminUser)->get(route('staff.config.show', ['tool' => 'invalid-tool-xyz']))->assertNotFound();
});

it('edit returns the form for a tool', function (): void {
    $response = $this->actingAs($this->adminUser)->get(route('staff.config.edit', ['tool' => 'chat']));
    $response->assertOk();
    $response->assertViewIs('Staff.config.edit');
});
