@extends('admin.layout.admin')

@section('title', 'Pengajuan Akses')

@section('content')
@php
    $itemName = $item->name ?? $item->title ?? 'Item';
    $tab = match($type) {
        'package' => 'packages',
        'video' => 'videos',
        'document' => 'documents',
        'live_session' => 'live',
        'class' => 'classes',
        'tryout' => 'tryouts',
        default => $type,
    };
    $typeLabel = match($type) {
        'packages', 'package' => 'Paket',
        'videos', 'video' => 'Video',
        'documents', 'document' => 'Dokumen',
        'live', 'live_session' => $clientBranding['live_session_label'] ?? 'Kelas Belajar',
        'classes', 'class' => 'Kelas Zoom',
        'tryouts', 'tryout' => 'Tryout',
        'tes_koran' => 'Tes Koran',
        default => 'Item',
    };
@endphp

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.akses.index', ['tab' => $tab]) }}" title="Akses User" />
            <x-breadcrumb-item href="" title="Pengajuan Akses" />
        </x-slot>
    </x-breadcrumb>
</div>

<div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">{{ $typeLabel }}</div>
            <h1 class="text-xl font-bold text-gray-900 mt-1">{{ $itemName }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $pendingRequests->count() }} pengajuan menunggu persetujuan</p>
        </div>
        <a href="{{ route('admin.akses.index', ['tab' => $tab]) }}"
           class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
            <i class="ri-arrow-left-line mr-1"></i>Kembali
        </a>
    </div>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    @if($pendingRequests->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pengguna</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bukti</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catatan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diajukan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach($pendingRequests as $accessRequest)
                @php
                    $customBooking = null;
                    if ($pendingRequestType === 'package') {
                        $proofPaths = collect($accessRequest->requirement_proof_paths ?? [])
                            ->when($accessRequest->requirement_proof_path, fn ($paths) => $paths->push($accessRequest->requirement_proof_path))
                            ->filter()
                            ->unique()
                            ->values();
                        $noteText = $accessRequest->requirement_user_notes;
                        $amountText = 'Gratis bersyarat';
                        $customBooking = $accessRequest->bookingRequests->first();
                    } else {
                        $paymentDetails = is_array($accessRequest->payment_details)
                            ? $accessRequest->payment_details
                            : (json_decode($accessRequest->payment_details ?? '[]', true) ?: []);
                        $proofPaths = collect($paymentDetails['requirement_proof_paths'] ?? [])
                            ->when($paymentDetails['proof_path'] ?? null, fn ($paths, $proofPath) => $paths->push($proofPath))
                            ->filter()
                            ->unique()
                            ->values();
                        $noteText = $paymentDetails['requirement_user_notes'] ?? ($paymentDetails['proof_name'] ?? null);
                        $amountText = ($accessRequest->payment_method ?? null) === 'free_conditional'
                            ? 'Gratis bersyarat'
                            : 'Rp ' . number_format((float) $accessRequest->total_amount, 0, ',', '.');
                    }
                @endphp
                <tr>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ optional($accessRequest->user)->name ?? 'User tidak ditemukan' }}</div>
                        <div class="text-xs text-gray-500">{{ optional($accessRequest->user)->email ?? '-' }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ $amountText }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($proofPaths->isNotEmpty())
                        <div class="space-y-1">
                            @foreach($proofPaths as $proofIndex => $proofPath)
                            <a href="{{ asset('storage/' . $proofPath) }}" target="_blank"
                               class="text-primary hover:underline text-sm block">
                                <i class="ri-attachment-line mr-1"></i>Bukti {{ $proofIndex + 1 }}
                            </a>
                            @endforeach
                        </div>
                        @else
                        <span class="text-gray-400 text-sm">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                        @if($noteText)
                        <p class="line-clamp-3">{{ $noteText }}</p>
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $accessRequest->created_at->format('d M Y H:i') }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-2">
                            @if($customBooking)
                            <button type="button"
                                class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-100"
                                data-schedule-tutor="{{ $customBooking->tentor?->name ?? 'Belum tersedia' }}"
                                data-schedule-start="{{ $customBooking->requested_start_at?->format('d M Y H:i') ?? '-' }}"
                                data-schedule-status="{{ ucfirst(str_replace('_', ' ', $customBooking->status)) }}"
                                data-schedule-notes="{{ $customBooking->student_notes ?? '-' }}"
                                onclick="openScheduleRequestModal(this)">
                                <i class="ri-calendar-schedule-line"></i>Lihat Pengajuan Jadwal
                            </button>
                            @else
                            <form action="{{ $pendingRequestType === 'package' ? route('admin.akses.requests.approve', $accessRequest) : route('admin.pembayaran.item.confirm', $accessRequest) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-xs font-medium hover:bg-green-200">
                                    Setujui
                                </button>
                            </form>
                            <form action="{{ $pendingRequestType === 'package' ? route('admin.akses.requests.reject', $accessRequest) : route('admin.pembayaran.item.reject', $accessRequest) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-xs font-medium hover:bg-red-200">
                                    Tolak
                                </button>
                            </form>
                            @if($pendingRequestType === 'individual')
                            <a href="{{ route('admin.pembayaran.item.show', $accessRequest) }}"
                               class="px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg text-xs font-medium hover:bg-gray-200">
                                Detail
                            </a>
                            @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="p-12 text-center text-gray-500">
        <i class="ri-inbox-line text-4xl text-gray-300 mb-3"></i>
        <p class="font-medium text-gray-700">Belum ada pengajuan akses</p>
        <p class="text-sm text-gray-400 mt-1">Pengajuan untuk item ini akan muncul di sini.</p>
    </div>
    @endif
</div>

<div id="scheduleRequestModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-gray-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="scheduleRequestModalTitle">
    <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="border-b border-gray-100 bg-white px-6 py-5 text-gray-900">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary"><i class="ri-calendar-schedule-line text-2xl"></i></span>
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Detail pengajuan</p><h2 id="scheduleRequestModalTitle" class="mt-0.5 text-lg font-bold text-gray-900">Jadwal Custom</h2></div>
                </div>
                <button type="button" onclick="closeScheduleRequestModal()" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Tutup"><i class="ri-close-line text-xl"></i></button>
            </div>
        </div>
        <div class="space-y-5 p-6">
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Tutor pilihan</p><p id="scheduleRequestTutor" class="mt-2 font-bold text-gray-900">-</p></div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Waktu diminta</p><p id="scheduleRequestStart" class="mt-2 font-bold text-gray-900">-</p></div>
            </div>
            <div class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-4 py-3"><span class="text-sm font-semibold text-amber-800">Status pengajuan</span><span id="scheduleRequestStatus" class="rounded-full bg-white px-3 py-1 text-xs font-bold text-amber-700">-</span></div>
            <div class="rounded-xl border border-gray-100 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Catatan peserta</p><p id="scheduleRequestNotes" class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700">-</p></div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function openScheduleRequestModal(button) {
    document.getElementById('scheduleRequestTutor').textContent = button.dataset.scheduleTutor || '-';
    document.getElementById('scheduleRequestStart').textContent = button.dataset.scheduleStart || '-';
    document.getElementById('scheduleRequestStatus').textContent = button.dataset.scheduleStatus || '-';
    document.getElementById('scheduleRequestNotes').textContent = button.dataset.scheduleNotes || '-';
    const modal = document.getElementById('scheduleRequestModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeScheduleRequestModal() {
    const modal = document.getElementById('scheduleRequestModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.getElementById('scheduleRequestModal')?.addEventListener('click', function (event) {
    if (event.target === this) closeScheduleRequestModal();
});
</script>
@endsection
