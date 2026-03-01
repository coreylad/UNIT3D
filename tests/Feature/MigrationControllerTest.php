<?php

declare(strict_types=1);

use App\Services\DatabaseMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// The migration dashboard is owner-only; we can bypass auth by actingAs a user later if needed.

it('returns a default error string when migration service replies without one', function () {
    // create a fake service that returns failure with no `error` key
    $service = Mockery::mock(DatabaseMigrationService::class);
    $service->shouldReceive('migrateForumThreads')
        ->once()
        ->andReturn(['success' => false]);

    // other migration methods should not be called; allow them if invoked unexpectedly
    $service->shouldIgnoreMissing();

    app()->instance(DatabaseMigrationService::class, $service);

    // perform request; supply minimal required connection data
    $response = $this->postJson(route('staff.migrations.start'), [
        'host'     => 'example.com',
        'port'     => 3306,
        'username' => 'user',
        'password' => 'pass',
        'database' => 'db',
        'tables'   => ['forum_threads'],
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'forum_threads' => ['success', 'error', 'logs'],
        ],
    ]);

    $result = $response->json('data.forum_threads');
    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('(no message)');
});
