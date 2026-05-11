@extends('admin.layout.admin')
@section('title', 'Materi Paket')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.package.index') }}" title="Manajemen Paket" />
            <x-breadcrumb-item href="" title="Materi" />
        </x-slot>
    </x-breadcrumb>
</div>

<div class="package-bimbel bg-white p-8 rounded-lg border border-border">
    <x-page-desc title="Materi - {{ $package->name }}" description="Pilih materi yang akan ditambahkan ke paket">
    </x-page-desc>

    <div class="relative overflow-x-auto mt-4">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 w-16">
                        <input type="checkbox" id="select-all"
                            class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                    </th>
                    <th scope="col" class="px-6 py-3">Nama Materi</th>
                    <th scope="col" class="px-6 py-3 text-center">Tipe</th>
                    <th scope="col" class="px-6 py-3 text-center">Durasi</th>
                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $material)
                @php
                $isLinked = $material->detailPackages->where('package_id', $package->package_id)->isNotEmpty();
                @endphp
                <tr class="bg-white border-b border-dashed border-gray-200 text-grey3 {{ $isLinked ? 'bg-green-50' : '' }}">
                    <td class="px-6 py-4">
                        <input type="checkbox"
                            class="material-checkbox w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2"
                            data-material-id="{{ $material->material_id }}" {{ $isLinked ? 'checked' : '' }}>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            @if($isLinked)
                            <i class="ri-check-circle-fill text-green-500"></i>
                            @endif
                            <div>
                                <p class="font-semibold">{{ $material->title }}</p>
                                <p class="text-sm text-gray-500">{{ Str::limit($material->description, 50) }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">
                            {{ $material->type_label ?? 'Video' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($material->formatted_duration)
                        {{ $material->formatted_duration }}
                        @else
                        -
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($material->is_active)
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Aktif</span>
                        @else
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Nonaktif</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class="ri-file-text-line text-4xl text-gray-300 mb-2"></i>
                            <p>Belum ada materi tersedia</p>
                            <a href="{{ route('admin.material.create') }}" class="text-primary hover:underline mt-2">
                                Buat materi baru
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($materials->hasPages())
    <div class="flex justify-center mt-4">
        {{ $materials->links() }}
    </div>
    @endif

    <!-- Summary -->
    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
        <div class="flex justify-between items-center">
            <p class="text-sm text-gray-600">
                <span class="font-medium" id="selected-count">{{ $materials->filter(fn($m) => $m->detailPackages->where('package_id', $package->package_id)->isNotEmpty())->count() }}</span>
                materi dipilih dari {{ $materials->total() }} total materi
            </p>
            <button id="save-changes"
                class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 disabled:opacity-50" disabled>
                Simpan Perubahan
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.material-checkbox');
    const selectAll = document.getElementById('select-all');
    const saveButton = document.getElementById('save-changes');
    const selectedCount = document.getElementById('selected-count');
    let initialState = new Set();
    let changedItems = new Set();

    // Store initial state
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            initialState.add(checkbox.dataset.materialId);
        }
    });

    function updateUI() {
        const checkedCount = document.querySelectorAll('.material-checkbox:checked').length;
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
            const materialId = this.dataset.materialId;
            const isChecked = this.checked;
            const wasInitiallyChecked = initialState.has(materialId);

            if (isChecked !== wasInitiallyChecked) {
                changedItems.add(materialId);
            } else {
                changedItems.delete(materialId);
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

        const promises = Array.from(changedItems).map(materialId => {
            const checkbox = document.querySelector(`.material-checkbox[data-material-id="${materialId}"]`);
            return fetch(`/admin/paket/{{ $package->package_id }}/materi/${materialId}/toggle`, {
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
                    initialState.add(checkbox.dataset.materialId);
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