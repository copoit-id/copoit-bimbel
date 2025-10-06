@extends('admin.layout.admin')
@section('title', 'Laporan User')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Laporan User" />
        </x-slot>
    </x-breadcrumb>
    <div class="flex gap-2">
        <button class="flex items-center gap-2 px-4 py-2 bg-green text-white rounded-lg hover:bg-green-700">
            <i class="ri-file-excel-line"></i>
            Export Excel
        </button>
        <button class="flex items-center gap-2 px-4 py-2 bg-red text-white rounded-lg hover:bg-red-700">
            <i class="ri-file-pdf-line"></i>
            Export PDF
        </button>
    </div>
</div>
<x-page-desc title="Laporan User - Data & Aktivitas"></x-page-desc>

<div class="package-bimbel bg-white p-8 rounded-lg border border-border mt-6">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 lg:gap-6 mb-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 w-full lg:w-auto">
            <div class="relative w-full sm:w-auto">
                <input type="text" id="report-search" placeholder="Cari user..."
                    class="pl-10 pr-4 py-2 w-full sm:w-64 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <select id="report-status-filter"
                    class="border border-gray-300 rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="tidak_aktif">Tidak Aktif</option>
                </select>
                <select id="report-period-filter"
                    class="border border-gray-300 rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">Periode</option>
                    <option value="7">7 Hari Terakhir</option>
                    <option value="30">30 Hari Terakhir</option>
                    <option value="90">90 Hari Terakhir</option>
                </select>
            </div>
            <button id="reset-report-filters"
                class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 w-full sm:w-auto">
                <i class="ri-refresh-line"></i> Reset
            </button>
        </div>
        <div id="report-count" class="text-sm text-gray-500 w-full lg:w-auto text-left lg:text-right">
            Total: <span class="font-medium text-gray-700">{{ $users->total() }} User</span>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-primary">Total User</p>
                    <p class="text-2xl font-bold text-primary">{{ $totalUsers }}</p>
                </div>
                <i class="ri-user-line text-3xl text-primary"></i>
            </div>
        </div>
        <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-primary">User Aktif</p>
                    <p class="text-2xl font-bold text-primary">{{ $activeUsers }}</p>
                </div>
                <i class="ri-user-line text-3xl text-primary"></i>
            </div>
        </div>
        <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-primary">Tryout Selesai</p>
                    <p class="text-2xl font-bold text-primary">{{ number_format($totalCompletedTryouts) }}</p>
                </div>
                <i class="ri-file-list-line text-3xl text-primary"></i>
            </div>
        </div>
        <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-primary">Sertifikat</p>
                    <p class="text-2xl font-bold text-primary">{{ $totalCertificates }}</p>
                </div>
                <i class="ri-award-line text-3xl text-primary"></i>
            </div>
        </div>
    </div>

    <div class="relative overflow-x-auto">
        <table class="w-full text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">User</th>
                    <th scope="col" class="px-6 py-3 text-center">Tryout Selesai</th>
                    <th scope="col" class="px-6 py-3 text-center">Rata-rata Nilai</th>
                    <th scope="col" class="px-6 py-3 text-center">Sertifikat</th>
                    <th scope="col" class="px-6 py-3 text-center">Terakhir Aktif</th>
                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                    <th scope="col" class="px-6 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr class="bg-white border-b border-dashed border-gray-200 text-grey3">
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=444444&color=fff"
                                class="w-10 h-10 rounded-full">
                            <div>
                                <p class="font-medium">{{ $user->name }}</p>
                                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center items-center">
                            <span class="text-lg font-medium text-gray-800">{{ $user->completed_tryouts }}</span>
                            <span class="text-sm text-gray-500 ml-1">tryout</span>
                        </div>
                    </td>

                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center items-center flex-col">
                            <span class="text-lg font-medium text-gray-800">{{ $user->avg_score }}</span>
                        </div>
                    </td>

                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center items-center">
                            @if($user->total_certificates > 0)
                            <span class="flex items-center gap-1 text-amber-600">
                                <i class="ri-award-line"></i>
                                {{ $user->total_certificates }}
                            </span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <span class="flex flex-col items-center text-sm">
                                <p>{{ $user->last_activity->format('d M Y') }}</p>
                                <p class="text-gray-500">{{ $user->last_activity->format('H:i') }}</p>
                            </span>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex justify-center items-center">
                            @if($user->is_active_user)
                            <span
                                class="px-3 py-1 border border-green-700 bg-green-100 text-green-700 rounded-full text-sm">Aktif</span>
                            @else
                            <span
                                class="px-3 py-1 border border-gray-700 bg-gray-100 text-gray-700 rounded-full text-sm">Tidak
                                Aktif</span>
                            @endif
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex justify-center items-center gap-2">
                            <a href="{{ route('admin.laporan.show', $user->id) }}"
                                class="text-gray-500 hover:text-primary">
                                <i class="ri-eye-line text-xl"></i>
                            </a>
                            <button class="text-gray-500 hover:text-green-600">
                                <i class="ri-download-line text-xl"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class="ri-user-line text-4xl text-gray-300 mb-2"></i>
                            <p>Belum ada data user</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="flex justify-between items-center mt-4">
        <p class="text-gray-500 text-sm">
            Menampilkan {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} user
        </p>
        <div class="flex items-center gap-2">
            {{ $users->links() }}
        </div>
    </div>
    @else
    <div class="flex justify-between items-center mt-4">
        <p class="text-gray-500 text-sm">Menampilkan {{ $users->count() }} user</p>
    </div>
    @endif
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('report-search');
    const statusFilter = document.getElementById('report-status-filter');
    const periodFilter = document.getElementById('report-period-filter');
    const resetButton = document.getElementById('reset-report-filters');
    const reportCount = document.getElementById('report-count');
    const tableRows = document.querySelectorAll('tbody tr');

    function filterReports() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedStatus = statusFilter.value;
        const selectedPeriod = periodFilter.value;

        let visibleCount = 0;

        tableRows.forEach(row => {
            if (row.querySelector('td[colspan]')) return; // Skip empty state row

            const userName = row.querySelector('.font-medium').textContent.toLowerCase();
            const userEmail = row.querySelector('.text-gray-500').textContent.toLowerCase();
            const statusBadge = row.querySelector('.px-3.py-1');
            const userStatus = statusBadge ? (statusBadge.textContent.trim() === 'Aktif' ? 'aktif' : 'tidak_aktif') : '';

            const matchesSearch = userName.includes(searchTerm) || userEmail.includes(searchTerm);
            const matchesStatus = !selectedStatus || userStatus === selectedStatus;
            const matchesPeriod = !selectedPeriod; // Period filter logic would need backend support

            if (matchesSearch && matchesStatus && matchesPeriod) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        updateReportCount(visibleCount);
    }

    function updateReportCount(count) {
        reportCount.innerHTML = `Total: <span class="font-medium text-gray-700">${count} User</span>`;
    }

    function resetFilters() {
        searchInput.value = '';
        statusFilter.value = '';
        periodFilter.value = '';
        filterReports();
    }

    // Event listeners
    searchInput.addEventListener('input', filterReports);
    statusFilter.addEventListener('change', filterReports);
    periodFilter.addEventListener('change', filterReports);
    resetButton.addEventListener('click', resetFilters);

    console.log('Laporan user scripts loaded');
});
</script>
@endsection
