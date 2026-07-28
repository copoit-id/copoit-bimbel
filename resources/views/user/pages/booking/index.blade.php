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
    get selected() { return this.options[this.selectedAccess] || null },
}">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Booking Jadwal</h1>
        <p class="mt-1 text-sm text-gray-500">Usulkan waktu belajar, lalu tunggu konfirmasi dari tutor.</p>
    </div>

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
                        <select name="user_package_access_id" x-model="selectedAccess" @change="selectedTutor = ''" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:ring-primary">
                            @foreach($accesses as $access)
                                <option value="{{ $access->user_package_access_id }}">{{ $access->package->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div x-show="selected" class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                            <p class="text-xs text-gray-500">Kuota tersedia</p>
                            <p class="mt-1 font-bold text-gray-800">
                                <span x-text="Math.max((selected?.quota || 0) - (selected?.used || 0), 0)"></span>
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

                    <button type="submit" :disabled="!selected || selected.used >= selected.quota || selected.tutors.length === 0" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">
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
                <article class="rounded-2xl border border-gray-200 bg-white p-5">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                        <div>
                            <p class="text-sm font-bold text-gray-900">{{ $booking->package->name }}</p>
                            <p class="mt-1 text-sm text-gray-500">
                                <i class="ri-user-star-line mr-1"></i>{{ $booking->tentor->name }}
                                @if($booking->tentor->expertise)
                                    <span class="text-gray-300">•</span> {{ $booking->tentor->expertise }}
                                @endif
                            </p>
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
