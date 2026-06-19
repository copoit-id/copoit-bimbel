@extends('admin.layout.admin')

@section('title', 'Detail Jadwal')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $classSchedule->title }}</h1>
            <p class="text-sm text-gray-500">{{ $classSchedule->schedule_type === 'recurring' ? 'Jadwal Rutin Mingguan' : 'Jadwal Sekali Jalan' }}</p>
            <p class="mt-1 text-xs text-gray-500">
                Kategori tujuan:
                {{ $classSchedule->destinationCategories->map(fn($category) => $category->display_name)->implode(', ') ?: '-' }}
            </p>
        </div>
        <form method="POST" action="{{ route('admin.class-schedules.generate', $classSchedule) }}">
            @csrf
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                Generate Absen
            </button>
        </form>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Jam</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Absensi</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $session)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $session->session_date->translatedFormat('l, d M Y') }}</td>
                        <td class="px-4 py-3">{{ $session->start_at->format('H:i') }}{{ $session->end_at ? ' - ' . $session->end_at->format('H:i') : '' }}</td>
                        <td class="px-4 py-3">{{ ucfirst($session->status) }}</td>
                        <td class="px-4 py-3">{{ $session->attendances_count }} data</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <a href="{{ route('admin.class-attendance.show', $session) }}" class="text-primary hover:underline">Kelola Absensi</a>
                                <form method="POST" action="{{ route('admin.class-sessions.update', $session) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" class="rounded-lg border border-gray-200 px-2 py-1 text-xs">
                                        @foreach(['scheduled' => 'Scheduled', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label)
                                            <option value="{{ $key }}" @selected($session->status === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-lg border border-gray-200 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50">Update</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada data absen. Klik generate absen.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $sessions->links() }}
</div>
@endsection
