@extends('admin.layout.admin')
@section('title', 'Kelola Akses User')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.akses.index') }}" title="Akses User" />
            <x-breadcrumb-item href="" title="PAKET A{{ $package->package_id }} 2025" />
        </x-slot>
    </x-breadcrumb>
    <div class="flex gap-2">
        <a href="{{ route('admin.akses.create', ['package_id' => $package->package_id]) }}"
            class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
            <i class="ri-add-line"></i>
            Tambah Akses User
        </a>
    </div>
</div>
<x-page-desc title="Kelola Akses - PAKET BIMBEL A{{ $package->package_id }} 2025"></x-page-desc>

<div class="package-bimbel bg-white p-8 rounded-lg border border-border mt-6">
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center gap-4">
            <div class="relative">
                <input type="text" id="access-search" placeholder="Cari user..."
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
            </div>
            <select id="access-status-filter"
                class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <option value="">Status Akses</option>
                <option value="aktif">Aktif</option>
                <option value="expired">Expired</option>
                <option value="akan_expired">Akan Expired</option>
            </select>
            <button id="reset-access-filters"
                class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="ri-refresh-line"></i> Reset
            </button>
        </div>
        <div id="access-count" class="text-sm text-gray-500">
            Total: <span class="font-medium text-gray-700">0 User</span>
        </div>
    </div>

    <div class="relative overflow-x-auto">
        <table class="w-full text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">User</th>
                    <th scope="col" class="px-6 py-3 text-center">Tanggal Mulai</th>
                    <th scope="col" class="px-6 py-3 text-center">Tanggal Berakhir</th>
                    <th scope="col" class="px-6 py-3 text-center">Sisa Hari</th>
                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                    <th scope="col" class="px-6 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($userAccesses as $access)
                @php
                $now = \Carbon\Carbon::now();
                $endDate = \Carbon\Carbon::parse($access->end_date);
                $sisaHari = $now->diffInDays($endDate, false);

                if ($sisaHari > 7) {
                $status = 'aktif';
                } elseif ($sisaHari > 0) {
                $status = 'akan_expired';
                } else {
                $status = 'expired';
                }
                @endphp

                <tr class="access-row bg-white border-b border-dashed border-gray-200 text-grey3"
                    data-user="{{ strtolower($access->user->name ?? 'unknown') }}"
                    data-email="{{ strtolower($access->user->email ?? 'unknown') }}" data-status="{{ $status }}">
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($access->user->name ?? 'Unknown') }}&background=6366f1&color=fff"
                                class="w-10 h-10 rounded-full">
                            <div>
                                <p class="font-medium">{{ $access->user->name ?? 'Unknown User' }}</p>
                                <p class="text-sm text-gray-500">{{ $access->user->email ?? 'No email' }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-4 text-center">
                        <div class="flex flex-col items-center">
                            <p class="font-medium">{{ \Carbon\Carbon::parse($access->start_date)->format('d M Y') }}</p>
                            <p class="text-sm text-gray-500">{{
                                \Carbon\Carbon::parse($access->start_date)->format('H:i') }}</p>
                        </div>
                    </td>

                    <td class="px-6 py-4 text-center">
                        <div class="flex flex-col items-center">
                            <p class="font-medium">{{ \Carbon\Carbon::parse($access->end_date)->format('d M Y') }}</p>
                            <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($access->end_date)->format('H:i')
                                }}</p>
                        </div>
                    </td>

                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center">
                            @if($sisaHari > 0)
                            <span class="text-dark">{{ abs($sisaHari) }} hari</span>
                            @else
                            <span class="text-red-600 font-medium">Expired</span>
                            @endif
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex justify-center items-center">
                            @if($status == 'aktif')
                            <span
                                class="px-3 py-1 border border-green-700 bg-green-100 text-green-700 rounded-full text-sm">Aktif</span>
                            @elseif($status == 'akan_expired')
                            <span
                                class="px-3 py-1 border border-yellow-700 bg-yellow-100 text-yellow-700 rounded-full text-sm">Akan
                                Expired</span>
                            @else
                            <span
                                class="px-3 py-1 border border-red-700 bg-red-100 text-red-700 rounded-full text-sm">Expired</span>
                            @endif
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex justify-center items-center gap-2">
                            <a href="{{ route('admin.akses.detail', ['package_id' => $package->package_id, 'user_id' => $access->user->id ?? 0]) }}"
                                class="text-gray-500 hover:text-primary">
                                <i class="ri-eye-line text-xl"></i>
                            </a>
                            <button class="text-gray-500 hover:text-green-600">
                                <i class="ri-refresh-line text-xl"></i>
                            </button>
                            <button class="text-gray-500 hover:text-red-500">
                                <i class="ri-delete-bin-line text-xl"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class="ri-user-line text-4xl text-gray-300 mb-2"></i>
                            <p>Belum ada user yang memiliki akses ke paket ini</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-between items-center mt-4">
        <p class="text-gray-500 text-sm">Menampilkan {{ $userAccesses->count() }} user</p>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('access-search');
    const statusFilter = document.getElementById('access-status-filter');
    const resetButton = document.getElementById('reset-access-filters');
    const accessCount = document.getElementById('access-count');
    const accessRows = document.querySelectorAll('.access-row');

    function filterAccess() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedStatus = statusFilter.value;

        let visibleCount = 0;

        accessRows.forEach(row => {
            const userName = row.dataset.user || '';
            const userEmail = row.dataset.email || '';
            const accessStatus = row.dataset.status || '';

            const matchesSearch = userName.includes(searchTerm) || userEmail.includes(searchTerm);
            const matchesStatus = !selectedStatus || accessStatus === selectedStatus;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        updateAccessCount(visibleCount);
    }

    function updateAccessCount(count) {
        accessCount.innerHTML = `Total: <span class="font-medium text-gray-700">${count} User</span>`;
    }

    function resetFilters() {
        searchInput.value = '';
        statusFilter.value = '';
        filterAccess();
    }

    // Event listeners
    searchInput.addEventListener('input', filterAccess);
    statusFilter.addEventListener('change', filterAccess);
    resetButton.addEventListener('click', resetFilters);

    // Initial render
    filterAccess();
});
</script>
@endsection
