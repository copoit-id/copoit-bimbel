@extends('admin.layout.admin')

@section('title', 'Dashboard Tutor')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-gray-500">Selamat datang,</p>
            <h1 class="text-2xl font-bold text-gray-900">{{ $tentor->name }}</h1>
            <p class="text-sm text-gray-500">{{ $tentor->expertise ?: 'Tutor' }}</p>
        </div>
        <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">
            Kehadiran bulan ini: <strong>{{ $monthAttendances }} sesi</strong>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr>
                    <th class="px-4 py-3">Sesi</th>
                    <th class="px-4 py-3">Rombel</th>
                    <th class="px-4 py-3">Kehadiran Saya</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $session)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">{{ $session->class?->title ?? 'Kelas' }}</p>
                            <p class="text-xs text-gray-500">{{ $session->start_at->translatedFormat('D, d M Y H:i') }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $session->studyGroup?->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $session->tutorAttendance ? ucfirst($session->tutorAttendance->status) : 'Belum absen' }}</td>
                        <td class="px-4 py-3 text-right"><a class="font-semibold text-primary hover:underline" href="{{ route('tutor.sessions.show', $session) }}">Buka absensi</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-500">Belum ada sesi kelas yang ditugaskan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $sessions->links() }}
</div>
@endsection
