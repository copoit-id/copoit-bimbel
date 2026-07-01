@extends('admin.layout.admin')
@section('title', 'Paket Kelas')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.package.index') }}" title="Manajemen Paket" />
            <x-breadcrumb-item href="" title="Kelas" />
        </x-slot>
    </x-breadcrumb>
    <x-btn title="Tambah Kelas"
        route="{{ route('admin.package.class.create', ['package_id' => $package->package_id]) }}" icon="ri-add-fill">
    </x-btn>
</div>

<div class="package-bimbel bg-white p-8 rounded-lg border border-border">
    <x-page-desc title="Kelas - {{ $package->name }}" description="Pilih kelas yang akan ditambahkan ke paket">
    </x-page-desc>

    <div class="relative overflow-x-auto mt-4">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 w-16">
                        <input type="checkbox" id="select-all"
                            autocomplete="off"
                            class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                    </th>
                    <th scope="col" class="px-6 py-3">Tanggal & Waktu</th>
                    <th scope="col" class="px-6 py-3 text-center">Judul</th>
                    <th scope="col" class="px-6 py-3 text-center">Mentor</th>
                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                    <th scope="col" class="px-6 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $class)
                @php
                $isInPackage = $class->detailPackages->isNotEmpty();
                @endphp
                <tr
                    class="bg-white border-b border-dashed border-gray-200 text-grey3 {{ $isInPackage ? 'bg-green-50' : '' }}">
                    <td class="px-6 py-4">
                        <input type="checkbox"
                            class="class-checkbox w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2"
                            autocomplete="off"
                            data-class-id="{{ $class->class_id }}" {{ $isInPackage ? 'checked' : '' }}>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            @if($isInPackage)
                            <i class="ri-check-circle-fill text-green-500"></i>
                            @endif
                            <div>
                                <p class="font-semibold">
                                    {{ \Carbon\Carbon::parse($class->schedule_time)->translatedFormat('l, d F Y') }}
                                </p>
                                <p>Pukul {{ \Carbon\Carbon::parse($class->schedule_time)->format('H:i') }} WIB</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">{{ $class->title }}</td>
                    <td class="px-6 py-4 text-center">{{ $class->tentor?->name ?? $class->mentor ?? '-' }}</td>
                    <td class="px-6 py-4 text-center">
                        @if($class->status == 'upcoming')
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">Akan Datang</span>
                        @elseif($class->status == 'completed')
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Selesai</span>
                        @else
                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Dibatalkan</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center items-center gap-2">
                            @if($class->zoom_link)
                            <a href="{{ $class->zoom_link }}" target="_blank" class="text-gray-500 hover:text-primary">
                                <i class="ri-video-on-line text-xl"></i>
                            </a>
                            @endif
                            @if($class->drive_link)
                            <a href="{{ $class->drive_link }}" target="_blank"
                                class="text-gray-500 hover:text-blue-600">
                                <i class="ri-folder-line text-xl"></i>
                            </a>
                            @endif
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
                            <i class="ri-calendar-line text-4xl text-gray-300 mb-2"></i>
                            <p>Belum ada kelas tersedia</p>
                            <a href="{{ route('admin.package.class.create', $package->package_id) }}"
                                class="text-primary hover:underline mt-2">
                                Buat kelas baru
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($classes->hasPages())
    <div class="flex justify-center mt-4">
        {{ $classes->links() }}
    </div>
    @endif

    <!-- Summary -->
    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
        <div class="flex justify-between items-center">
            <p class="text-sm text-gray-600">
                <span class="font-medium" id="selected-count">{{ $selectedClassCount ?? 0 }}</span> kelas dipilih dari {{ $classes->total() }} total kelas
            </p>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.class-checkbox');
    const selectAll = document.getElementById('select-all');
    const selectedCount = document.getElementById('selected-count');
    let totalSelectedCount = {{ (int) ($selectedClassCount ?? 0) }};
    let savingCount = 0;

    checkboxes.forEach(checkbox => {
        checkbox.defaultChecked = checkbox.checked;
    });

    function updateUI() {
        const checkedCount = document.querySelectorAll('.class-checkbox:checked').length;
        selectedCount.textContent = totalSelectedCount;

        const totalCheckboxes = checkboxes.length;
        selectAll.checked = totalCheckboxes > 0 && checkedCount === totalCheckboxes;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < totalCheckboxes;
        selectAll.disabled = savingCount > 0;
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', async function() {
            await syncClassCheckbox(this);
        });
    });

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(checkbox => {
            if (checkbox.checked !== this.checked) {
                checkbox.checked = this.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        });
    });

    async function syncClassCheckbox(checkbox) {
        const classId = checkbox.dataset.classId;
        const previousState = checkbox.defaultChecked;
        const nextState = checkbox.checked;

        checkbox.disabled = true;
        savingCount++;
        updateUI();

        try {
            const response = await fetch(`/admin/paket/{{ $package->package_id }}/kelas/${classId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    selected: nextState
                }),
                keepalive: true
            });

            if (!response.ok) {
                throw new Error('Gagal menyimpan perubahan');
            }

            checkbox.defaultChecked = nextState;
            totalSelectedCount += nextState ? 1 : -1;
            totalSelectedCount = Math.max(0, totalSelectedCount);
            showNotification('Perubahan berhasil disimpan', 'success');

        } catch (error) {
            checkbox.checked = previousState;
            showNotification('Terjadi kesalahan saat menyimpan', 'error');
        } finally {
            savingCount = Math.max(0, savingCount - 1);
            checkbox.disabled = false;
            updateUI();
        }
    }

    document.querySelectorAll('nav[role="navigation"] a, .pagination a').forEach(link => {
        link.addEventListener('click', function(event) {
            if (savingCount > 0) {
                event.preventDefault();
                showNotification('Tunggu sampai perubahan selesai tersimpan', 'error');
            }
        });
    });

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `fixed bottom-4 right-4 px-4 py-3 rounded-lg z-50 ${
            type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'
        }`;
        notification.innerHTML = `<p>${message}</p>`;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    updateUI();
});
</script>
@endsection
