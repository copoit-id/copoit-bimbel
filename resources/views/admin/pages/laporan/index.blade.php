@extends('admin.layout.admin')
@section('title', 'Laporan Tryout')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Laporan Tryout" />
        </x-slot>
    </x-breadcrumb>
    <div class="flex gap-2">
        <a href="{{ route('admin.laporan.export-excel') }}"
            class="flex items-center gap-2 px-4 py-2 bg-green text-white rounded-lg hover:bg-green-700">
            <i class="ri-file-excel-line"></i>
            Export Excel
        </a>
        <a href="{{ route('admin.laporan.export-pdf') }}"
            class="flex items-center gap-2 px-4 py-2 bg-red text-white rounded-lg hover:bg-red-700">
            <i class="ri-file-pdf-line"></i>
            Export PDF
        </a>
    </div>
</div>
<x-page-desc title="Monitor performa setiap tryout dan akses detail jawaban peserta"></x-page-desc>

<div class="package-bimbel bg-white p-8 rounded-lg border border-border mt-6">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 lg:gap-6 mb-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 w-full lg:w-auto">
            <div class="relative w-full sm:w-auto">
                <input type="text" id="report-search" placeholder="Cari tryout..."
                    class="pl-10 pr-4 py-2 w-full sm:w-64 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <select id="report-status-filter"
                    class="border border-gray-300 rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                </select>
            </div>
            <button id="reset-report-filters"
                class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 w-full sm:w-auto">
                <i class="ri-refresh-line"></i> Reset
            </button>
        </div>
        <div id="report-count" class="text-sm text-gray-500 w-full lg:w-auto text-left lg:text-right">
            Total: <span class="font-medium text-gray-700">{{ $tryouts->total() }} Tryout</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-primary/5 border border-primary/20 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-primary">Total Tryout</p>
                    <p class="text-2xl font-bold text-primary">{{ $summary['total_tryouts'] }}</p>
                </div>
                <i class="ri-file-list-3-line text-3xl text-primary"></i>
            </div>
        </div>
        <div class="bg-primary/5 border border-primary/20 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-primary">Tryout Aktif</p>
                    <p class="text-2xl font-bold text-primary">{{ $summary['active_tryouts'] }}</p>
                </div>
                <i class="ri-broadcast-line text-3xl text-primary"></i>
            </div>
        </div>
        <div class="bg-primary/5 border border-primary/20 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-primary">Total Pengerjaan</p>
                    <p class="text-2xl font-bold text-primary">{{ number_format($summary['total_attempts']) }}</p>
                </div>
                <i class="ri-user-voice-line text-3xl text-primary"></i>
            </div>
        </div>
        <div class="bg-primary/5 border border-primary/20 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-primary">Selesai</p>
                    <p class="text-2xl font-bold text-primary">{{ number_format($summary['completed_attempts']) }}</p>
                </div>
                <i class="ri-check-double-line text-3xl text-primary"></i>
            </div>
        </div>
    </div>

    <div class="relative overflow-x-auto">
        <table class="w-full text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Tryout</th>
                    <th scope="col" class="px-6 py-3 text-center">Subtest</th>
                    <th scope="col" class="px-6 py-3 text-center">Total Soal</th>
                    <th scope="col" class="px-6 py-3 text-center">Peserta</th>
                    <th scope="col" class="px-6 py-3 text-center">Completion</th>
                    <th scope="col" class="px-6 py-3 text-center">Rata-rata</th>
                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                    <th scope="col" class="px-6 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tryouts as $tryout)
                <tr class="bg-white border-b border-dashed border-gray-200 text-grey3">
                    <td class="py-3 px-4">
                        <div class="flex flex-col">
                            <p class="font-semibold text-gray-800 tryout-name">{{ $tryout->name }}</p>
                            <p class="text-sm text-gray-500">{{ $tryout->type_tryout === 'utbk_full' ? 'UTBK' : ucfirst($tryout->type_tryout) }}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-gray-800 font-medium">{{ $tryout->tryoutDetails->count() }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-gray-800 font-medium">{{ $tryout->total_questions }}</span>
                        <p class="text-xs text-gray-500">{{ $tryout->total_duration }} menit</p>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-gray-800 font-medium">{{ $tryout->total_attempts }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="inline-flex flex-col items-center">
                            <span class="font-semibold text-gray-800">{{ $tryout->completion_rate }}%</span>
                            <span class="text-xs text-gray-500">{{ $tryout->completed_attempts }} selesai</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-gray-800 font-medium">{{ $tryout->avg_score }}%</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                        $status = $tryout->is_active ? 'active' : 'inactive';
                        $statusClass = $status === 'active'
                        ? 'bg-green-100 text-green-700 border border-green-200'
                        : 'bg-gray-100 text-gray-700 border border-gray-200';
                        $statusText = $status === 'active' ? 'Aktif' : 'Tidak Aktif';
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-medium status-badge {{ $statusClass }}"
                            data-status="{{ $status }}">
                            {{ $statusText }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center items-center gap-2">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.laporan.show', $tryout->tryout_id) }}"
                                    class="flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full border border-primary text-primary hover:bg-primary hover:text-white transition">
                                    <i class="ri-bar-chart-2-line text-sm"></i>
                                    Statistik
                                </a>
                                @if($tryout->leaderboard_package_id)
                                <a href="{{ route('admin.leaderboard.show', [$tryout->leaderboard_package_id, $tryout->tryout_id]) }}"
                                    class="flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full border border-primary text-primary hover:bg-primary hover:text-white transition">
                                    <i class="ri-trophy-line text-sm"></i>
                                    Leaderboard
                                </a>
                                @endif
                                <a href="{{ route('admin.tryout.preview', $tryout->tryout_id) }}"
                                    class="flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full border border-primary text-primary hover:bg-primary hover:text-white transition">
                                    <i class="ri-eye-line text-sm"></i>
                                    Preview
                                </a>
                                @if($tryout->has_snapshot_proctoring)
                                    <a href="{{ route('admin.laporan.proctoring-snapshots', $tryout->tryout_id) }}"
                                        class="flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full border border-primary text-primary hover:bg-primary hover:text-white transition">
                                        <i class="ri-camera-line text-sm"></i>
                                        Snapshot
                                    </a>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class="ri-file-list-line text-4xl text-gray-300 mb-2"></i>
                            <p>Belum ada tryout</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tryouts->hasPages())
    <div class="flex justify-between items-center mt-4">
        <p class="text-gray-500 text-sm">
            Menampilkan {{ $tryouts->firstItem() ?? 0 }}-{{ $tryouts->lastItem() ?? 0 }} dari {{ $tryouts->total() }} tryout
        </p>
        <div class="flex items-center gap-2">
            {{ $tryouts->links() }}
        </div>
    </div>
    @else
    <div class="flex justify-between items-center mt-4">
        <p class="text-gray-500 text-sm">Menampilkan {{ $tryouts->count() }} tryout</p>
    </div>
    @endif
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('report-search');
        const statusFilter = document.getElementById('report-status-filter');
        const resetButton = document.getElementById('reset-report-filters');
        const reportCount = document.getElementById('report-count');
        const tableRows = document.querySelectorAll('tbody tr');

        function filterReports() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedStatus = statusFilter.value;

            let visibleCount = 0;

            tableRows.forEach(row => {
                if (row.querySelector('td[colspan]')) return;

                const tryoutName = row.querySelector('.tryout-name').textContent.toLowerCase();
                const statusBadge = row.querySelector('.status-badge');
                const tryoutStatus = statusBadge ? statusBadge.dataset.status : '';

                const matchesSearch = tryoutName.includes(searchTerm);
                const matchesStatus = !selectedStatus || selectedStatus === tryoutStatus;

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            reportCount.innerHTML = `Total: <span class="font-medium text-gray-700">${visibleCount} Tryout</span>`;
        }

        function resetFilters() {
            searchInput.value = '';
            statusFilter.value = '';
            filterReports();
        }

        searchInput.addEventListener('input', filterReports);
        statusFilter.addEventListener('change', filterReports);
        resetButton.addEventListener('click', resetFilters);
    });
</script>
@endsection
