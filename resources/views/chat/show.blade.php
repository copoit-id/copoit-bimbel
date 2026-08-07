@extends($layout)

@section('title', $title)

@section('content')
<div class="{{ $embedded || $conversationList ? 'mx-0 max-w-none' : 'mx-auto max-w-4xl' }}" id="chat-app"
    data-conversation-id="{{ $conversation->id }}"
    data-current-user-id="{{ auth()->id() }}"
    data-messages-url="{{ route($messagesRoute, ['conversation' => '__conversation__']) }}"
    data-store-url="{{ route($storeRoute, ['conversation' => '__conversation__']) }}"
    data-read-url="{{ route($readRoute, ['conversation' => '__conversation__']) }}">
    @if(! $embedded)
        <a href="{{ $backUrl }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-gray-500 hover:text-primary">
            <i class="ri-arrow-left-line"></i>{{ $backLabel }}
        </a>
    @endif

    <section class="{{ $conversationList ? 'grid h-[calc(100vh-9rem)] min-h-[600px] lg:grid-cols-[340px_minmax(0,1fr)]' : '' }} {{ $embedded ? 'flex h-screen flex-col rounded-none border-0 shadow-none' : 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm' }}">
        @if($conversationList)
            <aside class="flex min-h-0 flex-col border-b border-slate-200 bg-white lg:border-b-0 lg:border-r">
                <header class="border-b border-slate-100 px-5 py-5">
                    <h2 class="font-bold text-slate-900">Chat Siswa</h2>
                    <p class="mt-1 text-xs text-slate-500">Pilih percakapan untuk membalas.</p>
                </header>
                <div class="min-h-0 flex-1 overflow-y-auto p-2">
                    @forelse($conversationList as $contactConversation)
                        @php
                            $isActiveConversation = $contactConversation->getKey() === $conversation->getKey();
                            $contactName = $contactConversation->student?->name ?? 'Siswa';
                        @endphp
                        <a href="{{ route('tutor.chat.show', $contactConversation) }}" @if($isActiveConversation) aria-current="page" @endif class="mb-1 flex items-center gap-3 rounded-xl p-3 transition {{ $isActiveConversation ? 'bg-primary/10' : 'hover:bg-slate-50' }}">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $isActiveConversation ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600' }} font-bold">{{ strtoupper(mb_substr($contactName, 0, 1)) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-baseline justify-between gap-2"><span class="truncate text-sm font-semibold text-slate-800">{{ $contactName }}</span><time class="shrink-0 text-[10px] text-slate-400">{{ $contactConversation->last_message_at?->format('H:i') }}</time></span>
                                <span class="mt-0.5 block truncate text-xs {{ $isActiveConversation ? 'text-primary' : 'text-slate-500' }}">{{ $contactConversation->schedule?->title ?? $contactConversation->classRoom?->title }}</span>
                                <span class="mt-1 block truncate text-xs text-slate-400">{{ $contactConversation->latestMessage?->body ?? 'Belum ada pesan' }}</span>
                            </span>
                            @if(($unreadCounts->get($contactConversation->id) ?? 0) > 0)
                                <span class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-white">{{ ($unreadCounts->get($contactConversation->id) ?? 0) > 99 ? '99+' : $unreadCounts->get($contactConversation->id) }}</span>
                            @endif
                        </a>
                    @empty
                        <p class="px-4 py-12 text-center text-sm text-slate-500">Belum ada chat dari siswa.</p>
                    @endforelse
                </div>
            </aside>
            <div class="flex min-h-0 flex-col bg-white">
        @endif

        @unless($embedded)
        <header class="flex items-center gap-3 border-b border-slate-100 px-4 py-4 sm:px-6">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary/10 text-lg font-bold text-primary">
                {{ strtoupper(mb_substr((int) $conversation->student_user_id === auth()->id() ? $conversation->tutor?->name : $conversation->student?->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <h1 class="truncate font-bold text-slate-900">{{ $title }}</h1>
                <p class="truncate text-xs text-slate-500">{{ $conversation->schedule?->title ?? $conversation->classRoom?->title }}</p>
            </div>
            <span id="chat-presence" class="ml-auto inline-flex items-center gap-1.5 text-xs font-medium text-slate-400"><span class="h-2 w-2 rounded-full bg-slate-300"></span>Offline</span>
        </header>
        @endunless

        <div id="chat-messages" class="{{ $embedded || $conversationList ? 'min-h-0 flex-1' : 'h-[55vh] min-h-[360px]' }} space-y-3 overflow-y-auto bg-slate-50 px-4 py-5 sm:px-6" aria-live="polite">
            @forelse($messages as $message)
                @php($mine = (int) $message->sender_id === auth()->id())
                @php($isRead = $mine && $peerLastReadMessageId !== null && (int) $message->id <= $peerLastReadMessageId)
                <article data-message-id="{{ $message->id }}" class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[84%] rounded-2xl px-3.5 py-2.5 text-sm shadow-sm {{ $mine ? 'rounded-br-md bg-primary text-white' : 'rounded-bl-md bg-white text-slate-700' }}">
                        @unless($mine)<p class="mb-1 text-xs font-semibold text-primary">{{ $message->sender?->name }}</p>@endunless
                        @if($message->body)<p class="whitespace-pre-wrap break-words">{{ $message->body }}</p>@endif
                        @if($message->attachment_path)
                            <a href="{{ route('chat.attachments.download', $message) }}" class="{{ $mine ? 'bg-white/15 hover:bg-white/20' : 'bg-slate-100 hover:bg-slate-200' }} mt-2 flex items-center gap-2 rounded-xl p-2.5 transition" target="_blank" rel="noopener noreferrer">
                                <i class="ri-file-download-line text-lg"></i><span class="min-w-0 flex-1"><span class="block truncate font-semibold">{{ $message->attachment_name }}</span><span class="text-[10px] opacity-70">{{ number_format(($message->attachment_size ?? 0) / 1024, 1) }} KB</span></span>
                            </a>
                        @endif
                        <time class="mt-1 flex items-center justify-end gap-1 text-[10px] {{ $mine ? 'text-white/70' : 'text-slate-400' }}" datetime="{{ $message->created_at?->toIso8601String() }}"><span>{{ $message->created_at?->format('H:i') }}</span>@if($mine)<i data-read-status class="{{ $isRead ? 'ri-check-double-line text-sky-200' : 'ri-check-line' }}"></i>@endif</time>
                    </div>
                </article>
            @empty
                <p id="chat-empty" class="pt-20 text-center text-sm text-slate-400">Mulai percakapan dengan mengirim pesan.</p>
            @endforelse
        </div>

        <form id="chat-form" class="border-t border-slate-100 bg-white p-3 sm:p-4">
            <div class="flex items-end gap-2">
                <label for="chat-attachment" class="inline-flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-primary" title="Lampirkan materi atau file"><i class="ri-attachment-2 text-lg"></i></label>
                <input id="chat-attachment" type="file" class="sr-only" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.jpg,.jpeg,.png,.webp">
                <label for="chat-body" class="sr-only">Pesan</label>
                <textarea id="chat-body" rows="1" maxlength="4000" placeholder="Tulis pesan..." class="max-h-32 min-h-11 flex-1 resize-none rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/15"></textarea>
                <button id="chat-send" type="submit" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50" aria-label="Kirim pesan"><i class="ri-send-plane-2-fill text-lg"></i></button>
            </div>
            <div id="chat-attachment-preview" class="hidden items-center justify-between gap-2 pt-2 text-xs text-slate-500"><span id="chat-attachment-name" class="truncate"></span><button id="chat-remove-attachment" type="button" class="font-semibold text-red-500 hover:text-red-600">Hapus</button></div>
            <p class="pt-2 text-[10px] text-slate-400">Maks. 10 MB · PDF, Office, TXT, JPG, PNG, atau WEBP.</p>
            <p id="chat-error" class="hidden pt-2 text-xs text-red-600" role="alert"></p>
        </form>
        @if($conversationList)
            </div>
        @endif
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
    const attachmentInput = document.getElementById('chat-attachment');
    const attachmentPreview = document.getElementById('chat-attachment-preview');
    const attachmentName = document.getElementById('chat-attachment-name');
    const removeAttachment = document.getElementById('chat-remove-attachment');
    const sendButton = document.getElementById('chat-send');
    const error = document.getElementById('chat-error');
    const presence = document.getElementById('chat-presence');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const endpoint = (name) => app.dataset[name].replace('__conversation__', conversationId);

    const scrollToBottom = () => { messages.scrollTop = messages.scrollHeight; };
    const formatTime = (value) => new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date(value));
    const formatFileSize = (bytes) => bytes >= 1024 * 1024 ? `${(bytes / (1024 * 1024)).toFixed(1)} MB` : `${Math.max(bytes / 1024, 0.1).toFixed(1)} KB`;

    function setPresence(isOnline) {
        if (presence) {
            presence.className = `ml-auto inline-flex items-center gap-1.5 text-xs font-medium ${isOnline ? 'text-emerald-600' : 'text-slate-400'}`;
            presence.innerHTML = `<span class="h-2 w-2 rounded-full ${isOnline ? 'bg-emerald-500' : 'bg-slate-300'}"></span>${isOnline ? 'Online' : 'Offline'}`;
        }

        if (window.parent !== window) {
            window.parent.postMessage({ type: 'tutor-chat-presence', online: isOnline }, window.location.origin);
        }
    }

    function markMessagesRead(lastMessageId) {
        messages.querySelectorAll('[data-message-id]').forEach((row) => {
            if (Number(row.dataset.messageId) > Number(lastMessageId)) return;
            const status = row.querySelector('[data-read-status]');
            if (!status) return;
            status.className = 'ri-check-double-line text-sky-200';
        });
    }

    function renderAttachment(attachment, mine) {
        if (!attachment?.url) return null;
        const link = document.createElement('a');
        link.href = attachment.url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.className = `mt-2 flex items-center gap-2 rounded-xl p-2.5 transition ${mine ? 'bg-white/15 hover:bg-white/20' : 'bg-slate-100 hover:bg-slate-200'}`;
        const icon = document.createElement('i');
        icon.className = 'ri-file-download-line text-lg';
        const content = document.createElement('span');
        content.className = 'min-w-0 flex-1';
        const name = document.createElement('span');
        name.className = 'block truncate font-semibold';
        name.textContent = attachment.name || 'Lampiran';
        const size = document.createElement('span');
        size.className = 'text-[10px] opacity-70';
        size.textContent = formatFileSize(Number(attachment.size || 0));
        content.append(name, size);
        link.append(icon, content);
        return link;
    }

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
        if (message.body) {
            const body = document.createElement('p');
            body.className = 'whitespace-pre-wrap break-words';
            body.textContent = message.body;
            bubble.append(body);
        }
        const attachment = renderAttachment(message.attachment, mine);
        if (attachment) bubble.append(attachment);
        const time = document.createElement('time');
        time.className = `mt-1 flex items-center justify-end gap-1 text-[10px] ${mine ? 'text-white/70' : 'text-slate-400'}`;
        time.dateTime = message.created_at;
        time.textContent = formatTime(message.created_at);
        if (mine) {
            const status = document.createElement('i');
            status.dataset.readStatus = '';
            status.className = message.is_read ? 'ri-check-double-line text-sky-200' : 'ri-check-line';
            time.append(status);
        }
        bubble.append(time);
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

    async function refreshMessages() {
        if (document.hidden) return;

        try {
            const response = await fetch(endpoint('messagesUrl'), {
                headers: { 'Accept': 'application/json' },
            });
            if (!response.ok) return;

            const payload = await response.json();
            payload.data?.forEach((message) => {
                appendMessage(message);
                if (message.is_read) markMessagesRead(message.id);
            });
        } catch (_) {
            // Koneksi boleh gagal sementara; percobaan berikutnya akan berjalan otomatis.
        }
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = input.value.trim();
        const attachment = attachmentInput.files?.[0];
        if ((!body && !attachment) || sendButton.disabled) return;
        if (attachment && attachment.size > 10 * 1024 * 1024) {
            error.textContent = 'Ukuran file maksimal 10 MB.';
            error.classList.remove('hidden');
            return;
        }

        error.classList.add('hidden');
        sendButton.disabled = true;
        const clientMessageId = window.crypto?.randomUUID?.() || 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
            const random = Math.floor(Math.random() * 16);
            const value = character === 'x' ? random : (random & 0x3) | 0x8;
            return value.toString(16);
        });

        try {
            const formData = new FormData();
            formData.append('body', body);
            formData.append('client_message_id', clientMessageId);
            if (attachment) formData.append('attachment', attachment);
            const response = await fetch(endpoint('storeUrl'), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: formData,
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Pesan gagal dikirim.');
            appendMessage(payload.data);
            input.value = '';
            attachmentInput.value = '';
            attachmentPreview.classList.add('hidden');
            attachmentPreview.classList.remove('flex');
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

    attachmentInput.addEventListener('change', () => {
        const attachment = attachmentInput.files?.[0];
        if (!attachment) return;
        attachmentName.textContent = `${attachment.name} · ${formatFileSize(attachment.size)}`;
        attachmentPreview.classList.remove('hidden');
        attachmentPreview.classList.add('flex');
    });

    removeAttachment.addEventListener('click', () => {
        attachmentInput.value = '';
        attachmentPreview.classList.add('hidden');
        attachmentPreview.classList.remove('flex');
    });

    if (window.Echo) {
        window.Echo.private(`chat.conversation.${conversationId}`)
            .listen('.chat.message.created', ({ message }) => appendMessage(message))
            .listen('.chat.messages.read', ({ reader_id, last_read_message_id }) => {
                if (Number(reader_id) !== currentUserId) markMessagesRead(last_read_message_id);
            });

        window.Echo.join(`chat.presence.${conversationId}`)
            .here((users) => setPresence(users.some((user) => Number(user.id) !== currentUserId)))
            .joining((user) => { if (Number(user.id) !== currentUserId) setPresence(true); })
            .leaving((user) => { if (Number(user.id) !== currentUserId) setPresence(false); });
    } else {
        setPresence(false);
    }

    scrollToBottom();
    markRead();
    window.setInterval(refreshMessages, 3000);
})();
</script>
@endpush
