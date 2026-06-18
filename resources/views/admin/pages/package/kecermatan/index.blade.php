@extends('admin.layout.admin')

@section('title', 'Kecermatan Paket')

@section('content')
<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.package.index') }}" title="Manajemen Paket" />
            <x-breadcrumb-item href="" title="Kecermatan" />
        </x-slot>
    </x-breadcrumb>
</div>

<div class="mt-4 rounded-lg border border-border bg-white p-8">
    <x-page-desc title="Kecermatan - {{ $package->name }}" description="Pilih kecermatan yang akan ditambahkan ke paket." />

    <div class="relative mt-4 overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-500">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr>
                    <th class="w-16 px-6 py-3"><input type="checkbox" id="select-all" class="h-4 w-4 rounded border-gray-300 text-primary"></th>
                    <th class="px-6 py-3">Nama</th>
                    <th class="px-6 py-3 text-center">Tipe</th>
                    <th class="px-6 py-3 text-center">Kolom</th>
                    <th class="px-6 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kecermatans as $kecermatan)
                @php($isLinked = $kecermatan->detailPackages->where('package_id', $package->package_id)->isNotEmpty())
                <tr class="border-b border-dashed border-gray-200 bg-white {{ $isLinked ? 'bg-green-50' : '' }}">
                    <td class="px-6 py-4">
                        <input type="checkbox" class="kecermatan-checkbox h-4 w-4 rounded border-gray-300 text-primary" data-kecermatan-id="{{ $kecermatan->id }}" @checked($isLinked)>
                    </td>
                    <td class="px-6 py-4 font-semibold text-gray-900">{{ $kecermatan->name }}</td>
                    <td class="px-6 py-4 text-center">{{ $kecermatan->typeLabel() }}</td>
                    <td class="px-6 py-4 text-center">{{ $kecermatan->columns_count }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="rounded-full px-2 py-1 text-xs {{ $kecermatan->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">{{ $kecermatan->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        Belum ada kecermatan. <a href="{{ route('admin.kecermatan.create') }}" class="text-primary hover:underline">Buat kecermatan baru</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
        <span class="font-semibold" id="selected-count">{{ $selectedKecermatanCount ?? 0 }}</span>
        kecermatan dipilih dari {{ $kecermatans->total() }} total
    </div>

    <div class="mt-4">{{ $kecermatans->links() }}</div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.kecermatan-checkbox');
    const selectAll = document.getElementById('select-all');
    const selectedCount = document.getElementById('selected-count');
    let totalSelectedCount = {{ (int) ($selectedKecermatanCount ?? 0) }};
    let savingCount = 0;

    checkboxes.forEach((checkbox) => {
        checkbox.defaultChecked = checkbox.checked;
        checkbox.addEventListener('change', () => syncCheckbox(checkbox));
    });

    selectAll?.addEventListener('change', function() {
        checkboxes.forEach((checkbox) => {
            if (checkbox.checked !== this.checked) {
                checkbox.checked = this.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        });
    });

    function updateUI() {
        selectedCount.textContent = totalSelectedCount;
        const checkedCount = document.querySelectorAll('.kecermatan-checkbox:checked').length;
        selectAll.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
        selectAll.disabled = savingCount > 0;
    }

    async function syncCheckbox(checkbox) {
        const previousState = checkbox.defaultChecked;
        const nextState = checkbox.checked;
        checkbox.disabled = true;
        savingCount++;
        updateUI();

        try {
            const response = await fetch(`/admin/paket/{{ $package->package_id }}/kecermatan/${checkbox.dataset.kecermatanId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ selected: nextState })
            });
            if (!response.ok) throw new Error('Gagal menyimpan perubahan');
            checkbox.defaultChecked = nextState;
            totalSelectedCount += nextState ? 1 : -1;
            totalSelectedCount = Math.max(0, totalSelectedCount);
        } catch (error) {
            checkbox.checked = previousState;
            alert('Terjadi kesalahan saat menyimpan');
        } finally {
            savingCount = Math.max(0, savingCount - 1);
            checkbox.disabled = false;
            updateUI();
        }
    }

    updateUI();
});
</script>
@endsection
