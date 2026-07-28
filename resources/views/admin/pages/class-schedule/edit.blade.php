@extends('admin.layout.admin')

@section('title', 'Edit Kelas & Jadwal')

@section('content')
@php
    $selectedPackageIds = collect(old('package_ids', $classSchedule->packages->pluck('package_id')->all()))
        ->map(fn ($id) => (int) $id);
@endphp
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Kelas & Jadwal</h1>
        <p class="text-sm text-gray-500">Perbarui pola kelas, peserta, dan izin request jadwal custom.</p>
    </div>

    <form method="POST" action="{{ route('admin.class-schedules.update', $classSchedule) }}" class="rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <input type="hidden" name="day_of_month" value="{{ old('day_of_month', $classSchedule->day_of_month) }}">

        <div class="grid gap-5 md:grid-cols-2" x-data="{
            scheduleType: @js(old('schedule_type', $classSchedule->schedule_type ?: 'recurring')),
            allowCustom: {{ old('allow_custom_booking', $classSchedule->allow_custom_booking) ? 'true' : 'false' }}
        }">
            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-gray-700">Nama Jadwal</label>
                <input type="text" name="title" value="{{ old('title', $classSchedule->title) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Contoh: Bimbel Reguler Senin Sore">
            </div>

            <div class="md:col-span-2">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Tersedia untuk Paket</label>
                        <p class="mt-1 text-xs text-gray-500">Siswa dengan salah satu paket terpilih dan akses aktif dapat melihat jadwal.</p>
                    </div>
                    <span class="text-xs text-gray-400">Opsional</span>
                </div>
                <div class="grid max-h-52 gap-2 overflow-y-auto rounded-lg border border-gray-200 p-3 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($packages as $package)
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-100 px-3 py-2.5 hover:border-primary/30 hover:bg-primary/5">
                            <input type="checkbox" name="package_ids[]" value="{{ $package->package_id }}"
                                @checked($selectedPackageIds->contains((int) $package->package_id))
                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="text-sm text-gray-700">{{ $package->name }}</span>
                        </label>
                    @empty
                        <p class="py-3 text-sm text-gray-500 sm:col-span-2 lg:col-span-3">Belum ada paket aktif. Jadwal tetap dapat ditujukan melalui rombel.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Rombel / Grup Belajar</label>
                <select name="study_group_id" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <option value="">Pilih rombel</option>
                    @foreach($studyGroups as $studyGroup)
                        <option value="{{ $studyGroup->id }}" @selected(old('study_group_id', $classSchedule->study_group_id) == $studyGroup->id)>{{ $studyGroup->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Tutor</label>
                <select name="tentor_id" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <option value="">Ikuti Tutor dari rombel</option>
                    @foreach($tentors as $tentor)
                        <option value="{{ $tentor->id }}" @selected(old('tentor_id', $classSchedule->tentor_id) == $tentor->id)>
                            {{ $tentor->name }}{{ $tentor->expertise ? ' - ' . $tentor->expertise : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <input type="hidden" name="class_id" value="{{ old('class_id', $classSchedule->class_id ?: $classes->first()?->class_id) }}">

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">Tipe Jadwal</label>
                <select name="schedule_type" x-model="scheduleType" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <option value="recurring">Rutin</option>
                    <option value="single">Sekali Jalan</option>
                </select>
            </div>

            <input type="hidden" name="frequency" :value="scheduleType === 'recurring' ? 'weekly' : ''">

            <div x-show="scheduleType === 'recurring'">
                <label class="mb-2 block text-sm font-semibold text-gray-700">Hari Pelaksanaan</label>
                <select name="day_of_week" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @foreach([1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'] as $day => $label)
                        <option value="{{ $day }}" @selected(old('day_of_week', $preselectedDay) == $day)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700" x-text="scheduleType === 'single' ? 'Tanggal Sesi' : 'Tanggal Mulai'"></label>
                <input type="date" name="start_date" value="{{ old('start_date', $classSchedule->start_date?->toDateString() ?: now()->toDateString()) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
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
                <input type="time" name="end_time" value="{{ old('end_time', $classSchedule->end_time ? substr((string) $classSchedule->end_time, 0, 5) : '') }}" :required="allowCustom" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>

            <div class="md:col-span-2 rounded-xl border border-gray-200 p-4">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="hidden" name="allow_custom_booking" value="0">
                    <input type="checkbox" name="allow_custom_booking" value="1" x-model="allowCustom" class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                    <span>
                        <span class="block text-sm font-bold text-gray-900">Izinkan siswa request jadwal custom</span>
                        <span class="mt-1 block text-xs leading-5 text-gray-500">Siswa pemilik paket dapat mengusulkan waktu lain. Tutor tetap harus menyetujui sebelum sesi dibuat.</span>
                    </span>
                </label>
                <div x-show="allowCustom" x-cloak class="mt-4 border-t border-gray-200 pt-4">
                    <label class="block max-w-xs">
                        <span class="text-sm font-semibold text-gray-700">Kuota request per siswa/kelompok</span>
                        <input type="number" name="booking_session_quota" min="1" max="1000" required value="{{ old('booking_session_quota', $classSchedule->booking_session_quota ?: 1) }}" class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    </label>
                    <p class="mt-2 text-xs text-gray-500">Untuk mengaktifkan opsi ini, pilih minimal satu paket, satu Tutor, serta jam selesai.</p>
                </div>
                <input type="hidden" name="booking_session_quota" value="1" :disabled="allowCustom">
            </div>

            @if($canUseClass)
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-gray-700">Link Meeting (Opsional)</label>
                    <input type="url" name="meeting_url" value="{{ old('meeting_url', $classSchedule->meeting_url) }}" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="https://zoom.us/...">
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
            @else
                <input type="hidden" name="attendance_mode" value="button">
                <input type="hidden" name="open_minutes_before" value="15">
                <input type="hidden" name="close_minutes_after" value="30">
            @endif
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
