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
                    <td class="px-6 py-4 text-center">{{ $class->mentor ?? '-' }}</td>
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
                <span class="font-medium" id="selected-count">{{ $classes->where('detailPackages', '!=', null)->count()
                    }}</span> kelas dipilih dari {{ $classes->total() }} total kelas
            </p>
            <button id="save-changes"
                class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 disabled:opacity-50" disabled>
                Simpan Perubahan
            </button>
        </div>
    </div>
</div>

@if(session('success'))
<div class="fixed bottom-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg z-50">
    <p>{{ session('success') }}</p>
</div>
@endif

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.class-checkbox');
    const selectAll = document.getElementById('select-all');
    const saveButton = document.getElementById('save-changes');
    const selectedCount = document.getElementById('selected-count');
    let initialState = new Set();
    let changedItems = new Set();

    // Store initial state
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            initialState.add(checkbox.dataset.classId);
        }
    });

    function updateUI() {
        const checkedCount = document.querySelectorAll('.class-checkbox:checked').length;
        selectedCount.textContent = checkedCount;

        saveButton.disabled = changedItems.size === 0;

        // Update select all checkbox
        const totalCheckboxes = checkboxes.length;
        selectAll.checked = checkedCount === totalCheckboxes;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < totalCheckboxes;
    }

    // Handle individual checkbox changes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const classId = this.dataset.classId;
            const isChecked = this.checked;
            const wasInitiallyChecked = initialState.has(classId);

            if (isChecked !== wasInitiallyChecked) {
                changedItems.add(classId);
            } else {
                changedItems.delete(classId);
            }

            updateUI();
        });
    });

    // Handle select all
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(checkbox => {
            if (checkbox.checked !== this.checked) {
                checkbox.checked = this.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        });
    });

    // Handle save changes
    saveButton.addEventListener('click', async function() {
        this.disabled = true;
        this.textContent = 'Menyimpan...';

        const promises = Array.from(changedItems).map(classId => {
            return fetch(`/admin/paket/{{ $package->package_id }}/kelas/${classId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
        });

        try {
            await Promise.all(promises);

            // Update initial state
            initialState.clear();
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    initialState.add(checkbox.dataset.classId);
                }
            });

            changedItems.clear();

            // Show success message
            showNotification('Perubahan berhasil disimpan', 'success');

            // Reload page after short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);

        } catch (error) {
            showNotification('Terjadi kesalahan saat menyimpan', 'error');
        }

        this.disabled = false;
        this.textContent = 'Simpan Perubahan';
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