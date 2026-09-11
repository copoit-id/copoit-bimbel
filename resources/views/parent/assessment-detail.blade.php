@extends('parent.layout')

@section('title', 'Detail Tryout')

@section('content')
<div class="space-y-5">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('parent.assessments', ['anak' => $child->id]) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline"><i class="ri-arrow-left-line"></i>Kembali ke riwayat ujian</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">{{ $tryout->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">Rincian percobaan dan perkembangan {{ $child->name }} pada tryout ini.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700"><i class="ri-user-line text-primary"></i>{{ $child->name }}</span>
    </header>

    <section class="grid gap-3 sm:grid-cols-3">
        <article class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-center justify-between"><span class="text-sm text-gray-500">Percobaan</span><i class="ri-repeat-2-line text-lg text-primary"></i></div><p class="mt-3 text-2xl font-bold text-gray-900">{{ $attemptSummary['completed'] }}</p><p class="mt-1 text-xs text-gray-500">sudah diselesaikan</p></article>
        <article class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-center justify-between"><span class="text-sm text-gray-500">Rata-rata</span><i class="ri-line-chart-line text-lg text-primary"></i></div><p class="mt-3 text-2xl font-bold text-gray-900">{{ number_format($attemptSummary['average'], 0) }}</p><p class="mt-1 text-xs text-gray-500">nilai keseluruhan</p></article>
        <article class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-center justify-between"><span class="text-sm text-gray-500">Tertinggi</span><i class="ri-medal-line text-lg text-primary"></i></div><p class="mt-3 text-2xl font-bold text-gray-900">{{ number_format($attemptSummary['highest'], 0) }}</p><p class="mt-1 text-xs text-gray-500">pencapaian terbaik</p></article>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start"><div><div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="ri-line-chart-line"></i></span><h2 class="font-bold text-gray-900">Perkembangan nilai</h2></div><p class="mt-2 text-sm text-gray-500">{{ $attemptTrendChart['label'] }}</p></div>@if($attemptTrendChart['last_score'] !== null)<span class="rounded-lg bg-primary/10 px-3 py-1.5 text-sm font-bold text-primary">Nilai terakhir {{ number_format($attemptTrendChart['last_score'], 0) }}</span>@endif</div>
        @if($attemptTrendChart['points'] !== [])
            <div class="mt-5 overflow-x-auto"><svg class="h-52 min-w-[520px] w-full" viewBox="0 0 640 210" role="img" aria-label="Grafik perkembangan nilai tryout"><defs><linearGradient id="attempt-trend-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="currentColor" stop-opacity=".2"/><stop offset="100%" stop-color="currentColor" stop-opacity="0"/></linearGradient></defs><path d="M28 32H612M28 82H612M28 132H612M28 184H612" class="stroke-slate-100" stroke-width="1" fill="none"/><polygon points="{{ $attemptTrendChart['area'] }}" class="fill-primary/20"/><polyline points="{{ $attemptTrendChart['polyline'] }}" class="stroke-primary" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>@foreach($attemptTrendChart['points'] as $point)<circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" class="fill-white stroke-primary" stroke-width="3"/><text x="{{ $point['x'] }}" y="204" text-anchor="middle" class="fill-slate-400" font-size="10">{{ $point['label'] }}</text>@endforeach</svg></div>
        @endif
    </section>

    <div class="grid gap-5 xl:grid-cols-3">
        <section class="rounded-xl border border-slate-200 bg-white xl:col-span-2">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-gray-900">Seluruh percobaan</h2><p class="mt-1 text-sm text-gray-500">Nilai dan akurasi pada setiap tryout yang diselesaikan.</p></div>
            <div class="divide-y divide-slate-100">
                @foreach($attempts as $attempt)
                    <article class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($attempt->finished_at)->translatedFormat('d F Y, H:i') }}</p><p class="mt-1 text-sm text-gray-500">{{ $attempt->correct_answers }}/{{ $attempt->total_questions }} jawaban benar</p></div><div class="flex items-center gap-3"><span class="rounded-lg bg-primary/10 px-3 py-1.5 text-sm font-bold text-primary">{{ number_format($attempt->score, 0) }}</span></div></article>
                @endforeach
            </div>
            @if($attempts->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $attempts->links() }}</div>@endif
        </section>

        <aside class="space-y-5">
            <section class="rounded-xl border border-slate-200 bg-white p-5"><div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="ri-message-3-line"></i></span><h2 class="font-bold text-gray-900">Feedback tutor</h2></div><div class="mt-4 space-y-4">@forelse($feedback as $item)<article class="border-l-2 border-primary/30 pl-3"><p class="font-semibold text-gray-800">{{ $item->title }}</p><p class="mt-1 text-xs text-gray-500">{{ $item->tentor?->name ?? 'Tutor' }} · {{ $item->created_at?->translatedFormat('d M Y') }}</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $item->feedback }}</p></article>@empty<p class="text-sm leading-6 text-gray-500">Belum ada feedback tutor yang dibagikan untuk anak ini.</p>@endforelse</div></section>
            <section class="rounded-xl border border-slate-200 bg-white p-5"><div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="ri-file-chart-line"></i></span><h2 class="font-bold text-gray-900">Laporan perkembangan</h2></div><div class="mt-4 space-y-4">@forelse($progress as $item)<article class="border-l-2 border-slate-200 pl-3"><p class="font-semibold text-gray-800">{{ $item->title }}</p><p class="mt-1 text-xs text-gray-500">{{ $item->period_start?->translatedFormat('d M') }}–{{ $item->period_end?->translatedFormat('d M Y') }}</p><p class="mt-2 line-clamp-3 text-sm leading-6 text-gray-600">{{ $item->summary }}</p></article>@empty<p class="text-sm leading-6 text-gray-500">Belum ada laporan perkembangan dari tutor.</p>@endforelse</div></section>
        </aside>
    </div>
</div>
@endsection
