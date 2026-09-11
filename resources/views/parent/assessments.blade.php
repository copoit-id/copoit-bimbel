@extends('parent.layout')

@section('title', 'Riwayat Ujian')

@section('content')
<div class="space-y-5">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-primary">Evaluasi belajar</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Riwayat ujian</h1>
            <p class="mt-1 text-sm text-gray-500">Rangkuman setiap tryout, tren nilai, dan seluruh percobaan anak.</p>
        </div>
        @if($child)
            <span class="inline-flex w-fit items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700">
                <i class="ri-user-line text-primary"></i>{{ $child->name }}
            </span>
        @endif
    </header>

    @if($child)
        <section class="grid gap-3 sm:grid-cols-3">
            <article class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between"><span class="text-sm text-gray-500">Tryout selesai</span><i class="ri-file-list-3-line text-lg text-primary"></i></div>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ $assessmentSummary['completed'] }}</p>
                <p class="mt-1 text-xs text-gray-500">percobaan tercatat</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between"><span class="text-sm text-gray-500">Rata-rata nilai</span><i class="ri-line-chart-line text-lg text-primary"></i></div>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ number_format($assessmentSummary['average_score'], 0) }}</p>
                <p class="mt-1 text-xs text-gray-500">dari semua tryout</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between"><span class="text-sm text-gray-500">Nilai tertinggi</span><i class="ri-medal-line text-lg text-primary"></i></div>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ number_format($assessmentSummary['highest_score'], 0) }}</p>
                <p class="mt-1 text-xs text-gray-500">pencapaian terbaik</p>
            </article>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                <div>
                    <div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="ri-line-chart-line"></i></span><h2 class="font-bold text-gray-900">Tren nilai tryout</h2></div>
                    <p class="mt-2 text-sm text-gray-500">{{ $assessmentTrendChart['label'] }}</p>
                </div>
                @if($assessmentTrendChart['last_score'] !== null)
                    <span class="rounded-lg bg-primary/10 px-3 py-1.5 text-sm font-bold text-primary">{{ number_format($assessmentTrendChart['last_score'], 0) }}</span>
                @endif
            </div>
            @if($assessmentTrendChart['points'] !== [])
                <div class="mt-5 overflow-x-auto">
                    <svg class="h-52 min-w-[520px] w-full" viewBox="0 0 640 210" role="img" aria-label="Grafik tren nilai tryout">
                        <defs><linearGradient id="assessment-trend-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#6366f1" stop-opacity=".30"/><stop offset="100%" stop-color="#a855f7" stop-opacity=".02"/></linearGradient></defs>
                        <path d="M28 32H612M28 82H612M28 132H612M28 184H612" class="stroke-slate-100" stroke-width="1" fill="none"/>
                        <polygon points="{{ $assessmentTrendChart['area'] }}" fill="url(#assessment-trend-fill)"/>
                        <polyline points="{{ $assessmentTrendChart['polyline'] }}" stroke="#4f46e5" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        @foreach($assessmentTrendChart['points'] as $point)
                            <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" fill="#ffffff" stroke="#4f46e5" stroke-width="3"/>
                            <text x="{{ $point['x'] }}" y="204" text-anchor="middle" fill="#94a3b8" font-size="10">{{ $point['label'] }}</text>
                        @endforeach
                    </svg>
                </div>
            @else
                <div class="mt-5 flex h-36 items-center justify-center rounded-lg bg-slate-50 text-sm text-gray-500">Grafik akan muncul setelah anak menyelesaikan tryout.</div>
            @endif
        </section>

        <section class="rounded-xl border border-slate-200 bg-white">
            <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div><h2 class="font-bold text-gray-900">Daftar tryout</h2><p class="mt-1 text-sm text-gray-500">Pilih tryout untuk melihat setiap percobaan, perkembangan, dan feedback tutor.</p></div>
                <span class="text-sm font-semibold text-gray-500">{{ $assessmentItems->total() }} tryout</span>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($assessmentItems as $item)
                    <a href="{{ route('parent.assessments.detail', ['anak' => $child->id, 'tryout' => $item->tryout_id]) }}" class="group flex flex-col gap-3 px-5 py-4 transition-colors hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="ri-file-list-3-line text-lg"></i></span>
                            <div class="min-w-0"><p class="truncate font-semibold text-gray-900 group-hover:text-primary">{{ $item->tryout_name }}</p><p class="mt-1 text-xs text-gray-500">{{ $item->attempt_count }} percobaan · Terakhir {{ \Carbon\Carbon::parse($item->last_finished_at)->translatedFormat('d M Y') }}</p></div>
                        </div>
                        <div class="flex items-center gap-4 sm:text-right"><div><p class="text-lg font-bold text-gray-900">{{ number_format($item->average_score, 0) }}</p><p class="text-xs text-gray-500">rata-rata nilai</p></div><i class="ri-arrow-right-s-line text-xl text-gray-400 group-hover:text-primary"></i></div>
                    </a>
                @empty
                    <div class="px-5 py-12 text-center"><i class="ri-file-list-3-line text-3xl text-slate-300"></i><p class="mt-3 text-sm text-gray-500">Belum ada tryout yang diselesaikan.</p></div>
                @endforelse
            </div>
            @if($assessmentItems->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $assessmentItems->links() }}</div>@endif
        </section>
    @else
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center"><i class="ri-user-search-line text-4xl text-slate-300"></i><p class="mt-3 font-semibold text-gray-700">Pilih anak terlebih dahulu</p><p class="mt-1 text-sm text-gray-500">Riwayat ujian akan tampil setelah anak dipilih.</p></div>
    @endif
</div>
@endsection
