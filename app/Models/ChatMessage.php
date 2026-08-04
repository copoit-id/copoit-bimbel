<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'client_message_id',
        'type',
        'body',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toChatPayload(): array
    {
        $this->loadMissing('sender:id,name');

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->sender?->name,
            'type' => $this->type,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
