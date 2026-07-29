@extends('tutor.layout')

@section('content')
@php
    $tabs = [
        'waiting' => 'Perlu Diproses',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];
    $statusLabels = [
        'pending' => 'Menunggu keputusan',
        'counter_proposed' => 'Menunggu siswa',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Permintaan Booking</h1>
            <p class="mt-1 text-sm text-gray-500">Setujui waktu siswa atau berikan usulan jadwal lain.</p>
        </div>
        @if($waitingCount > 0)
            <span class="w-fit rounded-full border border-primary/20 bg-primary/5 px-3 py-1.5 text-sm font-bold text-primary">{{ $waitingCount }} perlu diproses</span>
        @endif
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

    <nav class="flex gap-2 overflow-x-auto border-b border-gray-200 pb-3" aria-label="Filter booking">
        @foreach($tabs as $key => $label)
            <a href="{{ route('tutor.booking.index', ['status' => $key]) }}"
                @if($status === $key) aria-current="page" @endif
                class="shrink-0 rounded-lg border px-4 py-2 text-sm font-semibold {{ $status === $key ? 'border-primary bg-primary text-white' : 'border-gray-200 bg-white text-gray-600 hover:border-primary/40 hover:text-primary' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    <div class="space-y-4">
        @forelse($bookings as $booking)
            <article class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold text-gray-900">{{ $booking->user->name }}</h2>
                            <span class="rounded-full border border-gray-200 px-2.5 py-0.5 text-xs font-semibold text-gray-600">{{ $booking->package->name }}</span>
                            @if($booking->studyGroup)
                                <span class="rounded-full border border-primary/20 bg-primary/5 px-2.5 py-0.5 text-xs font-semibold text-primary">
                                    Rombel · {{ $booking->studyGroup->target_participants }} siswa
                                </span>
                            @endif
                        </div>
                        <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-sm text-gray-500">
                            <span><i class="ri-mail-line mr-1"></i>{{ $booking->user->email }}</span>
                            @if($booking->user->phone)
                                <span><i class="ri-whatsapp-line mr-1"></i>{{ $booking->user->phone }}</span>
                            @endif
                        </div>
                    </div>
                    <span class="w-fit rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-bold text-gray-600">
                        {{ $statusLabels[$booking->status] ?? ucfirst($booking->status) }}
                    </span>
                </div>

                @if($booking->studyGroup)
                    <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500">{{ $booking->studyGroup->name }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ $booking->studyGroup->members->pluck('user.name')->filter()->join(', ') }}</p>
                    </div>
                @endif

                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Waktu dari siswa</p>
                        <p class="mt-1 font-bold text-gray-800">{{ $booking->requested_start_at->translatedFormat('l, d M Y • H:i') }} WIB</p>
                        <p class="mt-1 text-xs text-gray-500">s.d. {{ $booking->requested_end_at->format('H:i') }} WIB</p>
                    </div>
                    @if($booking->scheduled_start_at)
                        <div class="rounded-xl border border-primary/20 bg-primary/5 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $booking->status === 'counter_proposed' ? 'Waktu yang Anda usulkan' : 'Jadwal final' }}</p>
                            <p class="mt-1 font-bold text-gray-800">{{ $booking->scheduled_start_at->translatedFormat('l, d M Y • H:i') }} WIB</p>
                            @if($booking->scheduled_end_at)
                                <p class="mt-1 text-xs text-gray-500">s.d. {{ $booking->scheduled_end_at->format('H:i') }} WIB</p>
                            @endif
                        </div>
                    @endif
                </div>

                @if($booking->student_notes)
                    <div class="mt-3 rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-600">
                        <span class="font-semibold text-gray-800">Catatan siswa:</span> {{ $booking->student_notes }}
                    </div>
                @endif
                @if($booking->tutor_notes)
                    <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                        <span class="font-semibold text-gray-800">Catatan tutor:</span> {{ $booking->tutor_notes }}
                    </div>
                @endif

                @if($booking->status === 'pending')
                    <div class="mt-5 grid gap-3 border-t border-gray-100 pt-5 lg:grid-cols-3">
                        <details class="rounded-xl border border-gray-200 p-4" open>
                            <summary class="cursor-pointer text-sm font-bold text-gray-800">Setujui</summary>
                            <form method="POST" action="{{ route('tutor.booking.approve', $booking) }}" class="mt-4 space-y-3">
                                @csrf
                                <label class="block">
                                    <span class="text-xs font-semibold text-gray-600">Waktu final</span>
                                    <input type="datetime-local" name="scheduled_start_at" value="{{ $booking->requested_start_at->format('Y-m-d\TH:i') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                </label>
                                <label class="block">
                                    <span class="text-xs font-semibold text-gray-600">Lokasi <span class="font-normal text-gray-400">(opsional)</span></span>
                                    <input type="text" name="location" maxlength="255" placeholder="Ruang 2 / Online" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                </label>
                                <label class="block">
                                    <span class="text-xs font-semibold text-gray-600">Link meeting <span class="font-normal text-gray-400">(opsional)</span></span>
                                    <input type="url" name="meeting_url" maxlength="1000" placeholder="https://..." class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                </label>
                                <button class="w-full rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white">Setujui & buat jadwal</button>
                            </form>
                        </details>

                        <details class="rounded-xl border border-gray-200 p-4">
                            <summary class="cursor-pointer text-sm font-bold text-gray-800">Usulkan waktu lain</summary>
                            <form method="POST" action="{{ route('tutor.booking.propose', $booking) }}" class="mt-4 space-y-3">
                                @csrf
                                <label class="block">
                                    <span class="text-xs font-semibold text-gray-600">Waktu alternatif</span>
                                    <input type="datetime-local" name="scheduled_start_at" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                </label>
                                <label class="block">
                                    <span class="text-xs font-semibold text-gray-600">Alasan / catatan</span>
                                    <textarea name="tutor_notes" rows="3" maxlength="1000" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary"></textarea>
                                </label>
                                <button class="w-full rounded-lg border border-primary px-3 py-2 text-sm font-semibold text-primary hover:bg-primary hover:text-white">Kirim usulan</button>
                            </form>
                        </details>

                        <details class="rounded-xl border border-gray-200 p-4">
                            <summary class="cursor-pointer text-sm font-bold text-gray-800">Tolak</summary>
                            <form method="POST" action="{{ route('tutor.booking.reject', $booking) }}" class="mt-4 space-y-3">
                                @csrf
                                <label class="block">
                                    <span class="text-xs font-semibold text-gray-600">Alasan penolakan</span>
                                    <textarea name="tutor_notes" rows="3" maxlength="1000" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary"></textarea>
                                </label>
                                <button class="w-full rounded-lg border border-red-300 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Tolak permintaan</button>
                            </form>
                        </details>
                    </div>
                @elseif($booking->status === 'counter_proposed')
                    <p class="mt-5 border-t border-gray-100 pt-4 text-sm text-gray-500"><i class="ri-time-line mr-1"></i>Menunggu siswa menerima atau membatalkan usulan waktu.</p>
                @elseif($booking->status === 'approved' && $booking->session)
                    <div class="mt-5 flex flex-wrap items-center gap-4 border-t border-gray-100 pt-4 text-sm text-gray-600">
                        @if($booking->session->location)
                            <span><i class="ri-map-pin-line mr-1 text-primary"></i>{{ $booking->session->location }}</span>
                        @endif
                        @if($booking->session->meeting_url)
                            <a href="{{ $booking->session->meeting_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-primary hover:underline"><i class="ri-video-chat-line mr-1"></i>Buka meeting</a>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center">
                <i class="ri-inbox-line text-4xl text-gray-300"></i>
                <p class="mt-3 font-semibold text-gray-700">Tidak ada permintaan pada status ini</p>
            </div>
        @endforelse
    </div>

    {{ $bookings->links() }}
</div>
@endsection
