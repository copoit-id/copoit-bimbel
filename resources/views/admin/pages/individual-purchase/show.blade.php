@extends('admin.layout.admin')

@section('title', 'Detail Pembelian Individual')

@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.pembayaran.index') }}" title="Pembayaran" />
            <x-breadcrumb-item href="" title="Detail" />
        </x-slot>
    </x-breadcrumb>
</div>

<div class="flex items-center gap-3 mt-4">
    <a href="{{ route('admin.pembayaran.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-xl font-bold text-gray-800">Detail Pembelian Individual</h1>
        <p class="text-sm text-gray-500">{{ $purchase->transaction_id }}</p>
    </div>
</div>

@if(session('success'))
<div class="mt-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mt-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
    {{ session('error') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <!-- Left: Transaction Info -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Informasi Transaksi</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 mb-1">Transaction ID</p>
                    <p class="font-medium text-gray-800">{{ $purchase->transaction_id }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Item</p>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs font-medium">{{ $itemType }}</span>
                        <p class="font-medium text-gray-800 text-sm">{{ $itemTitle }}</p>
                    </div>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Harga</p>
                    <p class="font-medium text-gray-800">Rp {{ number_format($purchase->price, 0, ',', '.') }}</p>
                </div>
                @if($purchase->discount_amount > 0)
                <div>
                    <p class="text-xs text-gray-500 mb-1">Diskon @if($purchase->discount_code)<span class="text-primary font-medium">({{ $purchase->discount_code }})</span>@endif</p>
                    <p class="font-medium text-gray-800">- Rp {{ number_format($purchase->discount_amount, 0, ',', '.') }}</p>
                </div>
                @endif
                <div>
                    <p class="text-xs text-gray-500 mb-1">Total Bayar</p>
                    <p class="font-bold text-lg" style="color: var(--client-color-primary, #1C3259)">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Metode</p>
                    <p class="font-medium text-gray-800 capitalize">{{ $purchase->payment_method }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Tanggal Pengajuan</p>
                    <p class="font-medium text-gray-800">{{ $purchase->created_at->format('d M Y, H:i') }}</p>
                </div>
                @if($purchase->approved_at)
                <div>
                    <p class="text-xs text-gray-500 mb-1">Diproses Tanggal</p>
                    <p class="font-medium text-gray-800">{{ \Carbon\Carbon::parse($purchase->approved_at)->format('d M Y, H:i') }}</p>
                </div>
                @endif
                @if($purchase->approver)
                <div>
                    <p class="text-xs text-gray-500 mb-1">Diproses Oleh</p>
                    <p class="font-medium text-gray-800">{{ $purchase->approver->name }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Payment Proof -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            @php
                $proofPaths = collect($paymentDetails['requirement_proof_paths'] ?? [])
                    ->when($paymentDetails['proof_path'] ?? null, fn ($paths, $path) => $paths->push($path))
                    ->filter()
                    ->unique()
                    ->values();
                $isConditional = ($purchase->payment_method ?? null) === 'free_conditional';
            @endphp
            <h3 class="font-semibold text-gray-800 mb-4">{{ $isConditional ? 'Bukti Persyaratan' : 'Bukti Pembayaran' }}</h3>
            @if($isConditional && !empty($paymentDetails['conditional_requirement']))
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 whitespace-pre-line">{{ $paymentDetails['conditional_requirement'] }}</div>
            @endif
            @if(!empty($paymentDetails['requirement_user_notes']))
            <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700 whitespace-pre-line">{{ $paymentDetails['requirement_user_notes'] }}</div>
            @endif
            @if($proofPaths->isNotEmpty())
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($proofPaths as $proofIndex => $path)
                @php
                    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                @endphp
                <a href="{{ asset('storage/' . $path) }}" target="_blank" class="block rounded-lg border border-gray-200 overflow-hidden hover:border-primary">
                    @if($isImage)
                    <img src="{{ asset('storage/' . $path) }}" alt="Bukti {{ $proofIndex + 1 }}" class="w-full object-contain max-h-72 bg-gray-50">
                    @else
                    <div class="p-4 text-sm text-primary">
                        <i class="ri-attachment-line mr-1"></i>Buka Bukti {{ $proofIndex + 1 }}
                    </div>
                    @endif
                </a>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-400 italic">{{ $isConditional ? 'Tidak ada bukti persyaratan' : 'Tidak ada bukti pembayaran' }}</p>
            @endif
        </div>
    </div>

    <!-- Right: Status & Actions -->
    <div class="space-y-6">
        <!-- User Info -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-800 mb-4">User</h3>
            <div class="flex items-center gap-3 mb-4">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($purchase->user->name ?? 'U') }}&background=444&color=fff&size=48"
                     class="w-12 h-12 rounded-full">
                <div>
                    <p class="font-medium text-gray-800">{{ $purchase->user->name ?? 'Unknown' }}</p>
                    <p class="text-sm text-gray-500">{{ $purchase->user->email ?? '' }}</p>
                </div>
            </div>
            <a href="{{ route('admin.user.show', $purchase->user_id) }}"
               class="text-sm text-primary hover:underline">
                <i class="ri-external-link-line mr-1"></i>Lihat Profil User
            </a>
        </div>

        <!-- Status -->
        <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Status</h3>
            @if($purchase->status === 'pending')
            <div class="flex items-center gap-2 mb-4">
                <span class="px-3 py-1.5 bg-amber-100 text-amber-700 rounded-full text-sm font-medium">
                    <i class="ri-time-line mr-1"></i>Pending
                </span>
            </div>
            <div class="flex gap-2">
                <form action="{{ route('admin.pembayaran.item.confirm', $purchase) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" onclick="return confirm('Setujui pembelian ini? User akan mendapat akses.')"
                            class="w-full px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">
                        <i class="ri-check-line mr-1"></i>Setujui
                    </button>
                </form>
                <form action="{{ route('admin.pembayaran.item.reject', $purchase) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" onclick="return confirm('Tolak pembelian ini?')"
                            class="w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors">
                        <i class="ri-close-line mr-1"></i>Tolak
                    </button>
                </form>
            </div>
            @elseif($purchase->status === 'approved')
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                    <i class="ri-check-line mr-1"></i>Disetujui
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-2">User sudah mendapat akses ke item ini.</p>
            @else
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 bg-red-100 text-red-700 rounded-full text-sm font-medium">
                    <i class="ri-close-line mr-1"></i>Ditolak
                </span>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
