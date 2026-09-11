@extends('parent.layout')

@section('title', 'Ringkasan Anak')

@section('content')
@if(! $child)
    <section class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-2xl text-primary"><i class="ri-user-search-line"></i></span>
        <h1 class="mt-4 text-xl font-bold text-gray-900">Belum ada anak yang terhubung</h1>
        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">Minta Admin menautkan akun Orang Tua ini ke akun siswa agar progres belajar dapat dipantau.</p>
    </section>
@else
    <div class="space-y-5">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-primary">Ringkasan belajar</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Perkembangan {{ $child->name }}</h1>
                <p class="mt-1 text-sm text-gray-500">Pantau performa belajar, kehadiran, dan catatan tutor dalam satu tempat.</p>
            </div>
            <a href="{{ route('parent.report', ['anak' => $child->id]) }}" class="inline-flex w-fit items-center gap-2 rounded-lg border border-primary px-4 py-2.5 text-sm font-semibold text-primary transition-colors hover:bg-primary/5"><i class="ri-file-chart-line"></i>Laporan belajar</a>
        </header>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_220px] lg:items-center">
                <div class="flex items-start gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary text-xl font-bold text-white">{{ strtoupper(mb_substr($child->name, 0, 1)) }}</span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2"><h2 class="text-lg font-bold text-gray-900">{{ $child->name }}</h2><span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold {{ $childAccountStatus['classes'] }}"><i class="{{ $childAccountStatus['icon'] }}"></i>{{ $childAccountStatus['label'] }}</span></div>
                        <p class="mt-1 text-sm text-gray-500">{{ $activePackages->count() }} paket aktif · {{ $assessmentSummary['completed'] }} ujian telah diselesaikan</p>
                        <div class="mt-4 h-2 max-w-xl overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-primary" style="width: {{ $attendanceSummary['rate'] ?? 0 }}%"></div></div>
                        <p class="mt-2 text-xs font-medium text-gray-500">Konsistensi kehadiran: {{ $attendanceSummary['rate'] ?? '—' }}{{ $attendanceSummary['rate'] !== null ? '%' : '' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 border-t border-slate-100 pt-4 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                    <div class="relative h-20 w-20 shrink-0">
                        <svg class="h-20 w-20 -rotate-90" viewBox="0 0 36 36" aria-hidden="true">
                            <path class="fill-none stroke-slate-100" stroke-width="4" d="M18 2.5a15.5 15.5 0 1 1 0 31a15.5 15.5 0 1 1 0-31"></path>
                            <path class="fill-none stroke-primary" stroke-linecap="round" stroke-width="4" pathLength="100" stroke-dasharray="{{ $attendanceSummary['rate'] ?? 0 }} 100" d="M18 2.5a15.5 15.5 0 1 1 0 31a15.5 15.5 0 1 1 0-31"></path>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-sm font-bold text-gray-900">{{ $attendanceSummary['rate'] ?? '—' }}{{ $attendanceSummary['rate'] !== null ? '%' : '' }}</span>
                    </div>
                    <div><p class="text-xs font-bold uppercase tracking-[0.12em] text-gray-400">Kehadiran</p><p class="mt-1 text-sm font-semibold text-gray-800">{{ $attendanceSummary['present'] }} sesi hadir</p><p class="mt-1 text-xs leading-5 text-gray-500">{{ $childAccountStatus['description'] }}</p></div>
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-start justify-between"><span class="text-sm font-medium text-gray-500">Kehadiran</span><i class="ri-calendar-check-line text-lg text-emerald-600"></i></div><p class="mt-4 text-3xl font-bold text-gray-900">{{ $attendanceSummary['rate'] ?? '—' }}{{ $attendanceSummary['rate'] !== null ? '%' : '' }}</p><p class="mt-2 text-xs text-gray-500">{{ $attendanceSummary['present'] }} dari {{ $attendanceSummary['total'] }} sesi hadir</p></article>
            <article class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-start justify-between"><span class="text-sm font-medium text-gray-500">Rata-rata nilai</span><i class="ri-line-chart-line text-lg text-primary"></i></div><p class="mt-4 text-3xl font-bold text-gray-900">{{ $assessmentSummary['completed'] ? number_format($assessmentSummary['average_score'], 1) : '—' }}</p><p class="mt-2 text-xs text-gray-500">dari {{ $assessmentSummary['completed'] }} ujian selesai</p></article>
            <article class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-start justify-between"><span class="text-sm font-medium text-gray-500">Nilai tertinggi</span><i class="ri-medal-line text-lg text-amber-500"></i></div><p class="mt-4 text-3xl font-bold text-gray-900">{{ $assessmentSummary['completed'] ? number_format($assessmentSummary['highest_score'], 1) : '—' }}</p><p class="mt-2 text-xs text-gray-500">pencapaian terbaik anak</p></article>
            <article class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-start justify-between"><span class="text-sm font-medium text-gray-500">Paket aktif</span><i class="ri-book-open-line text-lg text-violet-600"></i></div><p class="mt-4 text-3xl font-bold text-gray-900">{{ $activePackages->count() }}</p><p class="mt-2 text-xs text-gray-500">program belajar berjalan</p></article>
        </section>

        @if($alerts->isNotEmpty())
            <section class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                <div class="flex items-start gap-3"><i class="ri-error-warning-line mt-0.5 text-lg text-amber-600"></i><div><h2 class="font-semibold text-amber-900">Perlu perhatian</h2><div class="mt-1 space-y-1">@foreach($alerts as $alert)<p class="text-sm text-amber-800">{{ $alert['text'] }}</p>@endforeach</div></div></div>
            </section>
        @endif

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)]">
            <article class="overflow-hidden rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[0.12em] text-primary">Analitik performa</p><h2 class="mt-1 font-bold text-gray-900">Tren nilai</h2><p class="mt-1 text-sm text-gray-500">Perubahan skor dari ujian yang telah diselesaikan.</p></div><div class="flex items-center gap-3"><span class="rounded-lg bg-primary/10 px-3 py-2 text-right"><span class="block text-[11px] font-semibold text-primary">Tren</span><span class="block text-lg font-bold text-primary">{{ $scoreTrendChart['change_label'] }}</span></span><a href="{{ route('parent.assessments', ['anak' => $child->id]) }}" class="text-sm font-semibold text-primary hover:underline">Lihat riwayat</a></div></div>
                @if($scoreTrendChart['points'] !== [])
                    <div class="mt-5 rounded-xl border border-primary/10 bg-gradient-to-b from-primary/5 to-white px-2 py-3 sm:px-4">
                        <svg viewBox="0 0 640 210" class="h-52 w-full overflow-visible" role="img" aria-label="Grafik tren nilai {{ $child->name }}">
                            <defs><linearGradient id="parent-score-area" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#6366f1" stop-opacity=".30"/><stop offset="100%" stop-color="#a855f7" stop-opacity=".02"/></linearGradient></defs>
                            <path d="M28 26H612M28 105H612M28 184H612" class="stroke-slate-200" stroke-dasharray="4 6" fill="none"></path>
                            <polygon points="{{ $scoreTrendChart['area'] }}" fill="url(#parent-score-area)"></polygon>
                            <polyline points="{{ $scoreTrendChart['polyline'] }}" fill="none" stroke="#4f46e5" stroke-linecap="round" stroke-linejoin="round" stroke-width="4"></polyline>
                            @foreach($scoreTrendChart['points'] as $point)
                                <g><circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="6" fill="#ffffff" stroke="#4f46e5" stroke-width="4"><title>{{ $point['name'] }} · {{ number_format($point['score'], 1) }}</title></circle><text x="{{ $point['x'] }}" y="{{ $point['y'] - 13 }}" text-anchor="middle" fill="#475569" font-size="11" font-weight="700">{{ number_format($point['score'], 0) }}</text><text x="{{ $point['x'] }}" y="204" text-anchor="middle" fill="#94a3b8" font-size="10">{{ $point['label'] }}</text></g>
                            @endforeach
                        </svg>
                    </div>
                @else
                    <div class="mt-5 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-12 text-center text-sm text-gray-500">Belum ada hasil ujian untuk ditampilkan.</div>
                @endif
            </article>

            <article class="rounded-xl border border-slate-200 bg-white p-5"><div class="flex items-start justify-between gap-3"><div><h2 class="font-bold text-gray-900">Jadwal terdekat</h2><p class="mt-1 text-sm text-gray-500">Sesi yang sudah disetujui tutor.</p></div><i class="ri-calendar-event-line text-xl text-primary"></i></div><div class="mt-4 divide-y divide-slate-100">@forelse($upcomingBookings as $booking)<div class="py-3 first:pt-0"><p class="font-semibold text-gray-800">{{ $booking->package?->name ?? 'Sesi bimbingan' }}</p><p class="mt-1 text-sm text-gray-500">{{ $booking->scheduled_start_at?->translatedFormat('D, d M · H:i') }} · {{ $booking->tentor?->name ?? 'Tutor' }}</p></div>@empty<p class="py-10 text-center text-sm text-gray-500">Belum ada jadwal mendatang.</p>@endforelse</div></article>
        </section>

        <section class="grid gap-5 xl:grid-cols-2">
            <article class="rounded-xl border border-slate-200 bg-white p-5"><div class="flex items-center justify-between gap-3"><div><h2 class="font-bold text-gray-900">Feedback tutor terbaru</h2><p class="mt-1 text-sm text-gray-500">Catatan yang terlihat untuk orang tua dan siswa.</p></div><a href="{{ route('parent.assessments', ['anak' => $child->id]) }}" class="text-sm font-semibold text-primary hover:underline">Buka riwayat</a></div><div class="mt-4 divide-y divide-slate-100">@forelse($recentFeedback as $feedback)<div class="py-4 first:pt-0"><div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-gray-800">{{ $feedback->title }}</p><p class="mt-1 text-xs text-gray-500">{{ $feedback->tentor?->name ?? 'Tutor' }} · {{ $feedback->created_at?->translatedFormat('d M Y') }}</p></div><i class="ri-message-3-line text-primary"></i></div><p class="mt-2 line-clamp-2 text-sm leading-6 text-gray-600">{{ $feedback->feedback }}</p></div>@empty<p class="py-10 text-center text-sm text-gray-500">Belum ada feedback dari tutor.</p>@endforelse</div></article>
            <article class="rounded-xl border border-slate-200 bg-white p-5"><div class="flex items-center justify-between"><div><h2 class="font-bold text-gray-900">Hasil ujian terbaru</h2><p class="mt-1 text-sm text-gray-500">Ringkasan performa terakhir anak.</p></div><i class="ri-file-list-3-line text-xl text-primary"></i></div><div class="mt-4 divide-y divide-slate-100">@forelse($recentAnswers as $answer)<div class="flex items-center justify-between gap-3 py-3 first:pt-0"><div class="min-w-0"><p class="truncate font-semibold text-gray-800">{{ $answer->tryout_name }}</p><p class="mt-1 text-xs text-gray-500">{{ \Carbon\Carbon::parse($answer->finished_at)->translatedFormat('d M Y') }}</p></div><span class="rounded-lg bg-primary/10 px-3 py-1.5 text-sm font-bold text-primary">{{ number_format((float) $answer->score, 1) }}</span></div>@empty<p class="py-10 text-center text-sm text-gray-500">Belum ada ujian yang selesai.</p>@endforelse</div></article>
        </section>
    </div>
@endif
@endsection
