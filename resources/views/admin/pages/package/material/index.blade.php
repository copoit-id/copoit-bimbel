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
                            autocomplete="off"
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
                            autocomplete="off"
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
                <span class="font-medium" id="selected-count">{{ $selectedMaterialCount ?? 0 }}</span>
                materi dipilih dari {{ $materials->total() }} total materi
            </p>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.material-checkbox');
    const selectAll = document.getElementById('select-all');
    const selectedCount = document.getElementById('selected-count');
    let totalSelectedCount = {{ (int) ($selectedMaterialCount ?? 0) }};
    let savingCount = 0;

    checkboxes.forEach(checkbox => {
        checkbox.defaultChecked = checkbox.checked;
    });

    function updateUI() {
        const checkedCount = document.querySelectorAll('.material-checkbox:checked').length;
        selectedCount.textContent = totalSelectedCount;

        const totalCheckboxes = checkboxes.length;
        selectAll.checked = totalCheckboxes > 0 && checkedCount === totalCheckboxes;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < totalCheckboxes;
        selectAll.disabled = savingCount > 0;
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', async function() {
            await syncMaterialCheckbox(this);
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

    async function syncMaterialCheckbox(checkbox) {
        const materialId = checkbox.dataset.materialId;
        const previousState = checkbox.defaultChecked;
        const nextState = checkbox.checked;

        checkbox.disabled = true;
        savingCount++;
        updateUI();

        try {
            const response = await fetch('{{ route('admin.package.material.toggle', ['package_id' => $package->package_id, 'material_id' => '__MATERIAL_ID__']) }}'.replace('__MATERIAL_ID__', materialId), {
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
