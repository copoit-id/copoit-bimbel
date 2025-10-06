@extends('admin.layout.admin')

@section('content')
<div class="space-y-6 w-full">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Manajemen Sertifikat</h2>
            <p class="text-gray-600">Kelola sertifikat dan validasi dokumen</p>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 lg:gap-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 w-full lg:w-auto">
                <div class="relative w-full sm:w-auto">
                    <input type="text" id="certificate-search" placeholder="Cari sertifikat..."
                        class="pl-10 pr-4 py-2 w-full sm:w-64 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <select id="certificate-status-filter"
                        class="border border-gray-300 rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="revoked">Dicabut</option>
                        <option value="expired">Expired</option>
                    </select>
                    <select id="certificate-date-filter"
                        class="border border-gray-300 rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Periode Terbit</option>
                        <option value="today">Hari Ini</option>
                        <option value="week">7 Hari Terakhir</option>
                        <option value="month">30 Hari Terakhir</option>
                        <option value="year">Tahun Ini</option>
                    </select>
                </div>
                <button id="reset-certificate-filters"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 w-full sm:w-auto">
                    <i class="ri-refresh-line"></i> Reset
                </button>
            </div>
            <div id="certificate-count" class="text-sm text-gray-500 w-full lg:w-auto text-left lg:text-right">
                Total: <span class="font-medium text-gray-700">0 Sertifikat</span>
            </div>
        </div>
    </div>

    <!-- Certificate Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden w-full">
        <div class="overflow-x-auto w-full">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 ">
                    <tr>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap">
                            <input type="checkbox" id="select-all-certificates"
                                class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                        </th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap w-full">No. Sertifikat</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap w-full">Nama</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap w-full">Pemegang</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap w-full">Tanggal Terbit</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap w-full">Status</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap w-full">Action</th>
                    </tr>
                </thead>
                <tbody id="certificate-table-body" class="bg-white divide-y divide-gray-200 ">
                    @forelse($certificates as $certificate)
                    <tr class="certificate-row hover:bg-gray-50" data-number="{{ $certificate->certificate_number }}"
                        data-name="{{ $certificate->certificate_name }}"
                        data-holder="{{ is_array($certificate->metadata) ? ($certificate->metadata['user_name'] ?? 'Unknown') : (json_decode($certificate->metadata, true)['user_name'] ?? 'Unknown') }}"
                        data-email="{{ is_array($certificate->metadata) ? ($certificate->metadata['user_email'] ?? '') : (json_decode($certificate->metadata, true)['user_email'] ?? '') }}"
                        data-status="{{ $certificate->status }}"
                        data-date="{{ $certificate->issued_date->format('Y-m-d') }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox"
                                class="certificate-checkbox w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2"
                                value="{{ $certificate->certificate_id }}">
                        </td>
                        <td class="px-6 py-4">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 truncate">{{ $certificate->certificate_number }}</p>
                                <p class="text-sm text-gray-500 truncate">ID: {{ $certificate->certificate_id }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="min-w-0">
                                <div class="font-medium text-gray-900 truncate">{{ $certificate->certificate_name }}
                                </div>
                                <div class="text-sm text-gray-500 truncate">{{ Str::limit($certificate->description ??
                                    '', 50) }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="min-w-0">
                                @php
                                $metadata = is_array($certificate->metadata) ? $certificate->metadata :
                                json_decode($certificate->metadata, true);
                                $userName = $metadata['user_name'] ?? 'Unknown User';
                                $userEmail = $metadata['user_email'] ?? 'No email';
                                @endphp
                                <p class="font-medium text-gray-900 truncate">{{ $userName }}</p>
                                <p class="text-sm text-gray-500 truncate">{{ $userEmail }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm">
                                <div class="font-medium text-gray-900">{{ $certificate->issued_date->format('d M Y') }}
                                </div>
                                @if($certificate->expired_date)
                                <div class="text-gray-500">s/d {{
                                    \Carbon\Carbon::parse($certificate->expired_date)->format('d M Y') }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @switch($certificate->status)
                            @case('active')
                            <span
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                            @break
                            @case('revoked')
                            <span
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Dicabut</span>
                            @break
                            @case('expired')
                            <span
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Expired</span>
                            @break
                            @default
                            <span
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">{{
                                ucfirst($certificate->status) }}</span>
                            @endswitch
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.certificate.show', $certificate) }}"
                                    class="text-blue-600 hover:text-blue-800" title="Lihat Detail">
                                    <i class="ri-eye-line text-lg"></i>
                                </a>
                                <a href="{{ route('admin.certificate.downloadTemplate', $certificate) }}"
                                    class="text-purple-600 hover:text-purple-800" title="Download">
                                    <i class="ri-download-line text-lg"></i>
                                </a>
                                <form action="{{ route('admin.certificate.destroy', $certificate) }}" method="POST"
                                    class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Hapus"
                                        onclick="return confirm('Yakin ingin menghapus sertifikat ini?')">
                                        <i class="ri-delete-bin-line text-lg"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="ri-award-line text-4xl text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada sertifikat</h3>
                                <p class="text-gray-500">Sertifikat akan muncul di sini setelah user menyelesaikan ujian
                                    sertifikasi</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- No Results Message -->
        <div id="no-certificate-results" class="hidden">
            <div class="text-center py-12">
                <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="ri-search-line text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada sertifikat ditemukan</h3>
                <p class="text-gray-500">Coba ubah kata kunci pencarian atau filter</p>
            </div>
        </div>

        @if($certificates->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200">
            {{ $certificates->links() }}
        </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('certificate-search');
    const statusFilter = document.getElementById('certificate-status-filter');
    const dateFilter = document.getElementById('certificate-date-filter');
    const resetButton = document.getElementById('reset-certificate-filters');
    const certificateCount = document.getElementById('certificate-count');
    const certificateRows = document.querySelectorAll('.certificate-row');
    const tableBody = document.getElementById('certificate-table-body');
    const noResults = document.getElementById('no-certificate-results');

    // Bulk actions
    const selectAllCheckbox = document.getElementById('select-all-certificates');
    const certificateCheckboxes = document.querySelectorAll('.certificate-checkbox');
    const selectedCount = document.getElementById('selected-count');

    function filterCertificates() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedStatus = statusFilter.value;
        const selectedDate = dateFilter.value;

        let visibleCount = 0;

        certificateRows.forEach(row => {
            const certificateNumber = row.dataset.number || '';
            const certificateName = row.dataset.name || '';
            const holderName = row.dataset.holder || '';
            const holderEmail = row.dataset.email || '';
            const certificateStatus = row.dataset.status || '';
            const certificateDate = row.dataset.date || '';

            const matchesSearch = certificateNumber.toLowerCase().includes(searchTerm) ||
                                certificateName.includes(searchTerm) ||
                                holderName.includes(searchTerm) ||
                                holderEmail.includes(searchTerm);
            const matchesStatus = !selectedStatus || certificateStatus === selectedStatus;
            const matchesDate = !selectedDate; // Date filtering would need backend support

            if (matchesSearch && matchesStatus && matchesDate) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Show/hide no results message
        if (visibleCount === 0 && certificateRows.length > 0) {
            noResults.classList.remove('hidden');
            tableBody.parentElement.style.display = 'none';
        } else {
            noResults.classList.add('hidden');
            tableBody.parentElement.style.display = 'block';
        }

        updateCertificateCount(visibleCount);
    }

    function updateCertificateCount(count) {
        certificateCount.innerHTML = `Total: <span class="font-medium text-gray-700">${count} Sertifikat</span>`;
    }

    function resetFilters() {
        searchInput.value = '';
        statusFilter.value = '';
        dateFilter.value = '';
        filterCertificates();
    }

    function updateSelectedCount() {
        const visibleCheckboxes = Array.from(certificateCheckboxes).filter(cb =>
            cb.closest('.certificate-row').style.display !== 'none'
        );
        const selectedCheckboxes = visibleCheckboxes.filter(cb => cb.checked);

        selectedCount.textContent = `${selectedCheckboxes.length} sertifikat dipilih`;

        // Update select all checkbox state
        if (selectedCheckboxes.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (selectedCheckboxes.length === visibleCheckboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
    }

    // Event listeners
    searchInput.addEventListener('input', filterCertificates);
    statusFilter.addEventListener('change', filterCertificates);
    dateFilter.addEventListener('change', filterCertificates);
    resetButton.addEventListener('click', resetFilters);

    // Bulk selection
    selectAllCheckbox.addEventListener('change', function() {
        const visibleCheckboxes = Array.from(certificateCheckboxes).filter(cb =>
            cb.closest('.certificate-row').style.display !== 'none'
        );

        visibleCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });

        updateSelectedCount();
    });

    certificateCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    // Initial render
    filterCertificates();
    updateSelectedCount();

    console.log('Certificate management scripts loaded');
});
</script>
@endsection
