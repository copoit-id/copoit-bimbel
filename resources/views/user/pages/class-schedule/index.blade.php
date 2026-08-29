@extends('user.layout.new-user')

@section('title', 'Jadwal Kelas')

@section('content')
@php
    $days = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];
    $periodTabs = [
        'today' => 'Hari Ini',
        'week' => 'Minggu Ini',
        'month' => 'Bulan Ini',
        'all' => 'Semua',
    ];
    $periodDescriptions = [
        'today' => 'Jadwal kelas untuk hari ini.',
        'week' => 'Jadwal kelas dari Senin sampai Minggu pada minggu ini.',
        'month' => 'Jadwal kelas pada bulan ini.',
        'all' => 'Seluruh jadwal kelas yang dapat kamu ikuti.',
    ];
    $visibleDays = $period === 'today'
        ? [now()->dayOfWeekIso => $days[now()->dayOfWeekIso]]
        : $days;
    $sessionsByDay = $sessions->getCollection()->groupBy(fn ($session) => $session->start_at->dayOfWeekIso);
    $liveClassesByDay = $liveClasses->groupBy(fn ($class) => $class->schedule_time->dayOfWeekIso);
    $periodQuery = fn (string $periodKey): array => array_filter([
        'period' => $periodKey,
        'package_id' => $selectedPackageId,
    ]);
@endphp

