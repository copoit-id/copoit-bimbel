@extends('admin.layout.admin')

@section('title', 'Edit Jadwal Kelas')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Jadwal Kelas</h1>
        <p class="text-sm text-gray-500">Perbarui hari, jam, kategori tujuan, dan metode absensi jadwal ini.</p>
    </div>

    <form method="POST" action="{{ route('admin.class-schedules.update', $classSchedule) }}" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <input type="hidden" name="schedule_type" value="{{ old('schedule_type', $classSchedule->schedule_type ?: 'recurring') }}">
        <input type="hidden" name="frequency" value="{{ old('frequency', $classSchedule->frequency ?: 'weekly') }}">
        <input type="hidden" name="day_of_month" value="{{ old('day_of_month', $classSchedule->day_of_month) }}">
        <input type="hidden" name="start_date" value="{{ old('start_date', $classSchedule->start_date?->toDateString() ?: now()->toDateString()) }}">

        <div class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-gray-700">Nama Jadwal</label>
                <input type="text" name="title" value="{{ old('title', $classSchedule->title) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Contoh: Kelas UTBK Senin Malam">
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Kelas</label>
                <select name="class_id" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @foreach($classes as $class)
                        <option value="{{ $class->class_id }}" @selected(old('class_id', $classSchedule->class_id) == $class->class_id)>{{ $class->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Tentor</label>
                <select name="tentor_id" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <option value="">Ikuti tentor dari kelas</option>
                    @foreach($tentors as $tentor)
                        <option value="{{ $tentor->id }}" @selected(old('tentor_id', $classSchedule->tentor_id) == $tentor->id)>
                            {{ $tentor->name }}{{ $tentor->expertise ? ' - ' . $tentor->expertise : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Hari Pelaksanaan</label>
                <select name="day_of_week" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @foreach([1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'] as $day => $label)
                        <option value="{{ $day }}" @selected(old('day_of_week', $preselectedDay) == $day)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Lokasi / Ruangan</label>
                <input type="text" name="location" value="{{ old('location', $classSchedule->location) }}" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Contoh: Ruang 1 / Online">
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Jam Mulai</label>
                <input type="time" name="start_time" value="{{ old('start_time', substr((string) $classSchedule->start_time, 0, 5)) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Jam Selesai</label>
                <input type="time" name="end_time" value="{{ old('end_time', $classSchedule->end_time ? substr((string) $classSchedule->end_time, 0, 5) : '') }}" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>

            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-gray-700">Link Meeting (Opsional)</label>
                <input type="url" name="meeting_url" value="{{ old('meeting_url', $classSchedule->meeting_url) }}" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="https://zoom.us/...">
            </div>

            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-gray-700">Kategori Tujuan <span class="text-red-500">*</span></label>
                @php
                    $selectedDestinationIds = collect(old('destination_category_ids', $classSchedule->destinationCategories->pluck('id')->all()))
                        ->map(fn($id) => (int) $id)
                        ->all();
                @endphp
                <div x-data="{ search: '' }" class="rounded-lg border border-gray-200 bg-white">
                    <div class="flex flex-col gap-3 border-b border-gray-200 p-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="relative w-full sm:max-w-sm">
                            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input
                                type="text"
                                x-model.debounce.150ms="search"
                                class="w-full rounded-lg border border-gray-300 py-2 pl-10 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                placeholder="Cari instansi atau sub tujuan..."
                            >
                        </div>
                        <p class="text-xs text-gray-500">Checklist satu atau beberapa kategori tujuan.</p>
                    </div>

                    <div class="max-h-72 overflow-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="sticky top-0 bg-gray-50 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="w-12 px-4 py-3"></th>
                                    <th class="px-4 py-3">Kategori</th>
                                    <th class="px-4 py-3">Tipe</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($destinationCategories as $category)
                                    @php
                                        $searchText = strtolower($category->display_name . ' ' . ($category->parent?->name ?? '') . ' ' . $category->name);
                                    @endphp
                                    <tr
                                        class="border-t border-gray-100 hover:bg-primary/5"
                                        x-show="'{{ e($searchText) }}'.includes(search.toLowerCase())"
                                    >
                                        <td class="px-4 py-3 align-top">
                                            <input
                                                type="checkbox"
                                                name="destination_category_ids[]"
                                                value="{{ $category->id }}"
                                                class="rounded border-gray-300 text-primary focus:ring-primary"
                                                @checked(in_array((int) $category->id, $selectedDestinationIds, true))
                                            >
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-medium text-gray-900">{{ $category->display_name }}</p>
                                            @if($category->parent)
                                                <p class="text-xs text-gray-500">Induk: {{ $category->parent->name }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="rounded-full px-2 py-1 text-xs font-medium {{ $category->parent ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-700' }}">
                                                {{ $category->parent ? 'Sub Tujuan' : 'Instansi' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                                            Belum ada kategori tujuan. Tambahkan dulu di menu Kategori > Tujuan / Instansi.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <p class="mt-1 text-xs text-gray-500">Peserta yang bisa absen akan diambil dari user dengan kategori tujuan ini. Bisa pilih lebih dari satu.</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Metode Absensi</label>
                <select name="attendance_mode" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <option value="button" @selected(old('attendance_mode', $classSchedule->attendanceSetting?->mode ?? 'button') === 'button')>Tombol saja</option>
                    <option value="photo" @selected(old('attendance_mode', $classSchedule->attendanceSetting?->mode ?? 'button') === 'photo')>Wajib foto</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700" title="Berapa menit absensi dibuka sebelum jam mulai kelas">Buka sebelum (menit)</label>
                    <input type="number" name="open_minutes_before" value="{{ old('open_minutes_before', $classSchedule->attendanceSetting?->open_minutes_before ?? 15) }}" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700" title="Berapa menit absensi ditutup setelah jam mulai kelas">Tutup setelah (menit)</label>
                    <input type="number" name="close_minutes_after" value="{{ old('close_minutes_after', $classSchedule->attendanceSetting?->close_minutes_after ?? 30) }}" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                </div>
            </div>
        </div>

        <label class="mt-5 flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $classSchedule->is_active) == 1) class="rounded border-gray-300 text-primary focus:ring-primary">
            Aktifkan jadwal ini
        </label>

        <div class="mt-6 flex justify-end gap-2 border-t border-gray-200 pt-5">
            <a href="{{ route('admin.class-schedules.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</a>
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
