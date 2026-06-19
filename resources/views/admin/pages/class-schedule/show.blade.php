@extends('admin.layout.admin')

@section('title', 'Absensi Jadwal')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $classSchedule->title }}</h1>
            <p class="text-sm text-gray-500">
                {{ $classSchedule->schedule_type === 'recurring' ? 'Jadwal Rutin Mingguan' : 'Jadwal Sekali Jalan' }}
                @if($classSchedule->start_time)
                    • {{ substr($classSchedule->start_time, 0, 5) }}{{ $classSchedule->end_time ? ' - ' . substr($classSchedule->end_time, 0, 5) : '' }}
                @endif
            </p>
            <p class="mt-1 text-xs text-gray-500">
                Kategori tujuan:
                {{ $classSchedule->destinationCategories->map(fn($category) => $category->display_name)->implode(', ') ?: '-' }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.class-schedules.edit', $classSchedule) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <i class="ri-edit-line mr-1"></i>Edit Jadwal
            </a>
            <form method="POST" action="{{ route('admin.class-schedules.generate', $classSchedule) }}">
                @csrf
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                    Generate Absen
                </button>
            </form>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-end">
            <form method="GET" action="{{ route('admin.class-schedules.show', $classSchedule) }}">
                <label class="mb-2 block text-sm font-semibold text-gray-700">Atau Pilih Sesi</label>
                <select name="session_id" onchange="this.form.submit()" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @forelse($sessionOptions as $option)
                        <option value="{{ $option->id }}" @selected($selectedSession?->id === $option->id)>
                            {{ $option->session_date->translatedFormat('D, d M Y') }} • {{ $option->start_at->format('H:i') }}
                        </option>
                    @empty
                        <option value="">Belum ada sesi absen</option>
                    @endforelse
                </select>
            </form>

            <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-gray-600">
                <p class="font-semibold text-gray-900">{{ $participants->count() }} peserta</p>
                <p>{{ $selectedSession ? $attendances->count() . ' sudah diinput' : 'Pilih sesi dulu' }}</p>
            </div>
        </div>
    </div>

    @if($selectedSession)
        @php
            $statusCounts = $attendances->countBy('status');
            $statusBadges = [
                'present' => ['label' => 'Hadir', 'class' => 'bg-green-50 text-green-700'],
                'late' => ['label' => 'Terlambat', 'class' => 'bg-amber-50 text-amber-700'],
                'excused' => ['label' => 'Izin', 'class' => 'bg-blue-50 text-blue-700'],
                'absent' => ['label' => 'Tidak Hadir', 'class' => 'bg-red-50 text-red-700'],
            ];
        @endphp

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Sesi Dipilih</p>
                <p class="mt-1 text-sm font-bold text-gray-900">{{ $selectedSession->session_date->translatedFormat('l, d M Y') }}</p>
                <p class="text-xs text-gray-500">{{ $selectedSession->start_at->format('H:i') }}{{ $selectedSession->end_at ? ' - ' . $selectedSession->end_at->format('H:i') : '' }}</p>
            </div>
            @foreach($statusBadges as $status => $meta)
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase text-gray-500">{{ $meta['label'] }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $statusCounts->get($status, 0) }}</p>
                </div>
            @endforeach
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                    <tr>
                        <th class="px-4 py-3">Peserta</th>
                        <th class="px-4 py-3">Status Saat Ini</th>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Foto</th>
                        <th class="px-4 py-3">Set Absensi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($participants as $participant)
                        @php($attendance = $attendances->get($participant->id))
                        @php($badge = $statusBadges[$attendance?->status ?? ''] ?? ['label' => 'Belum absen', 'class' => 'bg-gray-100 text-gray-600'])
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ $participant->name }}</p>
                                <p class="text-xs text-gray-500">{{ $participant->email }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-medium {{ $badge['class'] }}">
                                    {{ $badge['label'] }}
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
                                <form method="POST" action="{{ route('admin.class-attendance.mark', $selectedSession) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $participant->id }}">
                                    <select name="status" class="rounded-lg border border-gray-200 px-2 py-1.5 text-xs">
                                        @foreach(['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Tidak Hadir', 'excused' => 'Izin'] as $key => $label)
                                            <option value="{{ $key }}" @selected(($attendance->status ?? '') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary/90">Simpan</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada peserta pada kategori tujuan jadwal ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500">
            Belum ada sesi absen untuk jadwal ini. Klik <span class="font-semibold text-gray-700">Generate Absen</span> untuk membuat sesi berdasarkan jadwal.
        </div>
    @endif

</div>
@endsection
