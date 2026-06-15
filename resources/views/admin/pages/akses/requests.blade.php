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
        'tryout' => 'tryouts',
        default => $type,
    };
    $typeLabel = match($type) {
        'packages', 'package' => 'Paket',
        'videos', 'video' => 'Video',
        'documents', 'document' => 'Dokumen',
        'live', 'live_session' => 'Live Session',
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
                    if ($pendingRequestType === 'package') {
                        $proofPaths = collect($accessRequest->requirement_proof_paths ?? [])
                            ->when($accessRequest->requirement_proof_path, fn ($paths) => $paths->push($accessRequest->requirement_proof_path))
                            ->filter()
                            ->unique()
                            ->values();
                        $noteText = $accessRequest->requirement_user_notes;
                        $amountText = 'Gratis bersyarat';
                    } else {
                        $paymentDetails = is_array($accessRequest->payment_details)
                            ? $accessRequest->payment_details
                            : (json_decode($accessRequest->payment_details ?? '[]', true) ?: []);
                        $proofPaths = collect([$paymentDetails['proof_path'] ?? null])->filter()->values();
                        $noteText = $paymentDetails['proof_name'] ?? null;
                        $amountText = 'Rp ' . number_format((float) $accessRequest->total_amount, 0, ',', '.');
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
                            <form action="{{ $pendingRequestType === 'package' ? route('admin.akses.requests.approve', $accessRequest) : route('admin.individual-purchase.confirm', $accessRequest) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-xs font-medium hover:bg-green-200">
                                    Setujui
                                </button>
                            </form>
                            <form action="{{ $pendingRequestType === 'package' ? route('admin.akses.requests.reject', $accessRequest) : route('admin.individual-purchase.reject', $accessRequest) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-xs font-medium hover:bg-red-200">
                                    Tolak
                                </button>
                            </form>
                            @if($pendingRequestType === 'individual')
                            <a href="{{ route('admin.individual-purchase.show', $accessRequest) }}"
                               class="px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg text-xs font-medium hover:bg-gray-200">
                                Detail
                            </a>
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
@endsection
