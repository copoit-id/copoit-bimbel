@extends('user.layout.new-user')

@section('title', 'Catatan Saya')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-sm text-gray-500">AI Learning Tools</p><h1 class="text-2xl font-semibold text-gray-900">Catatan Saya</h1><p class="mt-1 text-sm text-gray-500">Ringkasan materi yang kamu simpan dari pembahasan soal.</p></div>
        <a href="{{ url()->previous() }}" class="w-fit rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Kembali</a>
    </div>
    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>@endif
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($notes as $note)
            <article class="flex flex-col rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-wide text-primary">{{ $note->tryout?->name ?? 'Tryout' }}</p><h2 class="mt-1 font-semibold text-gray-900">{{ $note->title }}</h2></div><i class="ri-sticky-note-line text-xl text-primary"></i></div>
                <p class="mt-3 line-clamp-4 text-sm leading-6 text-gray-600">{{ data_get($note->payload, 'summary') }}</p>
                <p class="mt-3 text-xs text-gray-400">Disimpan {{ $note->saved_at?->translatedFormat('d M Y H:i') }}</p>
                <div class="mt-auto flex gap-2 pt-4"><a href="{{ route('user.ai-learning.notes.pdf', $note) }}" class="flex-1 rounded-lg bg-primary px-3 py-2 text-center text-xs font-semibold text-white hover:bg-primary/90"><i class="ri-file-pdf-2-line mr-1"></i>Ekspor PDF</a><form method="POST" action="{{ route('user.ai-learning.notes.destroy', $note) }}" onsubmit="return confirm('Hapus catatan ini?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Hapus</button></form></div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 md:col-span-2 xl:col-span-3"><i class="ri-sticky-note-line mb-3 block text-4xl text-gray-300"></i>Belum ada catatan tersimpan. Buka pembahasan soal lalu gunakan AI Catatan Materi.</div>
        @endforelse
    </div>
    {{ $notes->links() }}
</div>
@endsection
