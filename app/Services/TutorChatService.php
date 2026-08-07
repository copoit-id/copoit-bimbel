<?php

namespace App\Services;

use App\Events\ChatMessageCreated;
use App\Events\ChatMessagesRead;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ClassSchedule;
use App\Models\User;
use App\Models\UserPackageAcces;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TutorChatService
{
    /**
     * @return array{0: ChatConversation, 1: bool}
     */
    public function openForStudent(User $student, ClassSchedule $schedule): array
    {
        $this->ensureFeatureEnabled();
        abort_if($student->isTutor(), 403, 'Akun tutor tidak dapat membuka chat sebagai siswa.');
        abort_unless(
            $schedule->schedule_type === 'recurring' && $schedule->is_active,
            404,
            'Chat hanya tersedia pada jadwal rutin yang aktif.'
        );
        abort_unless($this->studentCanAccessSchedule($student, $schedule), 403, 'Anda tidak memiliki akses ke jadwal ini.');

        $schedule->loadMissing('tentor:id,user_id,is_active');
        $tentor = $schedule->tentor;

        abort_unless($tentor?->is_active && $tentor->user_id, 422, 'Tutor aktif belum tersedia untuk jadwal ini.');

        $attributes = [
            'class_id' => $schedule->class_id,
            'class_schedule_id' => $schedule->id,
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
        $this->ensureFeatureEnabled();
        abort_unless($this->isAccessibleBy($user, $conversation), 403, 'Anda tidak memiliki akses ke percakapan ini.');
    }

    public function isAccessibleBy(User $user, ChatConversation $conversation): bool
    {
        if ((int) $conversation->student_user_id === (int) $user->id) {
            $conversation->loadMissing('schedule');

            if ($conversation->schedule) {
                return $conversation->schedule->schedule_type === 'recurring'
                    && $conversation->schedule->is_active
                    && $this->studentCanAccessSchedule($user, $conversation->schedule);
            }

            // Conversations created before this change stay readable through
            // their former class context, without becoming new chat threads.
            return $conversation->classRoom()->first()?->canUserAccess($user->id) ?? false;
        }

        if ((int) $conversation->tutor_user_id !== (int) $user->id) {
            return false;
        }

        $conversation->loadMissing('schedule');

        if ($conversation->schedule) {
            return $conversation->schedule->is_active
                && $conversation->schedule->tentor()
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->exists();
        }

        return $conversation->tentor()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function messages(ChatConversation $conversation, ?int $beforeId = null, int $limit = 50): Collection
    {
        $limit = min(max($limit, 1), 100);

        return $conversation->messages()
            ->select([
                'id', 'conversation_id', 'sender_id', 'client_message_id', 'type', 'body',
                'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size', 'created_at',
            ])
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
        $this->ensureFeatureEnabled();

        return ChatConversation::query()
            ->forUser($user->id)
            ->select([
                'id', 'class_id', 'class_schedule_id', 'student_user_id', 'tutor_user_id', 'tentor_id',
                'last_message_id', 'last_message_at', 'created_at',
            ])
            ->with([
                'classRoom:class_id,title',
                'schedule:id,class_id,study_group_id,tentor_id,schedule_type,is_active,title',
                'student:id,name',
                'tutor:id,name',
                'latestMessage:id,conversation_id,sender_id,body,created_at',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(\App\Support\Pagination::perPage(30));
    }

    /**
     * @return Collection<int, array{schedule_id: int, schedule_title: string, tutor_name: string, url: string}>
     */
    public function chatContactsForStudent(User $student): Collection
    {
        $this->ensureFeatureEnabled();
        abort_if($student->isTutor(), 403, 'Akun tutor tidak dapat membuka chat sebagai siswa.');

        $student->loadMissing('participantDestinationCategory');
        $destinationCategoryIds = collect([
            $student->participant_destination_category_id,
            $student->participantDestinationCategory?->parent_id,
        ])->filter()->map(fn ($id): int => (int) $id)->values();
        $packageIds = UserPackageAcces::query()
            ->where('user_id', $student->id)
            ->active()
            ->pluck('package_id');

        return ClassSchedule::query()
            ->select(['id', 'class_id', 'study_group_id', 'tentor_id', 'title'])
            ->with('tentor:id,name,user_id,is_active')
            ->where('schedule_type', 'recurring')
            ->where('is_active', true)
            ->whereHas('tentor', fn (Builder $query) => $query
                ->where('is_active', true)
                ->whereNotNull('user_id'))
            ->where(function (Builder $query) use ($student, $destinationCategoryIds, $packageIds): void {
                $query->whereHas('studyGroup.users', fn (Builder $userQuery) => $userQuery->where('users.id', $student->id));

                $query->orWhere(function (Builder $classAccessQuery) use ($student): void {
                    $classAccessQuery
                        ->whereNull('study_group_id')
                        ->whereHas('class.userAccess', fn (Builder $accessQuery) => $accessQuery
                            ->where('user_id', $student->id)
                            ->active());
                });

                $query->orWhere(function (Builder $packageAccessQuery) use ($packageIds): void {
                    $packageAccessQuery
                        ->whereNull('study_group_id')
                        ->whereHas('packages', fn (Builder $packageQuery) => $packageQuery
                            ->whereIn('packages.package_id', $packageIds));
                });

                $query->orWhere(function (Builder $legacyQuery) use ($destinationCategoryIds, $packageIds): void {
                    $legacyQuery
                        ->whereNull('study_group_id')
                        ->whereDoesntHave('packages')
                        ->where(function (Builder $legacyAccessQuery) use ($destinationCategoryIds, $packageIds): void {
                            if ($destinationCategoryIds->isNotEmpty()) {
                                $legacyAccessQuery->whereHas('destinationCategories', fn (Builder $categoryQuery) => $categoryQuery
                                    ->whereIn('participant_destination_categories.id', $destinationCategoryIds));
                            }

                            $legacyAccessQuery->orWhere(function (Builder $fallbackQuery) use ($packageIds): void {
                                $fallbackQuery
                                    ->whereDoesntHave('destinationCategories')
                                    ->whereHas('class.packages', fn (Builder $packageQuery) => $packageQuery
                                        ->whereIn('packages.package_id', $packageIds));
                            });
                        });
                });
            })
            ->orderBy('title')
            ->get()
            ->map(fn (ClassSchedule $schedule): array => [
                'schedule_id' => (int) $schedule->id,
                'schedule_title' => $schedule->title,
                'tutor_name' => $schedule->tentor->name,
                'url' => route('user.chat.schedule.show', [
                    'classSchedule' => $schedule,
                    'embed' => 1,
                ]),
            ])
            ->values();
    }

    /**
     * @return Collection<string, int>
     */
    public function unreadCountsFor(User $user): Collection
    {
        $this->ensureFeatureEnabled();

        return ChatMessage::query()
            ->selectRaw('chat_messages.conversation_id, COUNT(*) as unread_count')
            ->leftJoin('chat_read_states as chat_read_states', function ($join) use ($user): void {
                $join->on('chat_read_states.conversation_id', '=', 'chat_messages.conversation_id')
                    ->where('chat_read_states.user_id', '=', $user->id);
            })
            ->whereHas('conversation', fn (Builder $conversationQuery) => $conversationQuery->forUser($user->id))
            ->where('chat_messages.sender_id', '!=', $user->id)
            ->where(function (Builder $query): void {
                $query->whereNull('chat_read_states.last_read_message_id')
                    ->orWhereColumn('chat_messages.id', '>', 'chat_read_states.last_read_message_id');
            })
            ->groupBy('chat_messages.conversation_id')
            ->pluck('unread_count', 'chat_messages.conversation_id')
            ->map(fn ($count): int => (int) $count);
    }

    public function unreadCountFor(User $user): int
    {
        return $this->unreadCountsFor($user)->sum();
    }

    public function peerLastReadMessageId(ChatConversation $conversation, User $user): ?int
    {
        $peerId = (int) $conversation->student_user_id === (int) $user->id
            ? (int) $conversation->tutor_user_id
            : (int) $conversation->student_user_id;

        return $conversation->readStates()
            ->where('user_id', $peerId)
            ->value('last_read_message_id');
    }

    /**
     * @return array{0: ChatMessage, 1: bool}
     */
    public function send(
        User $sender,
        ChatConversation $conversation,
        string $body,
        string $clientMessageId,
        ?UploadedFile $attachment = null,
    ): array
    {
        $body = $this->normalizeBody($body);
        abort_if($body === '' && ! $attachment, 422, 'Pesan atau lampiran wajib diisi.');
        $storedAttachmentPath = null;

        try {
            return DB::transaction(function () use ($sender, $conversation, $body, $clientMessageId, $attachment, &$storedAttachmentPath) {
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

            $attachmentData = [];
            if ($attachment) {
                $storedAttachmentPath = $attachment->store('chat-attachments', 'local');
                $attachmentData = [
                    'attachment_path' => $storedAttachmentPath,
                    'attachment_name' => Str::limit($attachment->getClientOriginalName(), 255, ''),
                    'attachment_mime' => $attachment->getMimeType(),
                    'attachment_size' => $attachment->getSize(),
                ];
            }

            try {
                $message = $lockedConversation->messages()->create([
                    'sender_id' => $sender->id,
                    'client_message_id' => $clientMessageId,
                    'type' => $attachment ? 'attachment' : 'text',
                    'body' => $body,
                    ...$attachmentData,
                ]);
            } catch (UniqueConstraintViolationException) {
                if ($storedAttachmentPath) {
                    Storage::disk('local')->delete($storedAttachmentPath);
                    $storedAttachmentPath = null;
                }
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
        } catch (Throwable $exception) {
            if ($storedAttachmentPath) {
                Storage::disk('local')->delete($storedAttachmentPath);
            }

            throw $exception;
        }
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

        $updated = DB::transaction(function () use ($user, $conversation, $messageId): bool {
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
                return false;
            }

            $state->update([
                'last_read_message_id' => $messageId,
                'last_read_at' => now(),
            ]);
            return true;
        }, attempts: 3);

        if ($updated && $messageId !== null) {
            ChatMessagesRead::dispatch($conversation->id, $user->id, $messageId);
        }
    }

    private function normalizeBody(string $body): string
    {
        $body = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $body) ?? '';
        $body = preg_replace("/\r\n?/", "\n", $body) ?? '';

        return trim($body);
    }

    private function ensureFeatureEnabled(): void
    {
        abort_unless((bool) config('client.branding.tutor_chat_enabled', false), 404);
    }

    private function studentCanAccessSchedule(User $student, ClassSchedule $schedule): bool
    {
        $schedule->loadMissing([
            'class.packages',
            'studyGroup.users:id',
            'destinationCategories:id,parent_id',
            'packages:package_id',
        ]);

        if ($schedule->study_group_id) {
            return $schedule->studyGroup?->users->contains('id', $student->id) ?? false;
        }

        if ($schedule->class?->userAccess()
            ->where('user_id', $student->id)
            ->active()
            ->exists()) {
            return true;
        }

        $destinationCategoryIds = collect([
            $student->participant_destination_category_id,
            $student->participantDestinationCategory?->parent_id,
        ])->filter()->map(fn ($id): int => (int) $id);

        if ($schedule->destinationCategories->pluck('id')->intersect($destinationCategoryIds)->isNotEmpty()) {
            return true;
        }

        $packageIds = $schedule->packages->isNotEmpty()
            ? $schedule->packages->pluck('package_id')
            : ($schedule->class?->packages?->pluck('package_id') ?? collect());

        return $packageIds->isNotEmpty()
            && UserPackageAcces::query()
                ->where('user_id', $student->id)
                ->whereIn('package_id', $packageIds)
                ->active()
                ->exists();
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
