@extends('user.layout.new-user')

@section('title', 'Booking Jadwal')

@section('content')
@php
    $statusLabels = [
        'pending' => 'Menunggu tutor',
        'counter_proposed' => 'Ada usulan waktu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
        'completed' => 'Selesai',
        'expired' => 'Kedaluwarsa',
    ];
    $statusClasses = [
        'pending' => 'border-amber-200 bg-amber-50 text-amber-700',
        'counter_proposed' => 'border-blue-200 bg-blue-50 text-blue-700',
        'approved' => 'border-green-200 bg-green-50 text-green-700',
        'rejected' => 'border-red-200 bg-red-50 text-red-700',
        'cancelled' => 'border-gray-200 bg-gray-50 text-gray-600',
        'completed' => 'border-primary/20 bg-primary/5 text-primary',
        'expired' => 'border-gray-200 bg-gray-50 text-gray-600',
    ];
    $initialAccess = (string) old('user_package_access_id', $accesses->first()?->user_package_access_id ?? '');
@endphp

<div class="space-y-6" x-data="{
    options: {{ Illuminate\Support\Js::from($accessOptions) }},
    selectedAccess: @js($initialAccess),
    selectedTutor: @js((string) old('tentor_id', '')),
    selectedCohort: @js((string) old('booking_cohort_id', '')),
    get selected() { return this.options[this.selectedAccess] || null },
    get selectedGroup() {
        return (this.selected?.cohorts || []).find(
            cohort => String(cohort.id) === String(this.selectedCohort)
        ) || null
    },
    get usedQuota() { return this.selectedGroup?.used ?? this.selected?.used ?? 0 },
    get tutorInfo() {
        return (this.selected?.tutors || []).find(
            tutor => String(tutor.id) === String(this.selectedTutor)
        ) || null
    },
}">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Booking Jadwal</h1>
        <p class="mt-1 text-sm text-gray-500">Usulkan waktu belajar, lalu tunggu konfirmasi dari tutor.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold">Permintaan belum dapat diproses.</p>
            <ul class="mt-1 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($groupPackages->isNotEmpty() || $cohorts->isNotEmpty())
        <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <h2 class="font-bold text-gray-900">Booking belajar kelompok</h2>
                    <p class="mt-1 text-sm text-gray-500">Buat kelompok, bagikan kode undangan, lalu setiap anggota membayar melalui akunnya sendiri.</p>
                </div>
                <form method="POST" action="{{ route('user.booking.cohort.join') }}" class="flex w-full max-w-sm gap-2">
                    @csrf
                    <input type="text" name="invite_code" maxlength="12" required placeholder="Kode kelompok" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase focus:border-primary focus:ring-primary">
                    <button class="rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary hover:text-white">Gabung</button>
                </form>
            </div>

            @if($groupPackages->isNotEmpty())
                <div class="mt-5 grid gap-3 lg:grid-cols-2">
                    @foreach($groupPackages as $groupPackage)
                        <article class="rounded-xl border border-gray-200 p-4">
                            <p class="font-bold text-gray-900">{{ $groupPackage->name }}</p>
                            <p class="mt-1 text-xs text-gray-500">Pilih jumlah anggota. Nominal di bawah adalah harga untuk setiap akun.</p>
                            <form method="POST" action="{{ route('user.booking.cohort.store') }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                                @csrf
                                <input type="hidden" name="package_id" value="{{ $groupPackage->package_id }}">
                                <select name="participant_count" required class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                    @foreach($groupPackage->bookingRule->priceTiers as $tier)
                                        <option value="{{ $tier->participant_count }}">
                                            {{ $tier->participant_count }} orang · Rp {{ number_format($tier->price_per_person, 0, ',', '.') }}/orang
                                        </option>
                                    @endforeach
                                </select>
                                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Buat kelompok</button>
                            </form>
                        </article>
                    @endforeach
                </div>
            @endif

            @if($cohorts->isNotEmpty())
                <div class="mt-6 border-t border-gray-200 pt-5">
                    <h3 class="text-sm font-bold text-gray-900">Kelompok saya</h3>
                    <div class="mt-3 grid gap-3 lg:grid-cols-2">
                        @foreach($cohorts as $cohort)
                            @php
                                $myParticipant = $cohort->participants->firstWhere('user_id', auth()->id());
                                $paidCount = $cohort->participants->where('status', 'paid')->count();
                            @endphp
                            <article class="rounded-xl border border-gray-200 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $cohort->package->name }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $paidCount }}/{{ $cohort->target_participants }} anggota lunas</p>
                                    </div>
                                    <span class="rounded-full border border-primary/20 bg-primary/5 px-2.5 py-1 text-xs font-bold text-primary">
                                        {{ $cohort->status === 'ready' ? 'Siap dijadwalkan' : 'Menunggu pelunasan' }}
                                    </span>
                                </div>
                                <div class="mt-3 flex items-center justify-between rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2">
                                    <span class="text-xs text-gray-500">Kode undangan</span>
                                    <strong class="tracking-widest text-gray-900">{{ $cohort->invite_code }}</strong>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs">
                                    <span class="text-gray-600">
                                        Statusmu:
                                        <strong>{{ $myParticipant?->status === 'paid' ? 'Lunas' : 'Menunggu pembayaran' }}</strong>
                                    </span>
                                    @if($myParticipant?->invoice && $myParticipant->invoice->status !== 'paid')
                                        <a href="{{ route('user.billing.index') }}" class="font-bold text-primary hover:underline">Lihat tagihan</a>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.4fr)]">
        <section class="h-fit rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-xl text-primary">
                    <i class="ri-calendar-schedule-line"></i>
                </span>
                <div>
                    <h2 class="font-bold text-gray-900">Ajukan jadwal baru</h2>
                    <p class="mt-1 text-sm text-gray-500">Tutor masih dapat menawarkan waktu alternatif.</p>
                </div>
            </div>

            @if($accesses->isNotEmpty())
                <form method="POST" action="{{ route('user.booking.store') }}" class="mt-6 space-y-5">
                    @csrf
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Paket</span>
                        <select name="user_package_access_id" x-model="selectedAccess" @change="selectedTutor = ''; selectedCohort = ''" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                            @foreach($accesses as $access)
                                <option value="{{ $access->user_package_access_id }}">{{ $access->package->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block" x-show="selected && ((selected?.cohorts || []).length > 0 || selected?.learning_mode === 'group')" x-cloak>
                        <span class="text-sm font-semibold text-gray-700">Peserta jadwal</span>
                        <select name="booking_cohort_id" x-model="selectedCohort" :required="selected?.learning_mode === 'group'" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                            <option value="" x-show="selected?.learning_mode !== 'group'">Personal</option>
                            <template x-for="cohort in (selected?.cohorts || [])" :key="cohort.id">
                                <option :value="cohort.id" x-text="`${cohort.label} · ${cohort.participants} orang`"></option>
                            </template>
                        </select>
                        <p x-show="selected?.learning_mode === 'group' && (selected?.cohorts || []).length === 0" class="mt-1 text-xs text-red-600">Kelompok harus penuh dan seluruh anggota lunas sebelum dijadwalkan.</p>
                    </label>

                    <div x-show="selected" class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                            <p class="text-xs text-gray-500">Kuota tersedia</p>
                            <p class="mt-1 font-bold text-gray-800">
                                <span x-text="Math.max((selected?.quota || 0) - usedQuota, 0)"></span>
                                <span class="font-normal text-gray-500">sesi</span>
                            </p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                            <p class="text-xs text-gray-500">Durasi</p>
                            <p class="mt-1 font-bold text-gray-800"><span x-text="selected?.duration_minutes || 0"></span> <span class="font-normal text-gray-500">menit</span></p>
                        </div>
                    </div>

                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Tutor</span>
                        <select name="tentor_id" x-model="selectedTutor" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                            <option value="">Pilih tutor</option>
                            <template x-for="tutor in (selected?.tutors || [])" :key="tutor.id">
                                <option :value="tutor.id" x-text="tutor.expertise ? `${tutor.name} — ${tutor.expertise}` : tutor.name"></option>
                            </template>
                        </select>
                        <p x-show="selected && selected.tutors.length === 0" class="mt-1 text-xs text-red-600">Belum ada tutor aktif untuk paket ini.</p>
                    </label>

                    <div x-show="tutorInfo" x-cloak class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="flex items-start gap-3">
                            <template x-if="tutorInfo?.photo_url">
                                <img :src="tutorInfo.photo_url" :alt="`Foto ${tutorInfo.name}`" loading="lazy" class="h-16 w-16 shrink-0 rounded-xl border border-gray-200 object-cover">
                            </template>
                            <template x-if="!tutorInfo?.photo_url">
                                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-xl font-bold text-primary" x-text="tutorInfo?.name?.charAt(0)?.toUpperCase()"></div>
                            </template>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-gray-900" x-text="tutorInfo?.name"></p>
                                <p class="mt-0.5 text-xs font-semibold text-primary" x-text="tutorInfo?.expertise || 'Tutor'"></p>
                                <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500">
                                    <span><i class="ri-star-fill text-amber-400"></i> <span x-text="tutorInfo?.rating || 'Belum dinilai'"></span> <span x-show="tutorInfo?.review_count">(<span x-text="tutorInfo?.review_count"></span> review)</span></span>
                                    <span x-show="tutorInfo?.experience_years !== null"><i class="ri-briefcase-line"></i> <span x-text="`${tutorInfo?.experience_years} tahun`"></span></span>
                                </div>
                            </div>
                        </div>
                        <p x-show="tutorInfo?.bio" class="mt-3 line-clamp-3 text-xs leading-5 text-gray-600" x-text="tutorInfo?.bio"></p>
                        <a :href="tutorInfo?.profile_url" class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                            Lihat profil & review
                            <i class="ri-arrow-right-line"></i>
                        </a>
                    </div>

                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Tanggal & jam yang diinginkan</span>
                        <input type="datetime-local" name="requested_start_at" required value="{{ old('requested_start_at') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                        <p x-show="selected" class="mt-1 text-xs text-gray-500">
                            Ajukan minimal <span x-text="selected?.min_notice_hours"></span> jam dan maksimal <span x-text="selected?.max_advance_days"></span> hari dari sekarang.
                        </p>
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Catatan <span class="font-normal text-gray-400">(opsional)</span></span>
                        <textarea name="student_notes" rows="3" maxlength="1000" placeholder="Contoh: ingin membahas materi TPS Penalaran Umum" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">{{ old('student_notes') }}</textarea>
                    </label>

                    <button type="submit" :disabled="!selected || usedQuota >= selected.quota || selected.tutors.length === 0 || (selected.learning_mode === 'group' && !selectedGroup)" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">
                        <i class="ri-send-plane-line"></i>
                        Kirim permintaan
                    </button>
                </form>
            @else
                <div class="mt-6 rounded-xl border border-dashed border-gray-300 p-6 text-center">
                    <i class="ri-calendar-close-line text-3xl text-gray-300"></i>
                    <p class="mt-2 text-sm font-semibold text-gray-700">Belum ada paket yang mendukung booking</p>
                    <p class="mt-1 text-xs text-gray-500">Paket harus aktif dan memiliki kuota booking.</p>
                    <a href="{{ route('user.package.index') }}" class="mt-4 inline-flex rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary hover:text-white">Lihat paket</a>
                </div>
            @endif
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="font-bold text-gray-900">Riwayat permintaan</h2>
                <p class="mt-1 text-sm text-gray-500">Status terbaru dan jadwal yang telah dikonfirmasi.</p>
            </div>

            @forelse($bookings as $booking)
                @php
                    $sessionEnd = $booking->session?->end_at ?? $booking->session?->start_at;
                    $canReview = in_array($booking->status, ['approved', 'completed'], true)
                        && $sessionEnd
                        && $booking->session?->status !== 'cancelled'
                        && $sessionEnd->isPast();
                @endphp
                <article class="rounded-2xl border border-gray-200 bg-white p-5">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                        <div>
                            <p class="text-sm font-bold text-gray-900">{{ $booking->package->name }}</p>
                            <a href="{{ route('user.booking.tutor.show', $booking->tentor) }}" class="mt-1 inline-flex items-center text-sm text-gray-500 hover:text-primary">
                                <i class="ri-user-star-line mr-1"></i>{{ $booking->tentor->name }}
                                @if($booking->tentor->expertise)
                                    <span class="text-gray-300">•</span> {{ $booking->tentor->expertise }}
                                @endif
                            </a>
                        </div>
                        <span class="w-fit rounded-full border px-3 py-1 text-xs font-bold {{ $statusClasses[$booking->status] ?? $statusClasses['cancelled'] }}">
                            {{ $statusLabels[$booking->status] ?? ucfirst($booking->status) }}
                        </span>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                            <p class="text-xs text-gray-500">Waktu yang kamu ajukan</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ $booking->requested_start_at->translatedFormat('l, d M Y • H:i') }} WIB</p>
                        </div>
                        @if($booking->scheduled_start_at)
                            <div class="rounded-xl border border-primary/20 bg-primary/5 p-3">
                                <p class="text-xs text-gray-500">{{ $booking->status === 'counter_proposed' ? 'Usulan dari tutor' : 'Jadwal final' }}</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800">{{ $booking->scheduled_start_at->translatedFormat('l, d M Y • H:i') }} WIB</p>
                            </div>
                        @endif
                    </div>

                    @if($booking->student_notes)
                        <p class="mt-3 text-sm text-gray-600"><span class="font-semibold text-gray-700">Catatanmu:</span> {{ $booking->student_notes }}</p>
                    @endif
                    @if($booking->tutor_notes)
                        <p class="mt-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600"><span class="font-semibold text-gray-700">Catatan tutor:</span> {{ $booking->tutor_notes }}</p>
                    @endif

                    @if($booking->status === 'approved' && $booking->session)
                        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-4">
                            @if($booking->session->location)
                                <span class="text-sm text-gray-600"><i class="ri-map-pin-line mr-1 text-primary"></i>{{ $booking->session->location }}</span>
                            @endif
                            @if($booking->session->meeting_url)
                                <a href="{{ $booking->session->meeting_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline">
                                    <i class="ri-video-chat-line"></i>Buka meeting
                                </a>
                            @endif
                            <a href="{{ route('user.class-schedule.index', ['period' => 'all']) }}" class="ml-auto inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline">Lihat jadwal <i class="ri-arrow-right-line"></i></a>
                        </div>
                    @elseif($booking->status === 'counter_proposed')
                        <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                            <form method="POST" action="{{ route('user.booking.accept-counter', $booking) }}">
                                @csrf
                                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Terima waktu ini</button>
                            </form>
                            <form method="POST" action="{{ route('user.booking.cancel', $booking) }}" onsubmit="return confirm('Batalkan permintaan booking ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">Batalkan</button>
                            </form>
                        </div>
                    @elseif($booking->status === 'pending')
                        <div class="mt-4 flex justify-end border-t border-gray-100 pt-4">
                            <form method="POST" action="{{ route('user.booking.cancel', $booking) }}" onsubmit="return confirm('Batalkan permintaan booking ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">Batalkan permintaan</button>
                            </form>
                        </div>
                    @endif

                    @if($canReview)
                        <details class="mt-4 border-t border-gray-100 pt-4" @if(!$booking->review) open @endif>
                            <summary class="cursor-pointer text-sm font-bold text-gray-800">
                                <i class="ri-star-line mr-1 text-amber-500"></i>
                                {{ $booking->review ? 'Rating kamu: '.$booking->review->rating.'/5 — ubah penilaian' : 'Beri rating untuk Tutor' }}
                            </summary>
                            <form method="POST" action="{{ route('user.booking.review.store', $booking) }}" class="mt-4 space-y-3">
                                @csrf
                                <fieldset>
                                    <legend class="text-xs font-semibold text-gray-600">Nilai pengalaman belajar</legend>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @for($rating = 1; $rating <= 5; $rating++)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="rating" value="{{ $rating }}" required @checked(old('rating', $booking->review?->rating) == $rating) class="peer sr-only">
                                                <span class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-bold text-gray-600 peer-checked:border-amber-400 peer-checked:bg-amber-50 peer-checked:text-amber-700">
                                                    {{ $rating }} <i class="ri-star-fill text-amber-400"></i>
                                                </span>
                                            </label>
                                        @endfor
                                    </div>
                                </fieldset>
                                <label class="block">
                                    <span class="text-xs font-semibold text-gray-600">Review <span class="font-normal text-gray-400">(opsional)</span></span>
                                    <textarea name="comment" rows="3" maxlength="2000" placeholder="Bagikan pengalaman belajar kamu bersama Tutor ini" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary">{{ old('comment', $booking->review?->comment) }}</textarea>
                                </label>
                                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan penilaian</button>
                            </form>
                        </details>
                    @endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center">
                    <i class="ri-time-line text-3xl text-gray-300"></i>
                    <p class="mt-2 text-sm font-semibold text-gray-700">Belum ada permintaan booking</p>
                </div>
            @endforelse

            {{ $bookings->links() }}
        </section>
    </div>
</div>
@endsection
