<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ClassSchedule;
use App\Models\User;
use App\Services\PlanModuleService;
use App\Services\TutorChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(private TutorChatService $chatService)
    {
    }

    public function studentShow(Request $request, ClassSchedule $classSchedule): View
    {
        [$conversation] = $this->chatService->openForStudent($request->user(), $classSchedule);
        $embedded = $request->boolean('embed');

        return $this->showConversation(
            $request,
            $conversation,
            $embedded ? 'chat.embed-layout' : 'user.layout.user',
            'Chat dengan Tutor',
            route('user.class-schedule.index'),
            'Jadwal Kelas',
            'user.chat.messages',
            'user.chat.messages.store',
            'user.chat.read',
            $embedded,
        );
    }

    public function parentIndex(Request $request): View
    {
        $parent = $request->user();
        $children = $parent->children()->orderBy('name')->get(['users.id', 'users.name']);

        return view('parent.chat-index', [
            'children' => $children,
            'child' => $children->first(),
            'contacts' => $this->chatService->chatContactsForParent($parent),
        ]);
    }

    public function parentShow(Request $request, User $child, ClassSchedule $classSchedule): View
    {
        [$conversation] = $this->chatService->openForParent($request->user(), $child, $classSchedule);
        $children = $request->user()->children()->orderBy('name')->get(['users.id', 'users.name']);

        return $this->showConversation(
            $request,
            $conversation,
            'parent.layout',
            'Chat Tutor · '.$child->name,
            route('parent.chat.index'),
            'Daftar Tutor',
            'parent.chat.messages',
            'parent.chat.messages.store',
            'parent.chat.read',
            false,
            null,
            null,
            $conversation->tutor()->value('name'),
            $children,
            $child,
        );
    }

    public function tutorIndex(Request $request): View
    {
        return view('chat.tutor-index', [
            'conversations' => $this->chatService->conversationsFor($request->user()),
            'unreadCounts' => $this->chatService->unreadCountsFor($request->user()),
        ]);
    }

    public function tutorShow(Request $request, ChatConversation $conversation): View
    {
        $this->chatService->ensureAccessible($request->user(), $conversation);
        $conversation->loadMissing('schedule:id,class_id,study_group_id,tentor_id,schedule_type,is_active,title');

        $scheduleTitle = $conversation->schedule?->title;

        $conversations = $this->chatService->conversationsFor($request->user());

        return $this->showConversation(
            $request,
            $conversation,
            'tutor.layout',
            'Chat dengan '.$conversation->student()->value('name').($scheduleTitle ? ' · '.$scheduleTitle : ''),
            route('tutor.chat.index'),
            'Daftar Chat',
            'tutor.chat.messages',
            'tutor.chat.messages.store',
            'tutor.chat.read',
            false,
            $conversations,
        );
    }

    public function messages(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->chatService->ensureAccessible($request->user(), $conversation);

        $validated = $request->validate([
            'before_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $messages = $this->chatService->messages(
            $conversation,
            $validated['before_id'] ?? null,
            $validated['limit'] ?? 50,
        );

        $hasMore = $messages->isNotEmpty()
            && $conversation->messages()->where('id', '<', $messages->first()->id)->exists();
        $peerLastReadMessageId = $this->chatService->peerLastReadMessageId($conversation, $request->user());

        return response()->json([
            'data' => $messages->map(fn ($message) => $message->toChatPayload($peerLastReadMessageId))->values(),
            'has_more' => $hasMore,
        ]);
    }

    public function store(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->chatService->ensureAccessible($request->user(), $conversation);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:4000', 'required_without:attachment'],
            'client_message_id' => ['required', 'uuid'],
            'attachment' => [
                'nullable',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,jpg,jpeg,png,webp',
            ],
        ]);

        [$message, $created] = $this->chatService->send(
            $request->user(),
            $conversation,
            $validated['body'] ?? '',
            $validated['client_message_id'],
            $request->file('attachment'),
        );

        return response()->json([
            'data' => $message->toChatPayload(
                $this->chatService->peerLastReadMessageId($conversation, $request->user())
            ),
            'created' => $created,
        ], $created ? 201 : 200);
    }

    public function markRead(Request $request, ChatConversation $conversation): JsonResponse
    {
        $validated = $request->validate([
            'last_message_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->chatService->markRead(
            $request->user(),
            $conversation,
            $validated['last_message_id'] ?? null,
        );

        return response()->json(['ok' => true]);
    }

    public function downloadAttachment(Request $request, ChatMessage $message, PlanModuleService $planModules): StreamedResponse
    {
        abort_unless($planModules->allows('discussion'), 404);

        $message->loadMissing('conversation');
        abort_unless($message->conversation && $message->attachment_path, 404);
        $this->chatService->ensureAccessible($request->user(), $message->conversation);
        abort_unless(Storage::disk('local')->exists($message->attachment_path), 404);

        return Storage::disk('local')->download(
            $message->attachment_path,
            $message->attachment_name ?: 'lampiran-chat'
        );
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $conversations = $this->chatService->conversationsFor($request->user());

        return response()->json([
            'data' => $conversations->getCollection()->map(function (ChatConversation $conversation) use ($request) {
                $peer = (int) $conversation->student_user_id === (int) $request->user()->id
                    ? $conversation->tutor
                    : $conversation->student;

                return [
                    'id' => $conversation->id,
                    'schedule' => [
                        'id' => $conversation->class_schedule_id,
                        'title' => $conversation->schedule?->title ?? $conversation->classRoom?->title,
                    ],
                    'peer' => [
                        'id' => $peer?->id,
                        'name' => $peer?->name,
                    ],
                    'last_message' => $conversation->latestMessage?->toChatPayload(),
                    'last_message_at' => $conversation->last_message_at?->toIso8601String(),
                ];
            })->values(),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
            ],
        ]);
    }

    public function apiOpen(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_schedule_id' => ['required', 'integer', 'exists:class_schedules,id'],
        ]);

        [$conversation, $created] = $this->chatService->openForStudent(
            $request->user(),
            ClassSchedule::query()->findOrFail($validated['class_schedule_id']),
        );

        return response()->json(['data' => ['id' => $conversation->id]], $created ? 201 : 200);
    }

    private function showConversation(
        Request $request,
        ChatConversation $conversation,
        string $layout,
        string $title,
        string $backUrl,
        string $backLabel,
        string $messagesRoute,
        string $storeRoute,
        string $readRoute,
        bool $embedded = false,
        mixed $conversationList = null,
        ?Collection $unreadCounts = null,
        ?string $peerName = null,
        ?Collection $parentChildren = null,
        ?User $parentChild = null,
    ): View {
        $conversation->load([
            'classRoom:class_id,title',
            'schedule:id,class_id,study_group_id,tentor_id,schedule_type,is_active,title',
            'student:id,name',
            'tutor:id,name',
        ]);
        $messages = $this->chatService->messages($conversation);
        $this->chatService->markRead($request->user(), $conversation, $messages->last()?->id);
        $peerLastReadMessageId = $this->chatService->peerLastReadMessageId($conversation, $request->user());
        $unreadCounts ??= $conversationList
            ? $this->chatService->unreadCountsFor($request->user())
            : collect();
        $children = $parentChildren;
        $child = $parentChild;

        return view('chat.show', compact(
            'layout',
            'conversation',
            'messages',
            'title',
            'backUrl',
            'backLabel',
            'messagesRoute',
            'storeRoute',
            'readRoute',
            'embedded',
            'conversationList',
            'unreadCounts',
            'peerLastReadMessageId',
            'peerName',
            'children',
            'child',
        ));
    }
}
