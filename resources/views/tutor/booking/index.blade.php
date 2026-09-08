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
            <p class="mt-1 text-sm text-gray-500">Tinjau pengajuan jadwal, lalu setujui atau tolak.</p>
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
                    <div class="mt-5 flex flex-wrap gap-3 border-t border-gray-100 pt-5">
                        <button type="button" onclick="openApproveBookingModal(this)" data-booking-id="{{ $booking->id }}" data-booking-start="{{ $booking->requested_start_at->format('Y-m-d\\TH:i') }}" data-payment-model="{{ $booking->package?->bookingRule?->payment_model }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Setujui</button>
                        <button type="button" onclick="openRejectBookingModal(this)" data-booking-id="{{ $booking->id }}" class="rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Tolak</button>
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

<div id="approveBookingModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4"><h2 class="font-bold text-gray-900">Setujui Pengajuan</h2><button type="button" onclick="closeBookingModals()" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100"><i class="ri-close-line text-xl"></i></button></div>
        <form id="approveBookingForm" method="POST" class="space-y-4 p-5">@csrf
            <label class="block"><span class="text-sm font-semibold text-gray-700">Waktu final</span><input id="approveBookingStart" type="datetime-local" name="scheduled_start_at" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></label>
            <label id="approveSessionPriceField" class="hidden block"><span class="text-sm font-semibold text-gray-700">Nominal pertemuan</span><input type="number" name="session_price" min="0" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></label>
            <button class="w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white">Setujui & buat jadwal</button>
        </form>
    </div>
</div>
<div id="rejectBookingModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4"><h2 class="font-bold text-gray-900">Tolak Pengajuan</h2><button type="button" onclick="closeBookingModals()" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100"><i class="ri-close-line text-xl"></i></button></div>
        <form id="rejectBookingForm" method="POST" class="space-y-4 p-5">@csrf
            <label class="block"><span class="text-sm font-semibold text-gray-700">Alasan penolakan</span><textarea name="tutor_notes" rows="4" maxlength="1000" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Tuliskan alasan penolakan"></textarea></label>
            <button class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Tolak pengajuan</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const approveBookingUrl = @json(route('tutor.booking.approve', ['booking' => '__BOOKING_ID__']));
const rejectBookingUrl = @json(route('tutor.booking.reject', ['booking' => '__BOOKING_ID__']));
function openApproveBookingModal(button) { document.getElementById('approveBookingForm').action = approveBookingUrl.replace('__BOOKING_ID__', button.dataset.bookingId); document.getElementById('approveBookingStart').value = button.dataset.bookingStart; const priceField = document.getElementById('approveSessionPriceField'); priceField.classList.toggle('hidden', button.dataset.paymentModel !== 'per_session'); priceField.querySelector('input').required = button.dataset.paymentModel === 'per_session'; document.getElementById('approveBookingModal').classList.replace('hidden', 'flex'); }
function openRejectBookingModal(button) { document.getElementById('rejectBookingForm').action = rejectBookingUrl.replace('__BOOKING_ID__', button.dataset.bookingId); document.getElementById('rejectBookingModal').classList.replace('hidden', 'flex'); }
function closeBookingModals() { document.querySelectorAll('#approveBookingModal, #rejectBookingModal').forEach((modal) => { modal.classList.add('hidden'); modal.classList.remove('flex'); }); }
</script>
@endsection
