<?php

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.conversation.{conversation}', function (User $user, string $conversation): bool {
    $chatConversation = ChatConversation::query()->find($conversation);

    return $chatConversation?->isAccessibleBy($user) ?? false;
});
