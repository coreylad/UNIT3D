<?php

declare(strict_types=1);

use App\Http\Resources\ChatMessageResource;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

test('chat message resource replaces viewer and poster bbcode usernames', function (): void {
    $viewer = User::factory()->create([
        'username' => 'test1',
    ]);
    $poster = User::factory()->create([
        'username' => 'poster1',
    ]);

    $message = Message::factory()->create([
        'user_id' => $poster->id,
        'message' => 'hello [you], I am [me]',
    ]);

    $request = Request::create('/api/chat/messages/1', 'GET');
    $request->setUserResolver(fn (): User => $viewer);

    $resource = new ChatMessageResource($message);

    expect($resource->toArray($request)['message'])->toContain('hello test1, I am poster1');
});
