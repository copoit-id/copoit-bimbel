@extends('admin.layout.admin')

@section('title', 'Absensi Tutor')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-gray-500">Absensi untuk</p>
            <h1 class="text-2xl font-bold text-gray-900">{{ $tentor->name }}</h1>
            <p class="text-sm text-gray-500">Pilih sesi untuk mencatat kehadiran Anda atau siswa.</p>
        </div>
        <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">Kehadiran bulan ini: <strong>{{ $monthAttendances }} sesi</strong></div>
    </div>

    @forelse($sessionsByDate as $date => $sessions)
        @php
            $dateLabel = \Carbon\Carbon::parse($date);
        @endphp
        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <header class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                <h2 class="font-bold text-gray-900">{{ $dateLabel->locale('id')->translatedFormat('l, d F Y') }}</h2>
            </header>
            <div class="divide-y divide-gray-100">
                @foreach($sessions as $session)
                    @php
                        $attendance = $session->tutorAttendance;
                        $setting = $session->schedule?->attendanceSetting;
                        $openAt = $session->start_at->copy()->subMinutes($setting?->open_minutes_before ?? 30);
                        $closeAt = ($session->end_at ?? $session->start_at)->copy()->addMinutes($setting?->close_minutes_after ?? 60);
                        $canAttend = $session->status === 'scheduled' && ! $attendance && now()->between($openAt, $closeAt);
                    @endphp
                    <article class="flex flex-col gap-4 p-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-primary">{{ $session->start_at->format('H:i') }}{{ $session->end_at ? ' - ' . $session->end_at->format('H:i') : '' }}</p>
                            <h3 class="mt-1 font-bold text-gray-900">{{ $session->class?->title ?? 'Kelas' }}</h3>
                            <p class="mt-1 text-sm text-gray-500">Rombel: {{ $session->studyGroup?->name ?? '-' }}</p>
                            @if($attendance)
                                <p class="mt-1 text-xs text-green-700">Anda sudah absen {{ ucfirst($attendance->status) }} pada {{ $attendance->check_in_at?->format('H:i') }}.</p>
                            @elseif(! $canAttend)
                                <p class="mt-1 text-xs text-gray-500">Absensi tutor dibuka {{ $openAt->format('H:i') }} - {{ $closeAt->format('H:i') }}.</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if($canAttend)
                                <button type="button" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90" onclick="openTutorAttendanceModal(@js(route('tutor.attendance.mark', $session)), @js($session->class?->title ?? 'Kelas'))">Absen Saya</button>
                            @elseif($attendance)
                                <span class="rounded-lg bg-green-50 px-4 py-2 text-sm font-semibold text-green-700">Sudah Absen</span>
                            @else
                                <button type="button" disabled class="cursor-not-allowed rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-400">Absen Saya</button>
                            @endif
                            <a href="{{ route('tutor.attendance.show', $session) }}" class="rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary hover:text-white">Absen Siswa</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-lg border border-dashed border-gray-200 bg-white px-4 py-12 text-center text-gray-500">Belum ada sesi kelas dalam 30 hari ke depan.</div>
    @endforelse
</div>

<div id="tutor-attendance-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="tutor-attendance-title">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="tutor-attendance-title" class="text-lg font-bold text-gray-900">Absen Kehadiran Saya</h2>
                <p id="tutor-attendance-session" class="mt-1 text-sm text-gray-500"></p>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-700" onclick="closeTutorAttendanceModal()" aria-label="Tutup"><i class="ri-close-line text-xl"></i></button>
        </div>
        <form id="tutor-attendance-form" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
            @csrf
            <div>
                <label for="tutor-attendance-photo" class="mb-2 block text-sm font-medium text-gray-700">Foto kehadiran</label>
                <input id="tutor-attendance-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" capture="user" required class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-500">Ambil atau pilih foto (JPG, PNG, atau WebP; maks. 5 MB).</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50" onclick="closeTutorAttendanceModal()">Batal</button>
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Hadir</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openTutorAttendanceModal(action, sessionTitle) {
        document.getElementById('tutor-attendance-form').action = action;
        document.getElementById('tutor-attendance-session').textContent = sessionTitle;
        document.getElementById('tutor-attendance-modal').classList.remove('hidden');
        document.getElementById('tutor-attendance-modal').classList.add('flex');
    }

    function closeTutorAttendanceModal() {
        document.getElementById('tutor-attendance-modal').classList.add('hidden');
        document.getElementById('tutor-attendance-modal').classList.remove('flex');
    }
</script>
@endpush
