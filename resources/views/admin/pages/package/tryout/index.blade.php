@extends('admin.layout.admin')
@section('title', 'Paket Tryout')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.package.index') }}" title="Manajemen Paket" />
            <x-breadcrumb-item href="" title="Tryout" />
        </x-slot>
    </x-breadcrumb>
</div>

<div class="package-bimbel bg-white p-8 rounded-lg border border-border">
    <x-page-desc title="Tryout - {{ $package->name }}" description="Pilih tryout yang akan ditambahkan ke paket">
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
                    <th scope="col" class="px-6 py-3">Nama Tryout</th>
                    <th scope="col" class="px-6 py-3 text-center">Tipe</th>
                    <th scope="col" class="px-6 py-3 text-center">Durasi</th>
                    <th scope="col" class="px-6 py-3 text-center">Soal</th>
                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tryouts as $tryout)
                @php
                $isInPackage = $tryout->detailPackages->isNotEmpty();
                $totalQuestions = $tryout->tryoutDetails->sum(function($detail) {
                return $detail->questions->count() ?? 0;
                });
                $totalDuration = $tryout->tryoutDetails->sum('duration');
                @endphp
                <tr
                    class="bg-white border-b border-dashed border-gray-200 text-grey3 {{ $isInPackage ? 'bg-green-50' : '' }}">
                    <td class="px-6 py-4">
                        <input type="checkbox"
                            class="tryout-checkbox w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2"
                            autocomplete="off"
                            data-tryout-id="{{ $tryout->tryout_id }}" {{ $isInPackage ? 'checked' : '' }}>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            @if($isInPackage)
                            <i class="ri-check-circle-fill text-green-500"></i>
                            @endif
                            <div>
                                <p class="font-semibold">{{ $tryout->name }}</p>
                                <p class="text-sm text-gray-500">{{ Str::limit($tryout->description, 50) }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">
                            {{ strtoupper($tryout->type_tryout) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">{{ $totalDuration }} menit</td>
                    <td class="px-6 py-4 text-center">{{ $totalQuestions }} soal</td>
                    <td class="px-6 py-4 text-center">
                        @if($tryout->start_date > now())
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">Akan Datang</span>
                        @elseif($tryout->end_date < now()) <span
                            class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Selesai</span>
                            @else
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Aktif</span>
                            @endif
                    </td>
                </tr>

                <!-- Modal for SKD Full -->
                @if ($tryout->type_tryout == 'skd_full')
                <div id="modal-{{ $tryout->tryout_id }}" tabindex="-1" aria-hidden="true"
                    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                    <div class="relative p-4 w-full max-w-2xl max-h-full">
                        <div class="relative bg-white rounded-lg shadow">
                            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                                <h3 class="text-xl font-semibold text-gray-900">Pilih Subtest - {{ $tryout->name }}</h3>
                                <button type="button"
                                    class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center"
                                    data-modal-hide="modal-{{ $tryout->tryout_id }}">
                                    <i class="ri-close-line text-lg"></i>
                                </button>
                            </div>
                            <div class="p-4 md:p-5 space-y-4">
                                @foreach($tryout->tryoutDetails as $detail)
                                <a href="{{ route('admin.package.tryout.soal', ['package_id' => $package->package_id, 'tryout_detail_id' => $detail->tryout_detail_id]) }}"
                                    class="flex items-center justify-between w-full p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ strtoupper($detail->type_subtest) }}
                                        </h4>
                                        <p class="text-sm text-gray-500">{{ $detail->duration }} menit • {{
                                            $detail->questions->count() ?? 0 }} soal</p>
                                    </div>
                                    <i class="ri-arrow-right-line text-gray-400"></i>
                                </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class="ri-draft-line text-4xl text-gray-300 mb-2"></i>
                            <p>Belum ada tryout tersedia</p>
                            <a href="{{ route('admin.tryout.create') }}" class="text-primary hover:underline mt-2">
                                Buat tryout baru
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($tryouts->hasPages())
    <div class="flex justify-center mt-4">
        {{ $tryouts->links() }}
    </div>
    @endif

    <!-- Summary -->
    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
        <div class="flex justify-between items-center">
            <p class="text-sm text-gray-600">
                <span class="font-medium" id="selected-count">{{ $selectedTryoutCount ?? 0 }}</span> tryout dipilih dari {{ $tryouts->total() }} total tryout
            </p>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.tryout-checkbox');
    const selectAll = document.getElementById('select-all');
    const selectedCount = document.getElementById('selected-count');
    let totalSelectedCount = {{ (int) ($selectedTryoutCount ?? 0) }};
    let savingCount = 0;

    checkboxes.forEach(checkbox => {
        checkbox.defaultChecked = checkbox.checked;
    });

    function updateUI() {
        const checkedCount = document.querySelectorAll('.tryout-checkbox:checked').length;
        selectedCount.textContent = totalSelectedCount;

        // Update select all checkbox
        const totalCheckboxes = checkboxes.length;
        selectAll.checked = totalCheckboxes > 0 && checkedCount === totalCheckboxes;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < totalCheckboxes;
        selectAll.disabled = savingCount > 0;
    }

    // Handle individual checkbox changes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', async function() {
            await syncTryoutCheckbox(this);
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

    async function syncTryoutCheckbox(checkbox) {
        const tryoutId = checkbox.dataset.tryoutId;
        const previousState = checkbox.defaultChecked;
        const nextState = checkbox.checked;

        checkbox.disabled = true;
        savingCount++;
        updateUI();

        try {
            const response = await fetch('{{ route('admin.package.tryout.toggle', ['package_id' => $package->package_id, 'tryout_id' => '__TRYOUT_ID__']) }}'.replace('__TRYOUT_ID__', tryoutId), {
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
