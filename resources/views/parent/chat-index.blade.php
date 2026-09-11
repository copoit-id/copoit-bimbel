@extends('parent.layout')

@section('title', 'Chat Tutor')

@section('content')
<div class="space-y-6">
    <div>
        <p class="text-sm font-semibold text-primary">Komunikasi</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Chat tutor</h1>
        <p class="mt-1 text-sm text-gray-500">Komunikasi dengan tutor berdasarkan jadwal rutin anak.</p>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h2 class="font-bold text-gray-900">Pilih tutor</h2><p class="mt-1 text-sm text-gray-500">Pilih jadwal untuk membuka percakapan.</p></div><i class="ri-chat-3-line text-xl text-primary"></i></div>
        @forelse($contacts as $contact)
            <a href="{{ $contact['url'] }}" class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 transition-colors last:border-0 hover:bg-slate-50">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 font-bold text-primary">{{ strtoupper(mb_substr($contact['tutor_name'], 0, 1)) }}</span>
                <span class="min-w-0 flex-1"><span class="block truncate font-semibold text-gray-800">{{ $contact['tutor_name'] }}</span><span class="mt-0.5 block truncate text-xs text-primary">{{ $contact['child_name'] }} · {{ $contact['schedule_title'] }}</span></span>
                <i class="ri-arrow-right-s-line text-lg text-gray-400"></i>
            </a>
        @empty
            <div class="px-6 py-16 text-center text-sm text-gray-500"><i class="ri-chat-1-line mb-2 block text-3xl text-slate-300"></i>Belum ada tutor dari jadwal rutin anak yang dapat dihubungi.</div>
        @endforelse
    </section>
</div>
@endsection
