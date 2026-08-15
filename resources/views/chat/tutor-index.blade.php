@extends('admin.layout.admin')

@section('title', 'Chat Siswa')

@section('content')
<div class="mx-auto max-w-none">
    <section class="grid h-[calc(100vh-9rem)] min-h-[600px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:grid-cols-[360px_minmax(0,1fr)]">
        <aside class="flex min-h-0 flex-col border-b border-slate-200 bg-white lg:border-b-0 lg:border-r">
            <header class="border-b border-slate-100 px-5 py-5">
                <div class="flex items-center justify-between gap-3">
                    <div><h1 class="font-bold text-slate-900">Chat Siswa</h1><p class="mt-1 text-xs text-slate-500">Pesan dari jadwal yang Anda ampu.</p></div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary"><i class="ri-chat-3-line text-lg"></i></span>
                </div>
            </header>
            <div class="min-h-0 flex-1 overflow-y-auto p-2">
                @forelse($conversations as $conversation)
                    @php($unreadCount = (int) ($unreadCounts->get($conversation->id) ?? 0))
                    <a href="{{ route('tutor.chat.show', $conversation) }}" class="mb-1 flex items-center gap-3 rounded-xl p-3 transition hover:bg-slate-50">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 font-bold text-primary">{{ strtoupper(mb_substr($conversation->student?->name, 0, 1)) }}</span>
                        <span class="min-w-0 flex-1"><span class="flex items-baseline justify-between gap-2"><span class="truncate text-sm font-semibold text-slate-800">{{ $conversation->student?->name }}</span><time class="shrink-0 text-[10px] text-slate-400">{{ $conversation->last_message_at?->format('H:i') }}</time></span><span class="mt-0.5 block truncate text-xs text-primary">{{ $conversation->schedule?->title ?? $conversation->classRoom?->title }}</span><span class="mt-1 block truncate text-xs {{ $unreadCount ? 'font-semibold text-slate-700' : 'text-slate-400' }}">{{ $conversation->latestMessage?->body ?? 'Belum ada pesan' }}</span></span>
                        @if($unreadCount > 0)<span class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-white">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>@endif
                    </a>
                @empty
                    <div class="flex h-full flex-col items-center justify-center px-6 text-center text-sm text-slate-500"><i class="ri-chat-1-line mb-3 text-4xl text-slate-300"></i>Belum ada chat dari siswa.</div>
                @endforelse
            </div>
        </aside>
        <div class="hidden flex-col items-center justify-center bg-slate-50 px-8 text-center lg:flex"><span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-3xl text-primary shadow-sm"><i class="ri-message-3-line"></i></span><h2 class="mt-5 text-lg font-bold text-slate-800">Pilih percakapan</h2><p class="mt-2 max-w-sm text-sm text-slate-500">Pilih siswa di sebelah kiri untuk melihat dan membalas percakapan.</p></div>
    </section>
    <div class="mt-5">{{ $conversations->links() }}</div>
</div>
@endsection
