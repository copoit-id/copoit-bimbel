<?php

namespace App\Services;

use App\Events\ChatMessageCreated;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TutorChatService
{
    /**
     * @return array{0: ChatConversation, 1: bool}
     */
    public function openForStudent(User $student, ClassModel $class): array
    {
        abort_if($student->isTutor(), 403, 'Akun tutor tidak dapat membuka chat sebagai siswa.');
        abort_unless($class->canUserAccess($student->id), 403, 'Anda tidak memiliki akses ke kelas ini.');

        $class->loadMissing('tentor:id,user_id,is_active');
        $tentor = $class->tentor;

        abort_unless($tentor?->is_active && $tentor->user_id, 422, 'Tutor aktif belum tersedia untuk kelas ini.');

        $attributes = [
            'class_id' => $class->class_id,
            'student_user_id' => $student->id,
            'tutor_user_id' => $tentor->user_id,
            'tentor_id' => $tentor->id,
        ];

        try {
            $conversation = ChatConversation::query()->firstOrCreate(
                $attributes,
                ['id' => (string) Str::ulid()]
            );
        } catch (UniqueConstraintViolationException) {
            // The unique context index is the final guard for simultaneous requests
            // from two devices. Fetch the winning row after the losing insert fails.
            $conversation = ChatConversation::query()->where($attributes)->firstOrFail();
        }

        $this->ensureReadState($conversation, $student->id);
        $this->ensureReadState($conversation, $tentor->user_id);

        return [$conversation, $conversation->wasRecentlyCreated];
    }

    public function ensureAccessible(User $user, ChatConversation $conversation): void
    {
        abort_unless($conversation->isAccessibleBy($user), 403, 'Anda tidak memiliki akses ke percakapan ini.');
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function messages(ChatConversation $conversation, ?int $beforeId = null, int $limit = 50): Collection
    {
        $limit = min(max($limit, 1), 100);

        return $conversation->messages()
            ->select(['id', 'conversation_id', 'sender_id', 'client_message_id', 'type', 'body', 'created_at'])
            ->with('sender:id,name')
            ->when($beforeId, fn ($query, int $id) => $query->where('id', '<', $id))
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public function conversationsFor(User $user): LengthAwarePaginator
    {
        return ChatConversation::query()
            ->forUser($user->id)
            ->select([
                'id', 'class_id', 'student_user_id', 'tutor_user_id', 'tentor_id',
                'last_message_id', 'last_message_at', 'created_at',
            ])
            ->with([
                'classRoom:class_id,title',
                'student:id,name',
                'tutor:id,name',
                'latestMessage:id,conversation_id,sender_id,body,created_at',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(30);
    }

    /**
     * @return array{0: ChatMessage, 1: bool}
     */
    public function send(User $sender, ChatConversation $conversation, string $body, string $clientMessageId): array
    {
        $body = $this->normalizeBody($body);
        abort_if($body === '', 422, 'Pesan tidak boleh kosong.');

        return DB::transaction(function () use ($sender, $conversation, $body, $clientMessageId) {
            // Serializing writes per conversation makes last_message_* deterministic
            // even when both participants send at exactly the same time.
            $lockedConversation = ChatConversation::query()
                ->whereKey($conversation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureAccessible($sender, $lockedConversation);

            $existing = ChatMessage::query()
                ->where('conversation_id', $lockedConversation->id)
                ->where('sender_id', $sender->id)
                ->where('client_message_id', $clientMessageId)
                ->with('sender:id,name')
                ->first();

            if ($existing) {
                return [$existing, false];
            }

            try {
                $message = $lockedConversation->messages()->create([
                    'sender_id' => $sender->id,
                    'client_message_id' => $clientMessageId,
                    'type' => 'text',
                    'body' => $body,
                ]);
            } catch (UniqueConstraintViolationException) {
                $message = ChatMessage::query()
                    ->where('conversation_id', $lockedConversation->id)
                    ->where('sender_id', $sender->id)
                    ->where('client_message_id', $clientMessageId)
                    ->with('sender:id,name')
                    ->firstOrFail();

                return [$message, false];
            }

            $lockedConversation->forceFill([
                'last_message_id' => $message->id,
                'last_message_at' => $message->created_at,
            ])->save();

            $this->ensureReadState($lockedConversation, $sender->id);
            $lockedConversation->readStates()
                ->where('user_id', $sender->id)
                ->update(['last_read_message_id' => $message->id, 'last_read_at' => now()]);

            DB::afterCommit(function () use ($message) {
                ChatMessageCreated::dispatch($message->load('sender:id,name'));
            });

            return [$message->load('sender:id,name'), true];
        }, attempts: 3);
    }

    public function markRead(User $user, ChatConversation $conversation, ?int $messageId = null): void
    {
        $this->ensureAccessible($user, $conversation);

        $messageId ??= $conversation->messages()->max('id');

        if ($messageId !== null) {
            abort_unless(
                $conversation->messages()->whereKey($messageId)->exists(),
                422,
                'Pesan tidak ditemukan pada percakapan ini.'
            );
        }

        DB::transaction(function () use ($user, $conversation, $messageId) {
            $state = $conversation->readStates()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $state) {
                $this->ensureReadState($conversation, $user->id);
                $state = $conversation->readStates()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            // A delayed request from an older tab must never move the read cursor
            // backwards and make already-read messages appear unread again.
            if ($messageId === null || (int) $state->last_read_message_id >= $messageId) {
                return;
            }

            $state->update([
                'last_read_message_id' => $messageId,
                'last_read_at' => now(),
            ]);
        }, attempts: 3);
    }

    private function normalizeBody(string $body): string
    {
        $body = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $body) ?? '';
        $body = preg_replace("/\r\n?/", "\n", $body) ?? '';

        return trim($body);
    }

    private function ensureReadState(ChatConversation $conversation, int $userId): void
    {
        $now = now();

        DB::table('chat_read_states')->insertOrIgnore([
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'last_read_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
