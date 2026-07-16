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
    $sessionsByDay = $sessions->getCollection()->groupBy(fn ($session) => $session->start_at->dayOfWeekIso);
    $liveClassesByDay = $liveClasses->groupBy(fn ($class) => $class->schedule_time->dayOfWeekIso);
@endphp

<div class="space-y-6">
    <div class="rounded-2xl border border-gray-100 bg-white p-6">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Jadwal Kelas</h1>
        <p class="mt-1 text-sm text-gray-500">Lihat jadwal rutin dan kelas yang dapat kamu ikuti pada minggu ini maupun minggu berikutnya.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-7">
        @foreach($days as $dayNumber => $dayName)
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
                        @php
                            $attendance = $session->attendances->first();
                            $setting = $session->schedule->attendanceSetting;
                            $openAt = $session->start_at->copy()->subMinutes($setting?->open_minutes_before ?? 15);
                            $closeAt = ($session->end_at ?? $session->start_at)->copy()->addMinutes($setting?->close_minutes_after ?? 30);
                            $canAttend = now()->between($openAt, $closeAt) && !$attendance;
                            $tentorName = $session->tentor?->name ?? $session->schedule?->tentor?->name ?? $session->class?->tentor?->name ?? $session->class?->mentor;
                        @endphp

                        <article class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-xs font-semibold text-primary">{{ $session->start_at->format('d M') }}</p>
                                <span class="rounded-md bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold text-primary">{{ $session->start_at->format('H:i') }}</span>
                            </div>
                            <h3 class="mt-1.5 text-sm font-bold leading-snug text-gray-900">{{ $session->schedule?->title ?? $session->class?->title ?? 'Kelas' }}</h3>
                            @if($session->end_at)
                                <p class="mt-1 text-[11px] text-gray-500">s.d. {{ $session->end_at->format('H:i') }} WIB</p>
                            @endif
                            @if($tentorName)
                                <p class="mt-1 flex items-center gap-1 text-[11px] text-gray-500"><i class="ri-user-star-line"></i>{{ $tentorName }}</p>
                            @endif

                            <div class="mt-3 border-t border-gray-100 pt-2.5">
                                @if($attendance)
                                    <p class="flex items-center gap-1 text-[11px] font-medium text-emerald-700"><i class="ri-checkbox-circle-fill"></i>Sudah absen</p>
                                @elseif($canAttend)
                                    <form method="POST" action="{{ route('user.class-schedule.attend', $session) }}" enctype="multipart/form-data" class="space-y-2">
                                        @csrf
                                        @if(($setting?->mode ?? 'button') === 'photo')
                                            <input type="file" name="photo" accept="image/*" required class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-[10px]">
                                        @endif
                                        <button class="w-full rounded-lg bg-primary px-2 py-1.5 text-xs font-semibold text-white">Absen</button>
                                    </form>
                                @else
                                    <p class="text-[11px] text-gray-500">Absen {{ $openAt->format('H:i') }}–{{ $closeAt->format('H:i') }}</p>
                                @endif

                                @if($session->meeting_url)
                                    <a href="{{ $session->meeting_url }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-primary hover:underline"><i class="ri-video-chat-line"></i>Buka meeting</a>
                                @endif
                            </div>
                        </article>
                    @endforeach

                    @foreach($dayLiveClasses as $class)
                        <article class="rounded-xl border border-blue-100 bg-blue-50/40 p-3 shadow-sm">
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

    @if($sessions->isEmpty() && $liveClasses->isEmpty())
        <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center text-gray-500">
            Belum ada jadwal kelas yang dapat kamu ikuti.
        </div>
    @endif

    {{ $sessions->links() }}
</div>
@endsection
