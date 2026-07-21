@extends('admin.layout.admin')

@section('title', 'Absensi Tutor')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-gray-500">Absensi untuk</p>
            <h1 class="text-2xl font-bold text-gray-900">{{ $tentor->name }}</h1>
            <p class="text-sm text-gray-500">Kelola absensi Anda dan siswa untuk setiap jadwal mengajar.</p>
        </div>
        <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">Kehadiran bulan ini: <strong>{{ $monthAttendances }} sesi</strong></div>
    </div>

    <nav class="flex overflow-x-auto border-b border-gray-200" aria-label="Tampilan absensi">
        <a href="{{ route('tutor.attendance.index', ['tab' => 'schedule']) }}"
            class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ $activeTab === 'schedule' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
            @if($activeTab === 'schedule') aria-current="page" @endif>
            <i class="ri-calendar-schedule-line mr-1"></i>Per Jadwal
        </a>
        <a href="{{ route('tutor.attendance.index', ['tab' => 'latest']) }}"
            class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ $activeTab === 'latest' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
            @if($activeTab === 'latest') aria-current="page" @endif>
            <i class="ri-sort-desc mr-1"></i>Terbaru
        </a>
    </nav>

    @if($activeTab === 'schedule')
        @forelse($sessionsBySchedule as $sessions)
            @php($schedule = $sessions->first()->schedule)
            <a href="{{ route('tutor.attendance.schedule.show', $schedule) }}" class="group flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary hover:shadow-md sm:flex-row sm:items-center sm:gap-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-primary">Jadwal mengajar</p>
                    <h2 class="mt-1 text-lg font-bold text-gray-900 group-hover:text-primary">{{ $schedule?->title ?? $sessions->first()->class?->title ?? 'Kelas' }}</h2>
                    <p class="mt-2 flex items-center gap-1 text-sm text-gray-500"><i class="ri-group-line"></i> {{ $sessions->first()->studyGroup?->name ?? 'Tanpa rombel' }}</p>
                    <p class="mt-1 flex items-center gap-1 text-xs text-gray-500"><i class="ri-calendar-event-line"></i> Sesi berikutnya: {{ $sessions->first()->start_at->locale('id')->translatedFormat('d M Y, H:i') }}</p>
                </div>
                <div class="mt-4 flex items-center gap-3 sm:mt-0">
                    <span class="rounded-full bg-primary/10 px-3 py-1.5 text-xs font-bold text-primary">{{ $sessions->count() }} sesi</span>
                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-primary">Buka <i class="ri-arrow-right-line text-lg"></i></span>
                </div>
            </a>
        @empty
            @include('tutor.partials.attendance-empty')
        @endforelse
    @else
        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <header class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                <h2 class="font-bold text-gray-900">Sesi terbaru</h2>
                <p class="mt-0.5 text-xs text-gray-500">Diurutkan dari jadwal yang paling baru.</p>
            </header>
            <div class="divide-y divide-gray-100">
                @forelse($latestSessions as $session)
                    @include('tutor.partials.attendance-session', ['showDate' => true])
                @empty
                    @include('tutor.partials.attendance-empty')
                @endforelse
            </div>
        </section>
    @endif
</div>

@include('tutor.partials.attendance-modal')
@endsection
