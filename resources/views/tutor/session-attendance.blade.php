@extends('admin.layout.admin')

@section('title', 'Absensi Kelas')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('tutor.attendance.index') }}" class="text-sm font-semibold text-primary hover:underline">← Kembali ke daftar absensi</a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $session->class?->title ?? 'Sesi Kelas' }}</h1>
            <p class="text-sm text-gray-500">{{ $session->start_at->locale('id')->translatedFormat('l, d M Y H:i') }}</p>
        </div>
        <div class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600">
            {{ $session->tutorAttendance ? 'Anda sudah absen: ' . ucfirst($session->tutorAttendance->status) : 'Absensi Anda dapat dilakukan dari daftar absensi.' }}
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
                        <td class="px-4 py-3">{{ $attendance ? ucfirst($attendance->status) : 'Belum absen' }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('tutor.attendance.students.mark', $session) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $participant->id }}">
                                <select name="status" class="rounded-lg border border-gray-200 px-2 py-1 text-xs">
                                    @foreach(['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Tidak Hadir', 'excused' => 'Izin'] as $key => $label)
                                        <option value="{{ $key }}" @selected(($attendance->status ?? '') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-lg border border-primary px-3 py-1 text-xs font-semibold text-primary hover:bg-primary hover:text-white">Simpan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-10 text-center text-gray-500">Belum ada siswa pada sesi ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
