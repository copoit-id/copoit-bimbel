@extends('admin.layout.admin')
@section('title', 'Laporan Pengguna')

@section('styles')
<style>
    .report-hero { background: linear-gradient(120deg, #312e81 0%, #4f46e5 48%, #7c3aed 100%); overflow: hidden; position: relative; }
    .report-hero::before, .report-hero::after { background: rgba(255,255,255,.1); border-radius: 999px; content: ''; position: absolute; }
    .report-hero::before { height: 16rem; right: -4rem; top: -9rem; width: 16rem; }
    .report-hero::after { bottom: -7rem; height: 13rem; left: 38%; width: 13rem; }
    .report-hero-content { position: relative; z-index: 1; }
    .report-avatar { box-shadow: 0 12px 28px rgba(15, 23, 42, .28); }
    .stat-card { background: #fff; border: 1px solid rgba(226, 232, 240, .9); box-shadow: 0 8px 24px rgba(15, 23, 42, .05); transition: transform .2s ease, box-shadow .2s ease; }
    .stat-card:hover { box-shadow: 0 16px 30px rgba(79, 70, 229, .13); transform: translateY(-3px); }
    .history-row { border: 1px solid #edf0f5; box-shadow: 0 2px 7px rgba(15, 23, 42, .025); transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease; }
    .history-row:hover { border-color: #c7d2fe; box-shadow: 0 10px 22px rgba(79, 70, 229, .09); transform: translateY(-1px); }
    .activity-timeline { position: relative; }
    .activity-timeline::before { background: linear-gradient(#c7d2fe, #e5e7eb); bottom: 2rem; content: ''; left: 1.15rem; position: absolute; top: 2rem; width: 2px; }
    .activity-item { position: relative; }
    .activity-item:hover .activity-copy { background: #f8faff; border-color: #dbe4ff; }
    .activity-copy { border: 1px solid transparent; transition: background .2s ease, border-color .2s ease; }
</style>
@endsection

@section('content')
@php
    $activityStyles = [
        'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600'],
        'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
        'amber' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-600'],
    ];
    $statCards = [
        ['label' => 'Total tryout', 'value' => $statistics['total_tryouts'], 'icon' => 'ri-file-list-3-line', 'color' => 'bg-indigo-50 text-indigo-600'],
        ['label' => 'Rata-rata nilai', 'value' => $statistics['avg_score'], 'icon' => 'ri-bar-chart-box-line', 'color' => 'bg-sky-50 text-sky-600'],
        ['label' => 'Sertifikat', 'value' => $statistics['total_certificates'], 'icon' => 'ri-award-line', 'color' => 'bg-amber-50 text-amber-600'],
        ['label' => 'Waktu belajar', 'value' => $statistics['study_hours'] . ' jam', 'icon' => 'ri-timer-flash-line', 'color' => 'bg-emerald-50 text-emerald-600'],
    ];
@endphp

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.user.index') }}" title="Manajemen User" />
            <x-breadcrumb-item href="" title="Laporan User" />
        </x-slot>
    </x-breadcrumb>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.user.report.download-excel', $user->id) }}" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:-translate-y-0.5 hover:bg-emerald-100">
            <i class="ri-file-excel-2-line text-base"></i> Excel
        </a>
        <a href="{{ route('admin.user.report.download', $user->id) }}" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-primary/90 hover:shadow-lg">
            <i class="ri-file-pdf-2-line text-base"></i> PDF
        </a>
    </div>
</div>

<div class="mt-5 flex flex-col gap-1">
    <p class="text-xs font-bold uppercase tracking-[0.18em] text-primary">Laporan pengguna</p>
    <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Perjalanan belajar {{ $user->name }}</h1>
    <p class="text-sm text-gray-500">Rekaman lengkap progres tryout, sertifikat, dan aktivitas pengguna.</p>
</div>

<section class="report-hero mt-6 rounded-2xl px-6 py-7 text-white sm:px-8">
    <div class="report-hero-content flex flex-col gap-6 sm:flex-row sm:items-center">
        <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=fff&color=4f46e5&size=128&bold=true"
            alt="Avatar {{ $user->name }}" class="report-avatar h-20 w-20 rounded-2xl border-4 border-white/30 sm:h-24 sm:w-24">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="truncate text-2xl font-bold sm:text-3xl">{{ $user->name }}</h2>
                <span class="rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur-sm">{{ $user->userAnswers->where('created_at', '>', now()->subDays(30))->isNotEmpty() ? 'Aktif 30 hari terakhir' : 'Tidak ada aktivitas 30 hari' }}</span>
            </div>
            <p class="mt-1 truncate text-sm text-indigo-100">{{ $user->email }}</p>
            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-indigo-100">
                <span class="inline-flex items-center gap-1.5"><i class="ri-calendar-check-line"></i>Bergabung {{ $user->created_at->format('d M Y') }}</span>
                <span class="inline-flex items-center gap-1.5"><i class="ri-refresh-line"></i>Data diperbarui {{ $user->updated_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>
</section>

<div class="relative z-10 -mt-4 grid grid-cols-2 gap-3 px-3 sm:grid-cols-4 sm:px-6">
    @foreach($statCards as $stat)
    <div class="stat-card rounded-xl p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2"><p class="text-xs font-medium text-gray-500">{{ $stat['label'] }}</p><span class="flex h-8 w-8 items-center justify-center rounded-lg {{ $stat['color'] }}"><i class="{{ $stat['icon'] }}"></i></span></div>
        <p class="mt-2 text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">{{ $stat['value'] }}</p>
    </div>
    @endforeach
</div>

<section class="mt-8 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
    <div class="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
        <div><div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><i class="ri-history-line"></i></span><h3 class="text-lg font-bold text-gray-900">Riwayat Pengerjaan</h3></div><p class="mt-2 text-sm text-gray-500">Semua tryout yang sudah diselesaikan, diurutkan dari yang terbaru.</p></div>
        <span class="w-fit rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700">{{ $tryoutHistory->count() }} riwayat</span>
    </div>

    @if($tryoutHistory->isNotEmpty())
    <div class="mt-5 space-y-3">
        @foreach($tryoutHistory as $tryout)
        <article class="history-row grid gap-4 rounded-xl p-4 lg:grid-cols-[128px_minmax(0,1fr)_auto_auto_auto] lg:items-center lg:gap-5">
            <div class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600"><p class="font-semibold text-slate-800">{{ $tryout['date']->format('d M Y') }}</p><p class="mt-0.5 text-xs">{{ $tryout['date']->format('H:i') }} WIB</p></div>
            <div class="min-w-0"><p class="font-semibold text-gray-900">{{ $tryout['name'] }}</p>@if($tryout['section'])<p class="mt-1 text-xs text-gray-500">{{ $tryout['section'] }}</p>@endif</div>
            <div class="flex items-center gap-2 lg:block"><span class="text-xs text-gray-500 lg:block">Nilai</span><strong class="text-lg text-indigo-600">{{ $tryout['score'] }}</strong></div>
            <div class="text-sm text-gray-600"><span class="text-xs text-gray-500 lg:block">Pengerjaan</span>{{ $tryout['correct_answers'] }}/{{ $tryout['total_questions'] }} benar · {{ $tryout['duration'] === null ? '—' : $tryout['duration'] . ' menit' }}</div>
            <div><span class="rounded-full px-3 py-1.5 text-xs font-bold {{ $tryout['is_passed'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">{{ $tryout['is_passed'] ? 'Lulus' : 'Belum lulus' }}</span></div>
        </article>
        @endforeach
    </div>
    @else
    <div class="py-12 text-center text-gray-500"><i class="ri-file-list-3-line text-4xl text-indigo-200"></i><p class="mt-3 text-sm">Belum ada riwayat tryout yang selesai.</p></div>
    @endif
</section>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-5">
    <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm xl:col-span-2 sm:p-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-5"><div><div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600"><i class="ri-award-line"></i></span><h3 class="font-bold text-gray-900">Sertifikat</h3></div><p class="mt-2 text-sm text-gray-500">Dokumen yang sudah diterbitkan.</p></div><span class="text-sm font-bold text-amber-600">{{ $certificates->count() }}</span></div>
        <div class="mt-2 divide-y divide-slate-100">
            @forelse($certificates as $certificate)
            <div class="py-4"><p class="font-semibold text-gray-800">{{ $certificate->certificate_name }}</p><div class="mt-1 flex flex-wrap gap-x-2 text-xs text-gray-500"><span>{{ optional($certificate->issued_date)->format('d M Y') ?? 'Tanggal tidak tersedia' }}</span><span class="text-slate-300">•</span><span>{{ $certificate->certificate_number }}</span></div></div>
            @empty
            <div class="py-10 text-center"><i class="ri-award-line text-3xl text-amber-200"></i><p class="mt-2 text-sm text-gray-500">Belum ada sertifikat.</p></div>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm xl:col-span-3 sm:p-6">
        <div class="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between"><div><div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600"><i class="ri-route-line"></i></span><h3 class="font-bold text-gray-900">Timeline Aktivitas</h3></div><p class="mt-2 text-sm text-gray-500">Seluruh aktivitas tercatat, terbaru di atas.</p></div><span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $activities->count() }} aktivitas</span></div>
        <div class="activity-timeline mt-5 space-y-3">
            @foreach($activities as $activity)
            @php($style = $activityStyles[$activity['color']] ?? $activityStyles['indigo'])
            <div class="activity-item flex gap-3">
                <div class="z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-4 ring-white {{ $style['bg'] }}"><i class="{{ $activity['icon'] }} {{ $style['text'] }}"></i></div>
                <div class="activity-copy min-w-0 flex-1 rounded-xl px-3 py-2.5"><p class="text-sm font-medium text-gray-800">{{ $activity['text'] }}</p><p class="mt-1 text-xs text-gray-500">{{ $activity['date']->format('d M Y, H:i') }} WIB</p></div>
            </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
