@extends('admin.layout.admin')

@section('content')
@php
    $selectedTutorIds = old('tutor_ids', $rule->exists ? $rule->tutors->pluck('id')->all() : []);
    $allowAllTutors = (bool) old('allow_all_tutors', $rule->allow_all_tutors);
    $tierPrices = collect(old('price_tiers', $rule->exists
        ? $rule->priceTiers->map(fn ($tier) => [
            'participant_count' => $tier->participant_count,
            'price_per_person' => $tier->price_per_person,
        ])->all()
        : []))->mapWithKeys(fn ($tier) => [
            (int) $tier['participant_count'] => $tier['price_per_person'],
        ])->all();
@endphp

<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <a href="{{ route('admin.package.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-gray-500 hover:text-primary">
                <i class="ri-arrow-left-line"></i>
                Kembali ke paket
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Booking Jadwal</h1>
            <p class="mt-1 text-sm text-gray-500">Atur kuota dan tutor yang dapat dipesan untuk paket <span class="font-semibold text-gray-700">{{ $package->name }}</span>.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.package-booking.cohorts.index') }}" class="rounded-lg border border-primary px-3 py-2 text-sm font-semibold text-primary hover:bg-primary hover:text-white">Kelola kelompok</a>
            <span class="w-fit rounded-full border border-gray-200 px-3 py-1.5 text-sm font-semibold text-gray-600">
                {{ $rule->is_enabled ? 'Booking aktif' : 'Booking nonaktif' }}
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold">Pengaturan belum dapat disimpan.</p>
            <ul class="mt-1 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach([
            ['label' => 'Menunggu', 'value' => ($statusCounts['pending'] ?? 0) + ($statusCounts['counter_proposed'] ?? 0)],
            ['label' => 'Disetujui', 'value' => $statusCounts['approved'] ?? 0],
            ['label' => 'Ditolak', 'value' => $statusCounts['rejected'] ?? 0],
            ['label' => 'Dibatalkan', 'value' => $statusCounts['cancelled'] ?? 0],
        ] as $stat)
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-sm text-gray-500">{{ $stat['label'] }}</p>
                <p class="mt-1 text-2xl font-bold text-primary">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <form
        method="POST"
        action="{{ route('admin.package-booking.update', $package) }}"
        class="space-y-6"
        x-data="{
            learningMode: @js(old('learning_mode', $rule->learning_mode)),
            minParticipants: Number(@js(old('min_participants', $rule->min_participants))),
            maxParticipants: Number(@js(old('max_participants', $rule->max_participants))),
            prices: @js($tierPrices),
            participantCounts() {
                const minimum = Math.max(1, Number(this.minParticipants) || 1);
                const maximum = Math.min(20, Math.max(minimum, Number(this.maxParticipants) || minimum));
                return Array.from({ length: maximum - minimum + 1 }, (_, index) => minimum + index);
            }
        }"
    >
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-bold text-gray-900">Status booking</h2>
                    <p class="mt-1 text-sm text-gray-500">Jika aktif, siswa dengan akses paket ini dapat mengirim permintaan jadwal.</p>
                </div>
                <label class="inline-flex cursor-pointer items-center">
                    <input type="hidden" name="is_enabled" value="0">
                    <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $rule->is_enabled)) class="peer sr-only">
                    <span class="relative h-6 w-11 rounded-full bg-gray-200 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-transform peer-checked:bg-primary peer-checked:after:translate-x-5"></span>
                </label>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="font-bold text-gray-900">Format layanan dan kelompok</h2>
            <p class="mt-1 text-sm text-gray-500">Atur paket personal, kelompok, atau keduanya tanpa membuat tipe paket baru.</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Pelaksanaan</span>
                    <select name="delivery_mode" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                        <option value="offline" @selected(old('delivery_mode', $rule->delivery_mode) === 'offline')>Offline / tatap muka</option>
                        <option value="online" @selected(old('delivery_mode', $rule->delivery_mode) === 'online')>Online</option>
                        <option value="hybrid" @selected(old('delivery_mode', $rule->delivery_mode) === 'hybrid')>Hybrid</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Model belajar</span>
                    <select name="learning_mode" x-model="learningMode" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                        <option value="personal">Personal saja</option>
                        <option value="group">Kelompok saja</option>
                        <option value="both">Personal dan kelompok</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Lokasi default</span>
                    <input type="text" name="default_location" maxlength="255" value="{{ old('default_location', $rule->default_location) }}" placeholder="Contoh: Cabang Sudirman, Ruang 2" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                    <span class="mt-1 block text-xs text-gray-500">Kosongkan untuk ditentukan setelah tutor menyetujui.</span>
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Batas pembayaran anggota</span>
                    <div class="relative mt-2">
                        <input type="number" name="payment_deadline_hours" min="1" max="720" required value="{{ old('payment_deadline_hours', $rule->payment_deadline_hours) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 pr-12 focus:border-primary focus:ring-primary">
                        <span class="absolute right-3 top-2.5 text-sm text-gray-400">jam</span>
                    </div>
                </label>
            </div>

            <div class="mt-6 border-t border-gray-200 pt-5" x-show="learningMode === 'group' || learningMode === 'both'" x-cloak>
                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Minimal anggota</span>
                        <input type="number" name="min_participants" x-model.number="minParticipants" min="1" max="20" required value="{{ old('min_participants', $rule->min_participants) }}" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Maksimal anggota</span>
                        <input type="number" name="max_participants" x-model.number="maxParticipants" min="1" max="20" required value="{{ old('max_participants', $rule->max_participants) }}" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                    </label>
                </div>

                <div class="mt-5">
                    <div class="mb-3">
                        <p class="text-sm font-semibold text-gray-700">Harga per orang</p>
                        <p class="mt-1 text-xs text-gray-500">Harga disimpan sebagai snapshot saat kelompok dibuat, sehingga perubahan berikutnya tidak mengubah tagihan lama.</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <template x-for="count in participantCounts()" :key="count">
                            <label class="flex items-center gap-3 rounded-xl border border-gray-200 p-3">
                                <span class="w-20 text-sm font-semibold text-gray-700" x-text="count + ' orang'"></span>
                                <input type="hidden" :name="'price_tiers[' + count + '][participant_count]'" :value="count">
                                <div class="relative flex-1">
                                    <span class="absolute left-3 top-2.5 text-sm text-gray-400">Rp</span>
                                    <input
                                        type="number"
                                        min="0"
                                        max="999999999999"
                                        x-model="prices[count]"
                                        :name="'price_tiers[' + count + '][price_per_person]'"
                                        class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 focus:border-primary focus:ring-primary"
                                        required
                                    >
                                </div>
                            </label>
                        </template>
                    </div>
                </div>
            </div>

            <div class="hidden">
                <input type="number" name="min_participants" :value="learningMode === 'personal' ? 1 : minParticipants">
                <input type="number" name="max_participants" :value="learningMode === 'personal' ? 1 : maxParticipants">
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="font-bold text-gray-900">Aturan sesi</h2>
            <p class="mt-1 text-sm text-gray-500">Batas berlaku untuk setiap akses paket yang dimiliki siswa.</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Kuota sesi</span>
                    <input type="number" name="session_quota" min="1" max="1000" required value="{{ old('session_quota', $rule->session_quota) }}" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                    <span class="mt-1 block text-xs text-gray-500">Jumlah booking yang dapat disetujui.</span>
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Durasi per sesi</span>
                    <div class="relative mt-2">
                        <input type="number" name="duration_minutes" min="15" max="480" required value="{{ old('duration_minutes', $rule->duration_minutes) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 pr-16 focus:border-primary focus:ring-primary">
                        <span class="absolute right-3 top-2.5 text-sm text-gray-400">menit</span>
                    </div>
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Minimal pengajuan</span>
                    <div class="relative mt-2">
                        <input type="number" name="min_notice_hours" min="0" max="720" required value="{{ old('min_notice_hours', $rule->min_notice_hours) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 pr-12 focus:border-primary focus:ring-primary">
                        <span class="absolute right-3 top-2.5 text-sm text-gray-400">jam</span>
                    </div>
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Batas waktu ke depan</span>
                    <div class="relative mt-2">
                        <input type="number" name="max_advance_days" min="1" max="365" required value="{{ old('max_advance_days', $rule->max_advance_days) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 pr-12 focus:border-primary focus:ring-primary">
                        <span class="absolute right-3 top-2.5 text-sm text-gray-400">hari</span>
                    </div>
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Batas pembatalan</span>
                    <div class="relative mt-2">
                        <input type="number" name="cancellation_hours" min="0" max="168" required value="{{ old('cancellation_hours', $rule->cancellation_hours) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 pr-12 focus:border-primary focus:ring-primary">
                        <span class="absolute right-3 top-2.5 text-sm text-gray-400">jam</span>
                    </div>
                    <span class="mt-1 block text-xs text-gray-500">Disiapkan untuk kebijakan reschedule berikutnya.</span>
                </label>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <input type="hidden" name="allow_custom_time" value="1">
                    <p class="text-sm font-semibold text-gray-700">Waktu fleksibel</p>
                    <p class="mt-1 text-xs leading-5 text-gray-500">Siswa mengusulkan tanggal dan jam. Tutor dapat menyetujui atau menawarkan waktu lain.</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6" x-data="{ allTutors: {{ $allowAllTutors ? 'true' : 'false' }} }">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <h2 class="font-bold text-gray-900">Tutor yang dapat dipilih</h2>
                    <p class="mt-1 text-sm text-gray-500">Tutor nonaktif otomatis tidak ditampilkan kepada siswa.</p>
                </div>
                <label class="inline-flex cursor-pointer items-center gap-2">
                    <input type="hidden" name="allow_all_tutors" value="0">
                    <input type="checkbox" name="allow_all_tutors" value="1" x-model="allTutors" class="rounded border-gray-300 text-primary focus:ring-primary">
                    <span class="text-sm font-semibold text-gray-700">Semua tutor aktif</span>
                </label>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2" x-show="!allTutors" x-cloak>
                @forelse($tutors as $tutor)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-4 hover:border-primary/50">
                        <input type="checkbox" name="tutor_ids[]" value="{{ $tutor->id }}" @checked(in_array($tutor->id, $selectedTutorIds)) class="mt-0.5 rounded border-gray-300 text-primary focus:ring-primary">
                        <span>
                            <span class="block text-sm font-semibold text-gray-800">{{ $tutor->name }}</span>
                            <span class="mt-0.5 block text-xs text-gray-500">{{ $tutor->expertise ?: 'Keahlian belum diisi' }}</span>
                        </span>
                    </label>
                @empty
                    <p class="col-span-full rounded-xl border border-dashed border-gray-300 p-5 text-center text-sm text-gray-500">Belum ada tutor aktif.</p>
                @endforelse
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">
                <i class="ri-save-line"></i>
                Simpan pengaturan
            </button>
        </div>
    </form>
</div>
@endsection
