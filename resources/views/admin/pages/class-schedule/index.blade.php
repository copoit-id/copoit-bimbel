@extends('admin.layout.admin')

@section('title', 'Jadwal Kelas')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 font-sans tracking-tight">Jadwal Mingguan</h1>
            <p class="text-sm text-gray-500">Kelola jadwal rutin mingguan untuk kegiatan bimbingan belajar.</p>
        </div>
    </div>

    <!-- Weekly Grid Timetable -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-7">
        @php
            $days = [
                1 => ['label' => 'Senin'],
                2 => ['label' => 'Selasa'],
                3 => ['label' => 'Rabu'],
                4 => ['label' => 'Kamis'],
                5 => ['label' => 'Jumat'],
                6 => ['label' => 'Sabtu'],
                7 => ['label' => 'Minggu']
            ];
        @endphp

        @foreach($days as $dayNum => $dayInfo)
            <div class="flex flex-col rounded-lg border border-gray-200 bg-white overflow-hidden min-h-[300px]">
                <!-- Day Header -->
                <div class="flex items-center justify-between px-3 py-2.5 border-b border-gray-200 bg-gray-50 text-gray-800">
                    <span class="font-bold text-sm tracking-wide">{{ $dayInfo['label'] }}</span>
                    <a href="{{ route('admin.class-schedules.create', ['day_of_week' => $dayNum]) }}" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white text-gray-600 border border-gray-250 hover:bg-primary hover:text-white hover:border-primary transition-all duration-200">
                        <i class="ri-add-line text-sm"></i>
                    </a>
                </div>

                <!-- Day Schedules List -->
                <div class="flex-1 p-2 space-y-2 bg-slate-50/20">
                    @forelse($weeklySchedules[$dayNum] as $schedule)
                        <div class="group relative rounded-lg border border-gray-200 bg-white p-3 hover:border-primary transition-all duration-200">
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-semibold text-primary">
                                    {{ substr($schedule->start_time, 0, 5) }}{{ $schedule->end_time ? ' - ' . substr($schedule->end_time, 0, 5) : '' }}
                                </span>
                                <h3 class="font-bold text-gray-900 text-sm leading-snug group-hover:text-primary transition-colors">
                                    {{ $schedule->title }}
                                </h3>
                                @if($schedule->location)
                                    <span class="text-[11px] text-gray-500 flex items-center gap-1">
                                        <i class="ri-map-pin-line"></i>
                                        {{ $schedule->location }}
                                    </span>
                                @elseif($schedule->meeting_url)
                                    <a href="{{ $schedule->meeting_url }}" target="_blank" class="text-[11px] text-primary hover:underline flex items-center gap-1 font-medium">
                                        <i class="ri-video-chat-line"></i>
                                        Online Meeting
                                    </a>
                                @endif
                                @if($schedule->destinationCategories->isNotEmpty())
                                    <span class="text-[11px] text-gray-500 flex items-center gap-1">
                                        <i class="ri-focus-3-line"></i>
                                        {{ $schedule->destinationCategories->map(fn($category) => $category->display_name)->take(2)->implode(', ') }}
                                        @if($schedule->destinationCategories->count() > 2)
                                            +{{ $schedule->destinationCategories->count() - 2 }}
                                        @endif
                                    </span>
                                @endif
                                <?php $tentorName = $schedule->tentor?->name ?? $schedule->class?->tentor?->name ?? $schedule->class?->mentor; ?>
                                @if($tentorName)
                                    <span class="text-[11px] text-gray-500 flex items-center gap-1">
                                        <i class="ri-user-star-line"></i>
                                        {{ $tentorName }}
                                    </span>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="mt-3 pt-2.5 border-t border-gray-100 flex items-center justify-between gap-2 text-xs">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.class-schedules.show', $schedule) }}" class="text-gray-500 hover:text-primary font-medium flex items-center gap-0.5">
                                        <i class="ri-eye-line"></i> Absen
                                    </a>
                                    <a href="{{ route('admin.class-schedules.edit', $schedule) }}" class="text-gray-500 hover:text-primary font-medium flex items-center gap-0.5">
                                        <i class="ri-edit-line"></i> Edit
                                    </a>
                                </div>
                                
                                <form method="POST" action="{{ route('admin.class-schedules.destroy', $schedule) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-red-600 flex items-center gap-0.5 transition-colors">
                                        <i class="ri-delete-bin-line"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="h-full flex flex-col items-center justify-center py-10 text-center text-xs text-gray-400 border border-dashed border-gray-200 rounded-lg bg-slate-50/30">
                            <i class="ri-calendar-event-line text-lg mb-1 opacity-50"></i>
                            <span>Belum ada</span>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <!-- Other/Single Schedules (if any) -->
    @if($otherSchedules->isNotEmpty())
        <div class="mt-8">
            <h2 class="text-lg font-bold text-gray-900 mb-3">Jadwal Non-Mingguan / Sekali Jalan</h2>
            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <th class="px-4 py-3">Jadwal</th>
                            <th class="px-4 py-3">Kategori Tujuan</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3">Jam</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($otherSchedules as $schedule)
                            <tr class="border-t border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-900">{{ $schedule->title }}</p>
                                    <?php $tentorName = $schedule->tentor?->name ?? $schedule->class?->tentor?->name ?? $schedule->class?->mentor; ?>
                                    @if($tentorName)
                                        <p class="text-xs text-gray-500">Tentor: {{ $tentorName }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    {{ $schedule->destinationCategories->map(fn($category) => $category->display_name)->implode(', ') ?: '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                        {{ ucfirst($schedule->schedule_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ substr($schedule->start_time, 0, 5) }}</td>
                                <td class="px-4 py-3 flex items-center gap-3">
                                    <a href="{{ route('admin.class-schedules.show', $schedule) }}" class="text-primary hover:underline">Absen</a>
                                    <a href="{{ route('admin.class-schedules.edit', $schedule) }}" class="text-gray-600 hover:text-primary hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.class-schedules.destroy', $schedule) }}" onsubmit="return confirm('Apakah Anda yakin?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
