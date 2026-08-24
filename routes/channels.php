<?php

use App\Models\ChatConversation;
use App\Services\TutorChatService;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.conversation.{conversation}', function (User $user, string $conversation): bool {
    if (! (bool) config('client.branding.tutor_chat_enabled', false)) {
        return false;
    }

    $chatConversation = ChatConversation::query()->find($conversation);

    return $chatConversation
        ? app(TutorChatService::class)->isAccessibleBy($user, $chatConversation)
        : false;
});

Broadcast::channel('chat.presence.{conversation}', function (User $user, string $conversation): array|bool {
    if (! (bool) config('client.branding.tutor_chat_enabled', false)) {
        return false;
    }

    $chatConversation = ChatConversation::query()->find($conversation);
    if (! $chatConversation || ! app(TutorChatService::class)->isAccessibleBy($user, $chatConversation)) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
