@extends('tutor.layout')

@section('title', 'Jadwal Tutor')

@section('content')
<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
        <p class="text-sm text-gray-500">Jadwal mengajar</p>
        <h1 class="text-2xl font-bold text-gray-900">{{ $tentor->name }}</h1>
        @if($scheduleRange === 'week')
            <p class="text-sm text-gray-500">{{ $weekDates->first()->locale('id')->translatedFormat('d M') }} - {{ $weekDates->last()->locale('id')->translatedFormat('d M Y') }}</p>
        @elseif($scheduleRange === 'month')
            <p class="text-sm text-gray-500">{{ now()->locale('id')->translatedFormat('F Y') }}</p>
        @elseif($scheduleRange === 'today')
            <p class="text-sm text-gray-500">Jadwal hari ini</p>
        @else
            <p class="text-sm text-gray-500">Semua jadwal</p>
        @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-tab :tabs="$scheduleTabs" variant="pills" class="max-w-full overflow-x-auto" />
            @if($canManageBookings)
                <a href="{{ route('tutor.booking.index') }}" class="rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary hover:text-white"><i class="ri-calendar-schedule-line mr-1"></i>Booking</a>
            @endif
            @if($canManageSchedule)
                <a href="{{ route('tutor.schedule.create') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90"><i class="ri-add-line mr-1"></i>Tambah sesi</a>
            @endif
        </div>
    </div>

    @if($scheduleRange === 'today')
        <section class="space-y-4">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-primary">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</p><h2 class="mt-1 text-xl font-bold text-gray-900">Jadwal hari ini</h2><p class="mt-1 text-sm text-gray-500">Kelola absensi, feedback peserta, dan catatan progress internal langsung dari sesi yang dijadwalkan.</p></div>
            @forelse($todaySessions as $session)
                @php
                    $attendanceSetting = $session->schedule?->attendanceSetting;
                    $attendanceOpensAt = $session->start_at->copy()->subMinutes($attendanceSetting?->open_minutes_before ?? 30);
                    $attendanceClosesAt = ($session->end_at ?? $session->start_at)->copy()->addMinutes($attendanceSetting?->close_minutes_after ?? 60);
                    $canMarkOwnAttendance = $session->status === 'scheduled' && now()->between($attendanceOpensAt, $attendanceClosesAt) && ! $session->tutorAttendance;
                    $actionButtonClass = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-primary px-4 py-2.5 text-sm font-semibold text-primary transition hover:bg-primary hover:text-white';
                @endphp
                <article class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-sm font-bold text-primary">{{ $session->start_at->format('H:i') }}{{ $session->end_at ? ' – '.$session->end_at->format('H:i') : '' }} WIB</p><h3 class="mt-1 text-lg font-bold text-gray-900">{{ $session->schedule?->title ?? $session->class?->title ?? 'Sesi belajar' }}</h3><p class="mt-2 text-sm text-gray-500"><i class="ri-group-line mr-1"></i>{{ $session->studyGroup?->name ?? 'Sesi personal' }}</p>@if($session->location)<p class="mt-1 text-sm text-gray-500"><i class="ri-map-pin-line mr-1"></i>{{ $session->location }}</p>@elseif($session->meeting_url)<a href="{{ $session->meeting_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-block text-sm font-semibold text-primary hover:underline"><i class="ri-video-chat-line mr-1"></i>Online meeting</a>@endif</div><span class="w-fit rounded-full px-3 py-1 text-xs font-bold {{ $session->status === 'scheduled' ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-600' }}">{{ $session->status === 'scheduled' ? 'Terjadwal' : ucfirst($session->status) }}</span></div>
                    <div class="mt-5 flex flex-wrap gap-2 border-t border-gray-100 pt-4">@if($session->tutorAttendance)<span class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-500"><i class="ri-checkbox-circle-line"></i>Sudah absen</span>@elseif($canMarkOwnAttendance)<button type="button" class="{{ $actionButtonClass }}" onclick="openTutorAttendanceModal(@js(route('tutor.attendance.mark', $session)), @js($session->schedule?->title ?? 'Sesi belajar'), 'schedule_today')"><i class="ri-user-check-line"></i>Absensi Saya</button>@else<button type="button" disabled class="inline-flex min-h-11 cursor-not-allowed items-center justify-center gap-2 rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-400"><i class="ri-time-line"></i>Absensi belum dibuka</button>@endif<a href="{{ route('tutor.attendance.show', $session) }}#absensi-peserta" class="{{ $actionButtonClass }}"><i class="ri-team-line"></i>Absensi Peserta</a>@if($session->studyGroup?->package && in_array($session->studyGroup->package->tutor_payment_frequency, \App\Services\TutorPackagePaymentService::BILLING_FREQUENCIES, true) && $session->studyGroup->package->price)<form method="POST" action="{{ route('tutor.package-payments.prepare', $session) }}">@csrf<button class="{{ $actionButtonClass }}"><i class="ri-hand-coin-line"></i>Pembayaran siswa</button></form>@endif<a href="{{ route('tutor.schedule.feedback.create', $session) }}" class="{{ $actionButtonClass }}"><i class="ri-message-3-line"></i>Feedback</a><a href="{{ route('tutor.schedule.progress.create', $session) }}" class="{{ $actionButtonClass }}"><i class="ri-line-chart-line"></i>{{ $session->has_session_progress ? 'Lihat progress' : 'Catatan progress' }}</a></div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center text-sm text-gray-500"><i class="ri-calendar-event-line mb-3 block text-4xl text-gray-300"></i>Tidak ada jadwal untuk hari ini.</div>
            @endforelse
        </section>
    @elseif($scheduleRange === 'week')
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-7">
        @foreach($weekDates as $dayNumber => $date)
            <section class="flex min-h-[250px] flex-col overflow-hidden rounded-lg border border-gray-200 bg-white">
                <header class="border-b border-gray-200 bg-gray-50 px-3 py-2.5 text-gray-800">
                    <p class="text-sm font-bold tracking-wide">{{ $dayLabels[$dayNumber] }}</p>
                    <p class="text-xs text-gray-500">{{ $date->locale('id')->translatedFormat('d M') }}</p>
                </header>
                <div class="flex-1 space-y-2 bg-slate-50/20 p-2">
                    @forelse($weeklySessions->get($dayNumber, collect()) as $session)
                        <article class="rounded-lg border border-gray-200 bg-white p-3">
                            <p class="text-xs font-semibold text-primary">{{ $session->start_at->format('H:i') }}{{ $session->end_at ? ' - '.$session->end_at->format('H:i') : '' }}</p>
                            <h2 class="mt-1 text-sm font-bold leading-snug text-gray-900">{{ $session->schedule?->title ?? $session->class?->title ?? 'Kelas' }}</h2>
                            @if($session->bookingRequest)
                                <a href="{{ route('tutor.booking.index', ['status' => 'approved']) }}" class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-primary hover:underline"><i class="ri-calendar-schedule-line"></i>Booking · {{ $session->bookingRequest->user?->name ?? 'Siswa' }}</a>
                            @endif
                            <p class="mt-1 flex items-center gap-1 text-[11px] text-gray-500"><i class="ri-group-line"></i>{{ $session->studyGroup?->name ?? 'Tanpa rombel' }}</p>
                            @if($session->location)
                                <p class="mt-1 flex items-center gap-1 text-[11px] text-gray-500"><i class="ri-map-pin-line"></i>{{ $session->location }}</p>
                            @elseif($session->meeting_url)
                                <a href="{{ $session->meeting_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 flex items-center gap-1 text-[11px] font-medium text-primary hover:underline"><i class="ri-video-chat-line"></i>Online meeting</a>
                            @endif
                            @if($canManageSchedule && $session->status === 'scheduled' && $session->start_at->isFuture())
                                <form method="POST" action="{{ route('tutor.schedule.cancel', $session) }}" class="mt-3" onsubmit="return confirm('Batalkan sesi ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-[11px] font-semibold text-red-600 hover:underline">Batalkan sesi</button>
                                </form>
                            @elseif($session->status === 'cancelled')
                                <p class="mt-2 text-[11px] font-semibold text-red-600">Dibatalkan</p>
                            @endif
                        </article>
                    @empty
                        <div class="flex h-full min-h-36 flex-col items-center justify-center rounded-lg border border-dashed border-gray-200 bg-slate-50/30 px-2 text-center text-xs text-gray-400"><i class="ri-calendar-event-line mb-1 text-lg opacity-50"></i>Belum ada jadwal</div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
    @elseif($scheduleRange === 'month')
    <div class="grid grid-cols-7 gap-px overflow-hidden rounded-lg border border-gray-200 bg-gray-200">
        @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dayLabel)
            <div class="bg-gray-50 px-2 py-2 text-center text-xs font-bold text-gray-500">{{ $dayLabel }}</div>
        @endforeach
        @foreach($monthDates as $date)
            @php $dateKey = $date->toDateString(); $isCurrentMonth = $date->month === $monthStart->month; @endphp
            <section class="min-h-32 bg-white p-2 {{ $isCurrentMonth ? '' : 'bg-gray-50/70 text-gray-400' }}">
                <p class="text-xs font-bold {{ $date->isToday() ? 'inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary text-white' : '' }}">{{ $date->format('d') }}</p>
                <div class="mt-2 space-y-1">
                    @foreach($monthSessions->get($dateKey, collect()) as $session)
                        <article class="rounded border border-gray-200 bg-white p-1.5">
                            <p class="text-[10px] font-bold text-primary">{{ $session->start_at->format('H:i') }}</p>
                            <p class="mt-0.5 line-clamp-2 text-[10px] font-semibold leading-tight text-gray-800">{{ $session->schedule?->title ?? $session->class?->title ?? 'Kelas' }}</p>
                            @if($session->bookingRequest)<p class="mt-0.5 text-[9px] font-semibold text-primary">Booking</p>@endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
    @else
    <div class="space-y-10">
        @forelse($allMonths as $calendarMonth)
            <section aria-labelledby="schedule-month-{{ $loop->index }}">
                <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Agenda mengajar</p>
                        <h2 id="schedule-month-{{ $loop->index }}" class="mt-1 text-xl font-bold tracking-tight text-gray-900">{{ $calendarMonth['label'] }}</h2>
                    </div>
                    <span class="rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-600">{{ $calendarMonth['session_count'] }} sesi</span>
                </div>

                <x-ui.card variant="flat" padding="none" class="overflow-visible rounded-2xl border border-gray-200 shadow-sm">
                    <div class="divide-y divide-gray-100">
                        @foreach($calendarMonth['days'] as $day)
                            <section class="relative px-4 py-5 sm:px-6" aria-label="{{ $day['day_name'] }}, {{ $day['date_label'] }} — {{ $day['state_label'] }}">
                                @if(! $loop->last)
                                    <span class="absolute bottom-0 left-[2.35rem] top-[4.8rem] w-px bg-gray-100 sm:left-[3.35rem]"></span>
                                @endif

                                <div class="relative grid grid-cols-[3.25rem_minmax(0,1fr)] gap-3 sm:grid-cols-[4.5rem_minmax(0,1fr)] sm:gap-5">
                                    <div class="flex flex-col items-center">
                                        <div class="flex h-14 w-14 flex-col items-center justify-center rounded-2xl border text-center sm:h-16 sm:w-16 {{ $day['date_class'] }}">
                                            <span class="text-lg font-bold leading-none">{{ $day['day_number'] }}</span>
                                            <span class="mt-1 text-[10px] font-semibold uppercase tracking-wide">{{ Illuminate\Support\Str::substr($day['day_name'], 0, 3) }}</span>
                                        </div>
                                        <span class="relative mt-3 h-2.5 w-2.5 rounded-full {{ $day['timeline_class'] }}"></span>
                                    </div>

                                    <div class="min-w-0 pb-1">
                                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                            <div>
                                                <h3 class="text-sm font-bold text-gray-900">{{ $day['day_name'] }}</h3>
                                                <p class="mt-0.5 text-xs text-gray-500">{{ $day['date_label'] }}</p>
                                            </div>
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $day['state_class'] }}">{{ $day['state_label'] }}</span>
                                        </div>

                                        <div class="space-y-2.5">
                                            @foreach($day['sessions'] as $session)
                                                <article class="group rounded-xl border border-gray-200 bg-white p-3.5 transition duration-200 hover:border-primary/30 hover:shadow-md sm:flex sm:items-center sm:gap-5 sm:p-4">
                                                    <div class="flex shrink-0 items-center gap-2 sm:block sm:w-20">
                                                        <p class="text-sm font-bold tabular-nums text-gray-900">{{ $session->start_at->format('H:i') }}</p>
                                                        <p class="text-xs text-gray-500 sm:mt-0.5">{{ $session->end_at ? '– '.$session->end_at->format('H:i') : 'WIB' }}{{ $session->end_at ? ' WIB' : '' }}</p>
                                                    </div>

                                                    <div class="mt-3 min-w-0 flex-1 sm:mt-0">
                                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                                            <h4 class="text-sm font-bold leading-snug text-gray-900">{{ $session->schedule?->title ?? $session->class?->title ?? 'Sesi belajar' }}</h4>
                                                            @if($session->status === 'cancelled')
                                                                <span class="rounded-full bg-rose-50 px-2 py-1 text-[10px] font-bold text-rose-700">Dibatalkan</span>
                                                            @elseif($session->bookingRequest)
                                                                <span class="rounded-full bg-primary/10 px-2 py-1 text-[10px] font-bold text-primary">Booking</span>
                                                            @endif
                                                        </div>
                                                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-gray-500">
                                                            <span class="inline-flex items-center gap-1.5"><i class="ri-group-line text-gray-400"></i>{{ $session->studyGroup?->name ?? 'Sesi personal' }}</span>
                                                            @if($session->location)
                                                                <span class="inline-flex items-center gap-1.5"><i class="ri-map-pin-line text-gray-400"></i>{{ $session->location }}</span>
                                                            @elseif($session->meeting_url)
                                                                <a href="{{ $session->meeting_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 font-semibold text-primary hover:underline"><i class="ri-video-chat-line"></i>Online meeting</a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </article>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </section>
                        @endforeach
                    </div>
                </x-ui.card>
            </section>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">Belum ada jadwal.</div>
        @endforelse
    </div>
    @endif
</div>
@if($scheduleRange === 'today')
    @include('tutor.partials.attendance-modal')
@endif
@endsection
