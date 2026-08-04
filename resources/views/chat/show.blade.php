@extends($layout)

@section('title', $title)

@section('content')
<div class="mx-auto max-w-4xl" id="chat-app"
    data-conversation-id="{{ $conversation->id }}"
    data-current-user-id="{{ auth()->id() }}"
    data-messages-url="{{ route($messagesRoute, ['conversation' => '__conversation__']) }}"
    data-store-url="{{ route($storeRoute, ['conversation' => '__conversation__']) }}"
    data-read-url="{{ route($readRoute, ['conversation' => '__conversation__']) }}">
    <a href="{{ $backUrl }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-gray-500 hover:text-primary">
        <i class="ri-arrow-left-line"></i>{{ $backLabel }}
    </a>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header class="flex items-center gap-3 border-b border-slate-100 px-4 py-4 sm:px-6">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary/10 text-lg font-bold text-primary">
                {{ strtoupper(mb_substr((int) $conversation->student_user_id === auth()->id() ? $conversation->tutor?->name : $conversation->student?->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <h1 class="truncate font-bold text-slate-900">{{ $title }}</h1>
                <p class="truncate text-xs text-slate-500">{{ $conversation->classRoom?->title }}</p>
            </div>
            <span class="ml-auto inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Realtime</span>
        </header>

        <div id="chat-messages" class="h-[55vh] min-h-[360px] space-y-3 overflow-y-auto bg-slate-50 px-4 py-5 sm:px-6" aria-live="polite">
            @forelse($messages as $message)
                @php($mine = (int) $message->sender_id === auth()->id())
                <article data-message-id="{{ $message->id }}" class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[84%] rounded-2xl px-3.5 py-2.5 text-sm shadow-sm {{ $mine ? 'rounded-br-md bg-primary text-white' : 'rounded-bl-md bg-white text-slate-700' }}">
                        @unless($mine)<p class="mb-1 text-xs font-semibold text-primary">{{ $message->sender?->name }}</p>@endunless
                        <p class="whitespace-pre-wrap break-words">{{ $message->body }}</p>
                        <time class="mt-1 block text-right text-[10px] {{ $mine ? 'text-white/70' : 'text-slate-400' }}" datetime="{{ $message->created_at?->toIso8601String() }}">{{ $message->created_at?->format('H:i') }}</time>
                    </div>
                </article>
            @empty
                <p id="chat-empty" class="pt-20 text-center text-sm text-slate-400">Mulai percakapan dengan mengirim pesan.</p>
            @endforelse
        </div>

        <form id="chat-form" class="border-t border-slate-100 bg-white p-3 sm:p-4">
            <div class="flex items-end gap-2">
                <label for="chat-body" class="sr-only">Pesan</label>
                <textarea id="chat-body" rows="1" maxlength="4000" required placeholder="Tulis pesan..." class="max-h-32 min-h-11 flex-1 resize-none rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/15"></textarea>
                <button id="chat-send" type="submit" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50" aria-label="Kirim pesan"><i class="ri-send-plane-2-fill text-lg"></i></button>
            </div>
            <p id="chat-error" class="hidden pt-2 text-xs text-red-600" role="alert"></p>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const app = document.getElementById('chat-app');
    if (!app) return;

    const conversationId = app.dataset.conversationId;
    const currentUserId = Number(app.dataset.currentUserId);
    const messages = document.getElementById('chat-messages');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-body');
    const sendButton = document.getElementById('chat-send');
    const error = document.getElementById('chat-error');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const endpoint = (name) => app.dataset[name].replace('__conversation__', conversationId);

    const scrollToBottom = () => { messages.scrollTop = messages.scrollHeight; };
    const formatTime = (value) => new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date(value));

    function appendMessage(message) {
        if (!message?.id || messages.querySelector(`[data-message-id="${message.id}"]`)) return;
        document.getElementById('chat-empty')?.remove();

        const mine = Number(message.sender_id) === currentUserId;
        const row = document.createElement('article');
        row.dataset.messageId = message.id;
        row.className = `flex ${mine ? 'justify-end' : 'justify-start'}`;
        const bubble = document.createElement('div');
        bubble.className = `max-w-[84%] rounded-2xl px-3.5 py-2.5 text-sm shadow-sm ${mine ? 'rounded-br-md bg-primary text-white' : 'rounded-bl-md bg-white text-slate-700'}`;
        if (!mine && message.sender_name) {
            const sender = document.createElement('p');
            sender.className = 'mb-1 text-xs font-semibold text-primary';
            sender.textContent = message.sender_name;
            bubble.append(sender);
        }
        const body = document.createElement('p');
        body.className = 'whitespace-pre-wrap break-words';
        body.textContent = message.body;
        const time = document.createElement('time');
        time.className = `mt-1 block text-right text-[10px] ${mine ? 'text-white/70' : 'text-slate-400'}`;
        time.dateTime = message.created_at;
        time.textContent = formatTime(message.created_at);
        bubble.append(body, time);
        row.append(bubble);
        messages.append(row);
        scrollToBottom();

        if (!mine) markRead(message.id);
    }

    async function markRead(lastMessageId = null) {
        try {
            await fetch(endpoint('readUrl'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ last_message_id: lastMessageId }),
            });
        } catch (_) {}
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = input.value.trim();
        if (!body || sendButton.disabled) return;

        error.classList.add('hidden');
        sendButton.disabled = true;
        const clientMessageId = window.crypto?.randomUUID?.() || 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
            const random = Math.floor(Math.random() * 16);
            const value = character === 'x' ? random : (random & 0x3) | 0x8;
            return value.toString(16);
        });

        try {
            const response = await fetch(endpoint('storeUrl'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ body, client_message_id: clientMessageId }),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Pesan gagal dikirim.');
            appendMessage(payload.data);
            input.value = '';
            input.focus();
        } catch (exception) {
            error.textContent = exception.message || 'Pesan gagal dikirim. Coba lagi.';
            error.classList.remove('hidden');
        } finally {
            sendButton.disabled = false;
        }
    });

    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 128)}px`;
    });

    if (window.Echo) {
        window.Echo.private(`chat.conversation.${conversationId}`)
            .listen('.chat.message.created', ({ message }) => appendMessage(message));
    }

    scrollToBottom();
    markRead();
})();
</script>
@endpush
