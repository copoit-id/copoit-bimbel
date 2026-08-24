@php
    $attendance = $session->tutorAttendance;
    $setting = $session->schedule?->attendanceSetting;
    $openAt = $session->start_at->copy()->subMinutes($setting?->open_minutes_before ?? 30);
    $closeAt = ($session->end_at ?? $session->start_at)->copy()->addMinutes($setting?->close_minutes_after ?? 60);
    $isAttendanceOpen = $session->status === 'scheduled' && now()->between($openAt, $closeAt);
    $canAttend = $isAttendanceOpen && ! $attendance;
    $sessionTitle = $session->schedule?->title ?? $session->class?->title ?? 'Kelas';
@endphp
<article class="flex flex-col gap-4 p-4 lg:flex-row lg:items-center lg:justify-between">
    <div>
        @if($showDate ?? false)
            <p class="text-xs font-medium text-gray-500">{{ $session->start_at->locale('id')->translatedFormat('l, d F Y') }}</p>
        @endif
        <p class="mt-1 text-sm font-semibold text-primary">{{ $session->start_at->format('H:i') }}{{ $session->end_at ? ' - ' . $session->end_at->format('H:i') : '' }}</p>
        <h3 class="mt-1 font-bold text-gray-900">{{ $sessionTitle }}</h3>
        <p class="mt-1 text-sm text-gray-500">Rombel: {{ $session->studyGroup?->name ?? '-' }}</p>
        @if($attendance)
            <p class="mt-1 text-xs text-green-700">Anda sudah absen {{ ucfirst($attendance->status) }} pada {{ $attendance->check_in_at?->format('H:i') }}.</p>
            <p class="mt-1 text-xs {{ $attendance->approval_status === 'approved' ? 'text-green-700' : 'text-amber-700' }}">
                {{ $attendance->approval_status === 'approved' ? 'Kehadiran sudah disetujui admin.' : 'Menunggu persetujuan admin.' }}
            </p>
        @elseif(! $canAttend)
            <p class="mt-1 text-xs text-gray-500">Absensi tutor dibuka {{ $openAt->format('H:i') }} - {{ $closeAt->format('H:i') }}.</p>
        @endif
    </div>
    <div class="flex flex-wrap gap-2">
        @if($canAttend)
            <button type="button" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90" onclick="openTutorAttendanceModal(@js(route('tutor.attendance.mark', $session)), @js($sessionTitle))">Absen Saya</button>
        @elseif($attendance)
            <span class="rounded-lg bg-green-50 px-4 py-2 text-sm font-semibold text-green-700">Sudah Absen</span>
        @else
            <button type="button" disabled class="cursor-not-allowed rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-400">Absen Saya</button>
        @endif
        @if($isAttendanceOpen)
            <a href="{{ route('tutor.attendance.show', $session) }}" class="rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary hover:text-white">Absen Siswa</a>
        @endif
    </div>
</article>
