@extends('admin.layout.admin')

@section('title', 'Jadwal')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 font-sans tracking-tight">{{ $canUseClass ? 'Jadwal & Absensi' : 'Jadwal' }}</h1>
            <p class="text-sm text-gray-500">
                @if($filteredPackage)
                    Menampilkan jadwal untuk paket <span class="font-semibold text-gray-700">{{ $filteredPackage->name }}</span>.
                    <a href="{{ route('admin.class-schedules.index') }}" class="font-semibold text-primary hover:underline">Tampilkan semua</a>
                @else
                    {{ $canUseClass ? 'Kelola jadwal rutin, absensi, dan kelas Zoom.' : 'Kelola jadwal rutin atau sekali jalan untuk setiap paket.' }}
                @endif
            </p>
        </div>
        @if($activeTab === 'zoom')
            <x-btn title="Tambah Kelas Zoom" route="{{ route('admin.class.create') }}" icon="ri-add-fill"></x-btn>
        @else
            <x-btn title="Tambah Jadwal" route="{{ route('admin.class-schedules.create', request()->only('package_id')) }}" icon="ri-add-fill"></x-btn>
        @endif
    </div>

    <div class="flex justify-start border-b border-gray-200 overflow-x-auto">
        <a href="{{ route('admin.class-schedules.index', ['tab' => 'schedules']) }}"
            class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'schedules' ? 'text-primary border-primary' : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300' }}">
            {{ $canUseClass ? 'Jadwal & Absensi' : 'Jadwal' }}
        </a>
        @if($canUseClass)
            <a href="{{ route('admin.class-schedules.index', ['tab' => 'zoom']) }}"
                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'zoom' ? 'text-primary border-primary' : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300' }}">
                Kelas Zoom
            </a>
        @endif
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
            <i class="ri-checkbox-circle-line text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center gap-2">
            <i class="ri-error-warning-line text-lg"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($activeTab === 'schedules')
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
                    <a href="{{ route('admin.class-schedules.create', array_filter(['day_of_week' => $dayNum, 'package_id' => request()->integer('package_id') ?: null])) }}" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white text-gray-600 border border-gray-250 hover:bg-primary hover:text-white hover:border-primary transition-all duration-200">
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
                                @if($schedule->studyGroup)
                                    <span class="text-[11px] text-gray-500 flex items-center gap-1">
                                        <i class="ri-group-line"></i>
                                        {{ $schedule->studyGroup->name }}
                                    </span>
                                @endif
                                @if($schedule->packages->isNotEmpty())
                                    <span class="text-[11px] text-gray-500 flex items-start gap-1">
                                        <i class="ri-price-tag-3-line mt-0.5"></i>
                                        <span>{{ $schedule->packages->pluck('name')->join(', ') }}</span>
                                    </span>
                                @endif
                                <?php $tentorName = $schedule->tentor?->name ?? $schedule->studyGroup?->tentor?->name ?? $schedule->class?->tentor?->name ?? $schedule->class?->mentor; ?>
                                @if($tentorName)
                                    <span class="text-[11px] text-gray-500 flex items-center gap-1">
                                        <i class="ri-user-star-line"></i>
                                        {{ $tentorName }}
                                    </span>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="mt-3 pt-2.5 border-t border-gray-100 flex items-center justify-end gap-2 text-xs">
                                <div class="flex items-center gap-1.5">
                                    @if($canUseClass)
                                        <a href="{{ route('admin.class-schedules.show', $schedule) }}"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-primary/10 hover:text-primary transition-colors"
                                            title="Absensi" aria-label="Absensi">
                                            <i class="ri-eye-line text-base"></i>
                                            <span class="sr-only">Absensi</span>
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.class-schedules.edit', $schedule) }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-primary/10 hover:text-primary transition-colors"
                                        title="Edit" aria-label="Edit">
                                        <i class="ri-edit-line text-base"></i>
                                        <span class="sr-only">Edit</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.class-schedules.destroy', $schedule) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors"
                                            title="Hapus" aria-label="Hapus">
                                            <i class="ri-delete-bin-line text-base"></i>
                                            <span class="sr-only">Hapus</span>
                                        </button>
                                    </form>
                                </div>
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
                                    <?php $tentorName = $schedule->tentor?->name ?? $schedule->studyGroup?->tentor?->name ?? $schedule->class?->tentor?->name ?? $schedule->class?->mentor; ?>
                                    @if($tentorName)
                                        <p class="text-xs text-gray-500">Tutor: {{ $tentorName }}</p>
                                    @endif
                                    @if($schedule->studyGroup)
                                        <p class="text-xs text-gray-500">Rombel: {{ $schedule->studyGroup->name }}</p>
                                    @endif
                                    @if($schedule->packages->isNotEmpty())
                                        <p class="text-xs text-gray-500">Paket: {{ $schedule->packages->pluck('name')->join(', ') }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                        {{ ucfirst($schedule->schedule_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ substr($schedule->start_time, 0, 5) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        @if($canUseClass)
                                            <a href="{{ route('admin.class-schedules.show', $schedule) }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-primary/10 hover:text-primary transition-colors"
                                                title="Absensi" aria-label="Absensi">
                                                <i class="ri-eye-line text-base"></i>
                                                <span class="sr-only">Absensi</span>
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.class-schedules.edit', $schedule) }}"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-primary/10 hover:text-primary transition-colors"
                                            title="Edit" aria-label="Edit">
                                            <i class="ri-edit-line text-base"></i>
                                            <span class="sr-only">Edit</span>
                                        </a>
                                        <form method="POST" action="{{ route('admin.class-schedules.destroy', $schedule) }}" onsubmit="return confirm('Apakah Anda yakin?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors"
                                                title="Hapus" aria-label="Hapus">
                                                <i class="ri-delete-bin-line text-base"></i>
                                                <span class="sr-only">Hapus</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    @else
        <div class="package-bimbel bg-white p-8 rounded-lg border border-border">
            <x-page-desc title="Kelas Zoom" description="Data kelas live/Zoom lama. Jadwal rutin dan sekali jalan baru tetap dikelola dari tab Jadwal & Absensi."></x-page-desc>

            <div class="relative overflow-x-auto mt-4">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">Tanggal & Waktu</th>
                            <th scope="col" class="px-6 py-3 text-center">Judul</th>
                            <th scope="col" class="px-6 py-3 text-center">Mentor</th>
                            <th scope="col" class="px-6 py-3 text-center">Status</th>
                            <th scope="col" class="px-6 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($liveClasses as $class)
                            <tr class="bg-white border-b border-dashed border-gray-200 text-grey3">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-semibold">
                                            {{ \Carbon\Carbon::parse($class->schedule_time)->translatedFormat('l, d F Y') }}
                                        </p>
                                        <p>Pukul {{ \Carbon\Carbon::parse($class->schedule_time)->format('H:i') }} WIB</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">{{ $class->title }}</td>
                                <td class="px-6 py-4 text-center">{{ $class->tentor?->name ?? $class->mentor ?? '-' }}</td>
                                <td class="px-6 py-4 text-center">
                                    @if($class->status == 'upcoming')
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">Akan Datang</span>
                                    @elseif($class->status == 'completed')
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Selesai</span>
                                    @else
                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Dibatalkan</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center items-center gap-2">
                                        <a href="{{ route('admin.class.assessments', $class->class_id) }}"
                                            class="text-gray-500 hover:text-primary" title="Kelola Pre/Post Test">
                                            <i class="ri-file-list-3-line text-xl"></i>
                                        </a>
                                        @if($class->zoom_link)
                                            <a href="{{ $class->zoom_link }}" target="_blank" class="text-gray-500 hover:text-primary" title="Buka Zoom">
                                                <i class="ri-video-on-line text-xl"></i>
                                            </a>
                                        @endif
                                        @if($class->drive_link)
                                            <a href="{{ $class->drive_link }}" target="_blank" class="text-gray-500 hover:text-blue-600" title="Buka Materi">
                                                <i class="ri-folder-line text-xl"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.class.edit', array_merge(request()->query(), ['class' => $class->class_id, 'tab' => 'zoom'])) }}"
                                            class="text-gray-500 hover:text-yellow-500" title="Edit">
                                            <i class="ri-edit-line text-xl"></i>
                                        </a>
                                        <form action="{{ route('admin.class.destroy', $class->class_id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-gray-500 hover:text-red-500"
                                                title="Hapus" onclick="return confirm('Yakin ingin menghapus kelas ini?')">
                                                <i class="ri-delete-bin-line text-xl"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i class="ri-video-chat-line text-4xl text-gray-300 mb-2"></i>
                                        <p>Belum ada kelas Zoom tersedia</p>
                                        <a href="{{ route('admin.class.create') }}" class="text-primary hover:underline mt-2">
                                            Buat kelas Zoom baru
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($liveClasses->hasPages())
                <div class="flex justify-center mt-4">
                    {{ $liveClasses->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
