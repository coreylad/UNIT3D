<?php

declare(strict_types=1);

use App\Http\Resources\ChatMessageResource;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

test('chat message resource replaces you bbcode with the viewing username', function (): void {
    $viewer = User::factory()->create([
        'username' => 'test1',
    ]);

    $message = Message::factory()->create([
        'message' => 'hello [you]',
    ]);

    $request = Request::create('/api/chat/messages/1', 'GET');
    $request->setUserResolver(fn (): User => $viewer);

    $resource = new ChatMessageResource($message);

    expect($resource->toArray($request)['message'])->toContain('hello test1');
});

