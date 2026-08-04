@extends('tutor.layout')

@section('title', 'Chat Siswa')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Chat Siswa</h1>
        <p class="mt-1 text-sm text-slate-500">Percakapan dari kelas yang Anda ampu.</p>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @forelse($conversations as $conversation)
            <a href="{{ route('tutor.chat.show', $conversation) }}" class="flex items-center gap-3 border-b border-slate-100 px-4 py-4 transition hover:bg-slate-50 sm:px-6">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 font-bold text-primary">{{ strtoupper(mb_substr($conversation->student?->name, 0, 1)) }}</div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline justify-between gap-3"><p class="truncate font-semibold text-slate-800">{{ $conversation->student?->name }}</p><time class="shrink-0 text-xs text-slate-400">{{ $conversation->last_message_at?->diffForHumans() }}</time></div>
                    <p class="mt-0.5 truncate text-xs text-primary">{{ $conversation->classRoom?->title }}</p>
                    <p class="mt-1 truncate text-sm text-slate-500">{{ $conversation->latestMessage?->body ?? 'Belum ada pesan' }}</p>
                </div>
            </a>
        @empty
            <div class="px-6 py-16 text-center text-sm text-slate-500">Belum ada chat dari siswa.</div>
        @endforelse
    </section>
    <div class="mt-5">{{ $conversations->links() }}</div>
</div>
@endsection
