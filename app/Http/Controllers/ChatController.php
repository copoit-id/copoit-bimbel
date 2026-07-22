<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ClassModel;
use App\Services\TutorChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(private TutorChatService $chatService)
    {
    }

    public function studentShow(Request $request, ClassModel $class): View
    {
        [$conversation] = $this->chatService->openForStudent($request->user(), $class);

        return $this->showConversation(
            $request,
            $conversation,
            'user.layout.user',
            'Chat dengan Tutor',
            route('user.package.my'),
            'Kelas Saya',
            'user.chat.messages',
            'user.chat.messages.store',
            'user.chat.read',
        );
    }

    public function tutorIndex(Request $request): View
    {
        return view('chat.tutor-index', [
            'conversations' => $this->chatService->conversationsFor($request->user()),
        ]);
    }

    public function tutorShow(Request $request, ChatConversation $conversation): View
    {
        $this->chatService->ensureAccessible($request->user(), $conversation);

        return $this->showConversation(
            $request,
            $conversation,
            'tutor.layout',
            'Chat dengan '.$conversation->student()->value('name'),
            route('tutor.chat.index'),
            'Daftar Chat',
            'tutor.chat.messages',
            'tutor.chat.messages.store',
            'tutor.chat.read',
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

        return response()->json([
            'data' => $messages->map(fn ($message) => $message->toChatPayload())->values(),
            'has_more' => $hasMore,
        ]);
    }

    public function store(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->chatService->ensureAccessible($request->user(), $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
            'client_message_id' => ['required', 'uuid'],
        ]);

        [$message, $created] = $this->chatService->send(
            $request->user(),
            $conversation,
            $validated['body'],
            $validated['client_message_id'],
        );

        return response()->json([
            'data' => $message->toChatPayload(),
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
                    'class' => [
                        'id' => $conversation->class_id,
                        'title' => $conversation->classRoom?->title,
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
            'class_id' => ['required', 'integer', 'exists:classes,class_id'],
        ]);

        [$conversation, $created] = $this->chatService->openForStudent(
            $request->user(),
            ClassModel::query()->findOrFail($validated['class_id']),
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
    ): View {
        $conversation->load([
            'classRoom:class_id,title',
            'student:id,name',
            'tutor:id,name',
        ]);
        $messages = $this->chatService->messages($conversation);
        $this->chatService->markRead($request->user(), $conversation, $messages->last()?->id);

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
        ));
    }
}
