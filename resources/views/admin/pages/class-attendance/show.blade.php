@extends('admin.layout.admin')

@section('title', 'Absensi Kelas')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Absensi Kelas</h1>
        <p class="text-sm text-gray-500">
            {{ $session->class->title ?? '-' }} • {{ $session->start_at->translatedFormat('l, d M Y H:i') }}
        </p>
        <p class="mt-1 text-xs text-gray-500">
            Kategori tujuan:
            {{ $session->schedule->destinationCategories->map(fn($category) => $category->display_name)->implode(', ') ?: 'Fallback peserta dari akses paket' }}
        </p>
    </div>

    @if($session->tentor)
        @php($tutorAttendance = $session->tutorAttendance)
        @php($approvalStatus = $tutorAttendance?->approval_status ?? 'pending')
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-900">Absensi Tutor: {{ $session->tentor->name }}</p>
                    <p class="text-xs text-gray-500">{{ $tutorAttendance?->check_in_at?->format('d M Y H:i') ?? 'Belum melakukan absensi' }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @if($tutorAttendance)
                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">Status: {{ ucfirst($tutorAttendance->status) }}</span>
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $approvalStatus === 'approved' ? 'bg-green-100 text-green-700' : ($approvalStatus === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                {{ $approvalStatus === 'approved' ? 'Disetujui admin' : ($approvalStatus === 'rejected' ? 'Ditolak admin' : 'Menunggu persetujuan') }}
                            </span>
                            @if($tutorAttendance->photo_path)
                                <a href="{{ Storage::url($tutorAttendance->photo_path) }}" target="_blank" class="text-xs font-semibold text-primary hover:underline">Lihat bukti foto</a>
                            @endif
                        @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.class-attendance.tutor.mark', $session) }}" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <select name="status" class="rounded-lg border border-gray-200 px-2 py-1 text-sm">
                        @foreach(['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Tidak Hadir', 'excused' => 'Izin'] as $key => $label)
                            <option value="{{ $key }}" @selected(($tutorAttendance->status ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input name="notes" value="{{ $tutorAttendance?->notes }}" class="rounded-lg border border-gray-200 px-2 py-1 text-sm" placeholder="Catatan (opsional)">
                    <button class="rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white">Setujui & Hitung Gaji</button>
                </form>
            </div>
        </div>
    @endif

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr>
                    <th class="px-4 py-3">Peserta</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Foto</th>
                    <th class="px-4 py-3">Set Admin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($participants as $participant)
                    <?php $attendance = $attendances->get($participant->id); ?>
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $participant->name }}</p>
                            <p class="text-xs text-gray-500">{{ $participant->email }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs font-medium {{ $attendance ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $attendance ? ucfirst($attendance->status) : 'Belum absen' }}
                            </span>
                            @if($attendance)
                                <p class="mt-1 text-xs text-gray-400">via {{ $attendance->source }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $attendance?->check_in_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if($attendance?->photo_path)
                                <a href="{{ Storage::url($attendance->photo_path) }}" target="_blank" class="text-primary hover:underline">Lihat foto</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.class-attendance.mark', $session) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $participant->id }}">
                                <select name="status" class="rounded-lg border border-gray-200 px-2 py-1 text-xs">
                                    @foreach(['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Tidak Hadir', 'excused' => 'Izin'] as $key => $label)
                                        <option value="{{ $key }}" @selected(($attendance->status ?? '') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-lg bg-primary px-3 py-1 text-xs font-semibold text-white">Simpan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada peserta untuk rombel ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
