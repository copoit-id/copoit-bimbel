@extends('admin.layout.admin')
@section('title', 'Detail Akses User')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.akses.index') }}" title="Akses User" />
            <x-breadcrumb-item href="{{ route('admin.akses.show', ['package_id' => $package->package_id]) }}"
                title="{{ $package->name }}" />
            <x-breadcrumb-item href="" title="Detail User" />
        </x-slot>
    </x-breadcrumb>
    <div class="flex gap-2">
        @if($userAccess->status !== 'suspended')
        <button onclick="showExtendModal()"
            class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <i class="ri-refresh-line"></i>
            Perpanjang Akses
        </button>
        @endif

        @if($userAccess->status === 'active')
        <button onclick="confirmRevoke()"
            class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
            <i class="ri-close-line"></i>
            Cabut Akses
        </button>
        @elseif($userAccess->status === 'suspended')
        <form action="{{ route('admin.akses.toggle', [$package->package_id, $user->id]) }}" method="POST"
            class="inline">
            @csrf
            <button type="submit"
                class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="ri-check-line"></i>
                Aktifkan Kembali
            </button>
        </form>
        @endif
    </div>
</div>
<x-page-desc title="Detail Akses - {{ $user->name }} ({{ $package->name }})"></x-page-desc>

<!-- User Profile & Package Info -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <div class="bg-white rounded-lg border border-border p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi User</h3>
        <div class="flex items-center gap-4">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=6366f1&color=fff&size=80"
                class="w-16 h-16 rounded-full">
            <div>
                <h4 class="text-xl font-bold text-gray-800">{{ $user->name }}</h4>
                <p class="text-gray-600">{{ $user->email }}</p>
                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                    <span>
                        <i class="ri-user-line mr-1"></i>
                        Username: {{ $user->username }}
                    </span>
                    <span
                        class="px-2 py-1 {{ $user->status == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} rounded text-xs">
                        {{ ucfirst($user->status) }}
                    </span>
                </div>
                <div class="mt-2 text-sm text-gray-500">
                    <span>
                        <i class="ri-calendar-line mr-1"></i>
                        Bergabung: {{ $user->created_at->translatedFormat('d F Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-border p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Paket</h3>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-600">Nama Paket:</span>
                <span class="font-medium">{{ $package->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Tipe Paket:</span>
                <span class="font-medium">{{ ucfirst($package->type_package) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Harga:</span>
                <span class="font-medium">
                    @if($package->price == 0)
                    <span class="text-green-600">Gratis</span>
                    @else
                    Rp {{ number_format($package->price, 0, ',', '.') }}
                    @endif
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Status Paket:</span>
                <span
                    class="px-2 py-1 {{ $package->status == 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }} rounded-full text-xs">
                    {{ $package->status == 'active' ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Access Status -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
    <div class="bg-white border border-border rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Status Akses</p>
                <p
                    class="text-2xl font-bold {{ $userAccess->is_active ? 'text-green-600' : ($userAccess->status === 'suspended' ? 'text-red-600' : 'text-gray-600') }}">
                    @if($userAccess->status === 'active')
                    @if($userAccess->is_expired)
                    Expired
                    @else
                    Aktif
                    @endif
                    @elseif($userAccess->status === 'suspended')
                    Suspended
                    @else
                    {{ ucfirst($userAccess->status) }}
                    @endif
                </p>
            </div>
            <i class="ri-key-line text-3xl {{ $userAccess->is_active ? 'text-green-600' : 'text-gray-400' }}"></i>
        </div>
    </div>

    <div class="bg-white border border-border rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Tanggal Mulai</p>
                <p class="text-lg font-semibold text-gray-800">{{ $userAccess->start_date->translatedFormat('d M Y') }}
                </p>
            </div>
            <i class="ri-calendar-line text-3xl text-blue-600"></i>
        </div>
    </div>

    <div class="bg-white border border-border rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Tanggal Berakhir</p>
                <p class="text-lg font-semibold text-gray-800">{{ $userAccess->end_date->translatedFormat('d M Y') }}
                </p>
            </div>
            <i class="ri-calendar-check-line text-3xl text-orange-600"></i>
        </div>
    </div>

    <div class="bg-white border border-border rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Sisa Hari</p>
                <p
                    class="text-2xl font-bold {{ $userAccess->days_remaining <= 7 ? 'text-red-600' : 'text-green-600' }}">
                    {{ round($userAccess->days_remaining) }} Hari
                </p>
            </div>
            <i
                class="ri-time-line text-3xl {{ $userAccess->days_remaining <= 7 ? 'text-red-600' : 'text-green-600' }}"></i>
        </div>
    </div>
</div>

<!-- Payment Info -->
<div class="bg-white rounded-lg border border-border p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Pembayaran</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="flex justify-between">
            <span class="text-gray-600">Jumlah Pembayaran:</span>
            <span class="font-medium">{{ $userAccess->formatted_payment_amount }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600">Status Pembayaran:</span>
            <span>{!! $userAccess->payment_status_badge !!}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600">Dibuat Oleh:</span>
            <span class="font-medium">
                @if($userAccess->createdBy)
                {{ $userAccess->createdBy->name }}
                @else
                System
                @endif
            </span>
        </div>
    </div>

    @if($userAccess->notes)
    <div class="mt-4 pt-4 border-t border-gray-200">
        <p class="text-sm text-gray-600 mb-1">Catatan:</p>
        <p class="text-gray-800">{{ $userAccess->notes }}</p>
    </div>
    @endif
</div>

<!-- Recent Activity -->
<div class="bg-white rounded-lg border border-border p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Aktivitas Terbaru</h3>
    <div class="space-y-4">
        @forelse($recentActivities as $activity)
        <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
            <div class="flex-shrink-0">
                <i class="{{ $activity->icon }} {{ $activity->color }} text-xl"></i>
            </div>
            <div class="flex-1">
                <p class="font-medium text-gray-800">{{ $activity->activity }}</p>
                <p class="text-sm text-gray-500">{{ $activity->time->diffForHumans() }}</p>
            </div>
        </div>
        @empty
        <p class="text-gray-500 text-center py-4">Belum ada aktivitas</p>
        @endforelse
    </div>
</div>

<!-- Extend Access Modal -->
<div id="extend-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Perpanjang Akses</h3>
        <form action="{{ route('admin.akses.extend', [$package->package_id, $user->id]) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="extend_end_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Berakhir
                    Baru</label>
                <input type="date" id="extend_end_date" name="end_date"
                    value="{{ $userAccess->end_date->addDays(30)->format('Y-m-d') }}"
                    min="{{ date('Y-m-d', strtotime('+1 day')) }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
            <div class="mb-4">
                <label for="extend_notes" class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                <textarea id="extend_notes" name="notes" rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/20"
                    placeholder="Alasan perpanjangan..."></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="hideExtendModal()"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Batal</button>
                <button type="submit"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Perpanjang</button>
            </div>
        </form>
    </div>
</div>

@if(session('success'))
<div class="fixed bottom-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg z-50">
    <p>{{ session('success') }}</p>
</div>
@endif

@if(session('error'))
<div class="fixed bottom-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg z-50">
    <p>{{ session('error') }}</p>
</div>
@endif

@endsection

@section('scripts')
<script>
    function showExtendModal() {
    document.getElementById('extend-modal').classList.remove('hidden');
}

function hideExtendModal() {
    document.getElementById('extend-modal').classList.add('hidden');
}

function confirmRevoke() {
    if (confirm('Apakah Anda yakin ingin mencabut akses user ini? Akses akan langsung dihentikan.')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.akses.revoke", [$package->package_id, $user->id]) }}';

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto hide notifications
document.addEventListener('DOMContentLoaded', function() {
    const notifications = document.querySelectorAll('.fixed.bottom-4.right-4');
    notifications.forEach(notification => {
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    });
});
</script>
@endsection
