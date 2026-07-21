@extends('admin.layout.admin')

@section('title', 'Absensi Kelas')

@section('content')
@php
    $attendanceStatusLabels = [
        'present' => 'Hadir',
        'late' => 'Terlambat',
        'absent' => 'Tidak Hadir',
        'excused' => 'Izin',
    ];
    $attendanceStatusClasses = [
        'present' => 'bg-green-100 text-green-700',
        'late' => 'bg-amber-100 text-amber-700',
        'absent' => 'bg-red-100 text-red-700',
        'excused' => 'bg-blue-100 text-blue-700',
    ];
@endphp
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('tutor.attendance.index') }}" class="text-sm font-semibold text-primary hover:underline">← Kembali ke daftar absensi</a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $session->schedule?->title ?? $session->class?->title ?? 'Sesi Kelas' }}</h1>
            <p class="text-sm text-gray-500">{{ $session->start_at->locale('id')->translatedFormat('l, d M Y H:i') }}</p>
        </div>
        <div class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600">
            {{ $canManageStudentAttendance ? 'Absensi siswa sedang dibuka.' : 'Absensi siswa belum dibuka atau sudah ditutup.' }}
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr><th class="px-4 py-3">Siswa</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Perbarui</th></tr>
            </thead>
            <tbody>
                @forelse($participants as $participant)
                    @php($attendance = $attendances->get($participant->id))
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3"><p class="font-medium text-gray-900">{{ $participant->name }}</p><p class="text-xs text-gray-500">{{ $participant->email }}</p></td>
                        <td class="px-4 py-3">
                            @if($attendance)
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $attendanceStatusClasses[$attendance->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $attendanceStatusLabels[$attendance->status] ?? ucfirst($attendance->status) }}
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Belum absen</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($canManageStudentAttendance)
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-lg border border-primary px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary hover:text-white"
                                    onclick="openStudentAttendanceModal(@js($participant->id), @js($participant->name), @js($attendance?->status))">
                                    <i class="{{ $attendance ? 'ri-edit-line' : 'ri-checkbox-circle-line' }}"></i>
                                    {{ $attendance ? 'Edit' : 'Absen' }}
                                </button>
                            @else
                                <span class="text-xs text-gray-400">Belum tersedia</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-10 text-center text-gray-500">Belum ada siswa pada sesi ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="student-attendance-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="student-attendance-title">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="student-attendance-title" class="text-lg font-bold text-gray-900">Absensi Siswa</h2>
                <p id="student-attendance-name" class="mt-1 text-sm text-gray-500"></p>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-700" onclick="closeStudentAttendanceModal()" aria-label="Tutup"><i class="ri-close-line text-xl"></i></button>
        </div>
        <form method="POST" action="{{ route('tutor.attendance.students.mark', $session) }}" class="mt-5 space-y-4">
            @csrf
            <input id="student-attendance-user-id" type="hidden" name="user_id">
            <div>
                <label for="student-attendance-status" class="mb-2 block text-sm font-medium text-gray-700">Status kehadiran</label>
                <select id="student-attendance-status" name="status" class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    @foreach($attendanceStatusLabels as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50" onclick="closeStudentAttendanceModal()">Batal</button>
                <button id="student-attendance-submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan Absensi</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openStudentAttendanceModal(userId, studentName, status) {
        document.getElementById('student-attendance-user-id').value = userId;
        document.getElementById('student-attendance-name').textContent = studentName;
        document.getElementById('student-attendance-status').value = status || 'present';
        document.getElementById('student-attendance-submit').textContent = status ? 'Simpan Perubahan' : 'Simpan Absensi';
        document.getElementById('student-attendance-modal').classList.remove('hidden');
        document.getElementById('student-attendance-modal').classList.add('flex');
    }

    function closeStudentAttendanceModal() {
        document.getElementById('student-attendance-modal').classList.add('hidden');
        document.getElementById('student-attendance-modal').classList.remove('flex');
    }
</script>
@endpush
