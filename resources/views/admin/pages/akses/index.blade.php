@extends('admin.layout.admin')
@section('title', 'Akses User')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Akses User" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="Akses User - Kelola & Pengajuan"></x-page-desc>

<!-- Summary Cards -->
<div class="grid grid-cols-3 gap-4 mt-6">
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Total User Akses</p>
                <p class="text-2xl font-bold text-primary">{{ number_format($totalUserAccess) }}</p>
            </div>
            <i class="ri-user-line text-3xl text-primary"></i>
        </div>
    </div>
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Akses Aktif</p>
                <p class="text-2xl font-bold text-primary">{{ number_format($activeAccess) }}</p>
            </div>
            <i class="ri-key-line text-3xl text-primary"></i>
        </div>
    </div>
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Pengajuan Pending</p>
                <p class="text-2xl font-bold text-primary">{{ number_format($pendingRequestCount) }}</p>
            </div>
            <i class="ri-notification-line text-3xl text-primary"></i>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-8">
    <button type="button"
        class="akses-tab px-5 py-2 rounded-lg bg-primary text-white font-semibold"
        data-section="packages">
        Kelola Akses
    </button>
    <button type="button"
        class="akses-tab px-5 py-2 rounded-lg border border-primary text-primary font-semibold"
        data-section="requests">
        Pengajuan Akses ({{ $pendingRequestCount }})
    </button>
</div>

<!-- Package List -->
<div id="akses-section-packages" data-layout="grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
    @foreach($packages as $package)
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm capitalize">{{ $package->type_package
                }}</span>
            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">{{ ucfirst($package->status)
                }}</span>
        </div>
        <h3 class="text-lg font-bold mb-2">{{ $package->name }}</h3>
        <p class="text-gray-600 text-sm mb-4">{{ Str::limit($package->description, 100) }}</p>
        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
            <span>Total User: {{ $package->user_access_count ?? 0 }}</span>
            <span>Aktif: {{ $package->active_users_count ?? 0 }}</span>
        </div>
        <a href="{{ route('admin.akses.show', $package->package_id) }}"
            class="block w-full text-center bg-primary text-white py-2 rounded-lg hover:bg-primary/90">
            Kelola Akses
        </a>
    </div>
    @endforeach
</div>

<!-- Pending Requests -->
<div id="akses-section-requests" data-layout="block" class="hidden mt-6">
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Pengajuan Akses Gratis Bersyarat</h3>
            <p class="text-sm text-gray-500">Review bukti peserta sebelum memberikan akses paket gratis bersyarat.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold">Pengguna</th>
                        <th class="px-6 py-3 text-left font-semibold">Paket</th>
                        <th class="px-6 py-3 text-left font-semibold">Bukti & Catatan</th>
                        <th class="px-6 py-3 text-left font-semibold">Status</th>
                        <th class="px-6 py-3 text-left font-semibold">Diajukan</th>
                        <th class="px-6 py-3 text-left font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($pendingRequests as $requestItem)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-900">{{ $requestItem->user->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $requestItem->user->email ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-900">{{ $requestItem->package->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500 capitalize">{{ $requestItem->package->type_package ?? '' }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $proofPath = $requestItem->requirement_proof_path;
                                $proofExists = $proofPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($proofPath);
                                $proofUrl = $proofExists ? asset('storage/' . ltrim($proofPath, '/')) : null;
                            @endphp
                            <div class="flex flex-col gap-1">
                                @if($proofUrl)
                                <a href="{{ $proofUrl }}" target="_blank"
                                    class="inline-flex items-center gap-1 text-primary text-sm font-semibold hover:underline">
                                    <i class="ri-attachment-line"></i>
                                    Lihat Bukti
                                </a>
                                @else
                                <span class="text-xs text-gray-400">Belum ada bukti</span>
                                @endif
                                <p class="text-xs text-gray-500">{{ Str::limit($requestItem->notes, 80) }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700">
                                Menunggu Review
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ optional($requestItem->created_at)->format('d M Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-2">
                                <form action="{{ route('admin.akses.requests.approve', $requestItem->user_package_access_id) }}"
                                    method="POST" onsubmit="return confirm('Setujui pengajuan akses ini?');">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-lg border border-green-200 bg-green-50 px-3 py-1 text-xs font-semibold text-green-700 hover:bg-green-100">
                                        <i class="ri-check-line"></i> Setujui
                                    </button>
                                </form>
                                <form action="{{ route('admin.akses.requests.reject', $requestItem->user_package_access_id) }}"
                                    method="POST" onsubmit="return confirm('Tolak pengajuan akses ini?');">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 hover:bg-red-100">
                                        <i class="ri-close-line"></i> Tolak
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                            Tidak ada pengajuan akses menunggu persetujuan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    console.log('Package access management loaded');
});
</script>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sectionMap = {
            packages: document.getElementById('akses-section-packages'),
            requests: document.getElementById('akses-section-requests'),
        };

        const tabs = document.querySelectorAll('.akses-tab');

        function setActive(section) {
            Object.entries(sectionMap).forEach(([key, el]) => {
                if (!el) {
                    return;
                }

                const layoutClass = el.dataset.layout ?? 'block';

                if (key === section) {
                    el.classList.remove('hidden');
                    el.classList.add(layoutClass);
                } else {
                    el.classList.add('hidden');
                    el.classList.remove(layoutClass);
                }
            });

            tabs.forEach(tab => {
                if (tab.dataset.section === section) {
                    tab.classList.add('bg-primary', 'text-white');
                    tab.classList.remove('border', 'border-primary', 'text-primary');
                } else {
                    tab.classList.remove('bg-primary', 'text-white');
                    tab.classList.add('border', 'border-primary', 'text-primary');
                }
            });
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', function () {
                const section = tab.dataset.section;
                setActive(section);
            });
        });

        setActive('packages');
    });
</script>
@endsection