<div class="space-y-6">
    <div class="rounded-2xl border border-gray-100 bg-white p-6">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Jadwal Kelas</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $periodDescriptions[$period] }}</p>
        @if($selectedPackageId)
            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                <span class="rounded-full bg-primary/10 px-3 py-1.5 font-semibold text-primary"><i class="ri-filter-3-line mr-1"></i>Menampilkan jadwal dari paket yang dipilih</span>
                <a href="{{ route('user.class-schedule.index', ['period' => $period]) }}" class="font-semibold text-gray-500 hover:text-primary">Lihat semua jadwal</a>
            </div>
        @endif

        <nav class="mt-5 flex flex-wrap gap-2" aria-label="Filter periode jadwal">
            @foreach($periodTabs as $periodKey => $periodLabel)
                <a href="{{ route('user.class-schedule.index', $periodQuery($periodKey)) }}"
                    @if($period === $periodKey) aria-current="page" @endif
                    class="rounded-lg border px-4 py-2 text-sm font-semibold transition-colors {{ $period === $periodKey
                        ? 'border-primary bg-primary text-white'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-primary/40 hover:text-primary' }}">
                    {{ $periodLabel }}
                </a>
            @endforeach
        </nav>
    </div>

    @if($sessions->isNotEmpty() || $liveClasses->isNotEmpty())
    <div class="grid grid-cols-1 gap-4 {{ $period === 'today' ? 'lg:grid-cols-1' : 'lg:grid-cols-7' }}">
        @foreach($visibleDays as $dayNumber => $dayName)
            @php
                $daySessions = $sessionsByDay->get($dayNumber, collect());
                $dayLiveClasses = $liveClassesByDay->get($dayNumber, collect());
            @endphp

            <section class="flex min-h-[260px] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <header class="border-b border-gray-200 bg-gray-50 px-3 py-3">
                    <h2 class="text-sm font-bold tracking-wide text-gray-800">{{ $dayName }}</h2>
                </header>

                <div class="flex-1 space-y-3 bg-slate-50/40 p-2.5">
                    @foreach($daySessions as $session)
                        <article class="rounded-xl border border-gray-200 bg-white p-3">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-xs font-semibold text-primary">{{ $session->start_at->format('d M') }}</p>
                                <span class="rounded-md bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold text-primary">{{ $session->start_at->format('H:i') }}</span>
                            </div>
                            <h3 class="mt-1.5 text-sm font-bold leading-snug text-gray-900">{{ $session->schedule?->title ?? $session->class?->title ?? 'Kelas' }}</h3>
                            @if($session->end_at)
                                <p class="mt-1 text-[11px] text-gray-500">s.d. {{ $session->end_at->format('H:i') }} WIB</p>
                            @endif
                            @if($session->tentor_name)
                                <p class="mt-1 flex items-center gap-1 text-[11px] text-gray-500"><i class="ri-user-star-line"></i>{{ $session->tentor_name }}</p>
                            @endif
                            @if($session->location)
                                <p class="mt-1 flex items-center gap-1 text-[11px] text-gray-500"><i class="ri-map-pin-line"></i>{{ $session->location }}</p>
                            @endif

                            @if($canUseAttendance || ($canUseClass && $session->meeting_url) || $session->can_chat_tutor)
                                <div class="mt-3 border-t border-gray-100 pt-2.5">
                                    @if($canUseAttendance)
                                    @if($session->user_attendance)
                                        <p class="flex items-center gap-1 text-[11px] font-medium text-emerald-700"><i class="ri-checkbox-circle-fill"></i>Sudah absen</p>
                                    @elseif($session->can_user_attend)
                                        <form method="POST" action="{{ route('user.class-schedule.attend', $session) }}" enctype="multipart/form-data" class="space-y-2">
                                            @csrf
                                            @if($session->attendance_mode === \App\Models\AttendanceSetting::MODE_PHOTO)
                                                <input type="file" name="photo" accept="image/*" required class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-[10px]">
                                            @endif
                                            <button class="w-full rounded-lg bg-primary px-2 py-1.5 text-xs font-semibold text-white">Absen</button>
                                        </form>
                                    @else
                                        <p class="text-[11px] text-gray-500">Absen {{ $session->attendance_open_at->format('H:i') }}–{{ $session->attendance_close_at->format('H:i') }}</p>
                                    @endif
                                    @endif

                                    @if($canUseClass && $session->meeting_url)
                                        <a href="{{ $session->meeting_url }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-primary hover:underline"><i class="ri-video-chat-line"></i>Buka meeting</a>
                                    @endif

                                    @if($session->can_chat_tutor)
                                        <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-tutor-chat'))" class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-sky-700 hover:underline"><i class="ri-chat-3-line"></i>Chat Tutor</button>
                                    @endif
                                </div>
                            @endif
                        </article>
                    @endforeach

                    @foreach($dayLiveClasses as $class)
                        <article class="rounded-xl border border-blue-100 bg-blue-50/40 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-xs font-semibold text-primary">{{ $class->schedule_time->format('d M') }}</p>
                                <span class="rounded-md bg-white px-1.5 py-0.5 text-[10px] font-bold text-primary">{{ $class->schedule_time->format('H:i') }}</span>
                            </div>
                            <h3 class="mt-1.5 text-sm font-bold leading-snug text-gray-900">{{ $class->title }}</h3>
                            @if($class->tentor?->name || $class->mentor)
                                <p class="mt-1 flex items-center gap-1 text-[11px] text-gray-500"><i class="ri-user-star-line"></i>{{ $class->tentor?->name ?? $class->mentor }}</p>
                            @endif
                            @if($class->zoom_link)
                                <a href="{{ $class->zoom_link }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-1 text-[11px] font-semibold text-primary hover:underline"><i class="ri-video-chat-line"></i>Buka meeting</a>
                            @endif
                        </article>
                    @endforeach

                    @if($daySessions->isEmpty() && $dayLiveClasses->isEmpty())
                        <div class="flex min-h-36 flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-white/60 px-3 py-6 text-center text-xs text-gray-400">
                            <i class="ri-calendar-event-line mb-1 text-lg"></i>
                            <span>Belum ada jadwal</span>
                        </div>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    {{ $sessions->links() }}
    @else
        <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center text-gray-500">
            Belum ada jadwal kelas untuk periode {{ strtolower($periodTabs[$period]) }}.
        </div>
    @endif
</div>
@endsection
