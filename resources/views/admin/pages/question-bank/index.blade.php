@extends('admin.layout.admin')
@section('title', 'Bank Soal')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Bank Soal</h1>
            <p class="text-gray-500">Atur koleksi soal dan sub bank untuk mempermudah penyusunan tryout.</p>
        </div>
        {{-- Button dengan Cek Plan Quota (batasan jumlah soal) --}}
        <x-plan-quota-button
            feature="question_bank"
            href="#"
            icon="ri-add-circle-line"
            label="Tambah Bank"
            variant="primary"
            size="md"
            tooltipPosition="bottom"
            id="openCreateBank" />
    </div>

    @if (session('success'))
    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
        {{ session('success') }}
    </div>
    @endif

    @if (session('error'))
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
        {{ session('error') }}
    </div>
    @endif

    {{-- Alert Quota Status --}}
    @php
        $bankQuota = $planQuota['question_bank'] ?? \App\Services\PlanQuotaService::canCreateQuestionBank();
    @endphp
    
    @if(!$bankQuota['allowed'])
        <div class="rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700">
            <div class="flex items-center gap-2">
                <i class="ri-information-line text-lg"></i>
                <div>
                    <p class="font-medium">Kuota Soal Terpenuhi</p>
                    <p>{{ $bankQuota['reason'] }} Silakan hubungi administrator untuk upgrade plan.</p>
                </div>
            </div>
        </div>
    @elseif($bankQuota['limit'] > 0 && $bankQuota['current'] >= $bankQuota['limit'] - 10)
        {{-- Warning jika hampir penuh (10 soal tersisa) --}}
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-700">
            <div class="flex items-center gap-2">
                <i class="ri-alert-line text-lg"></i>
                <div>
                    <p class="font-medium">Kuota Soal Hampir Penuh</p>
                    <p>Anda telah menggunakan {{ $bankQuota['current'] }} dari {{ $bankQuota['limit'] }} soal. Segera upgrade plan untuk menambah lebih banyak soal.</p>
                </div>
            </div>
        </div>
    @endif

    @if ($tryoutDetail)
    <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-800">
        Kamu sedang memilih soal untuk tryout <span class="font-semibold">{{ $tryoutDetail->tryout->name ?? '-' }}</span>
        (Subtest: <span class="font-semibold">{{ strtoupper($tryoutDetail->type_subtest ?? '-') }}</span>). Pilih bank dan
        gunakan soal yang sesuai.
    </div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-primary/20 bg-primary/5 px-5 py-4">
            <p class="text-sm text-primary">Total Bank</p>
            <p class="text-3xl font-semibold text-primary mt-1">{{ number_format($stats['total_banks']) }}</p>
        </div>
        <div class="rounded-2xl border border-primary/20 bg-primary/5 px-5 py-4">
            <p class="text-sm text-primary">Sub Bank</p>
            <p class="text-3xl font-semibold text-primary mt-1">{{ number_format($stats['child_banks']) }}</p>
        </div>
        <div class="rounded-2xl border border-primary/20 bg-primary/5 px-5 py-4">
            <p class="text-sm text-primary">Total Soal Tersimpan</p>
            <p class="text-3xl font-semibold text-primary mt-1">{{ number_format($stats['total_questions']) }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-border bg-white p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Daftar Bank Soal</h2>
                <p class="text-sm text-gray-500">Pilih bank untuk melihat sub bank dan soal di dalamnya.</p>
            </div>
            <form method="GET" action="{{ route('admin.question-bank.index') }}" class="flex items-center gap-2">
                @if($importTarget)
                <input type="hidden" name="import_for" value="{{ $importTarget }}">
                @endif
                <label for="bank-sort" class="text-sm text-gray-500">Urutkan</label>
                <select id="bank-sort" name="sort" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <option value="newest" @selected(($bankSort ?? 'newest') === 'newest')>Terbaru</option>
                    <option value="oldest" @selected(($bankSort ?? 'newest') === 'oldest')>Terlama</option>
                </select>
            </form>
        </div>

        @if ($rootBanks->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-200 p-8 text-center text-gray-500">
            Belum ada bank soal. Klik tombol <span class="font-semibold">Tambah Bank</span> untuk mulai membuat.
        </div>
        @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($rootBanks as $bank)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs uppercase tracking-wide text-gray-400">Bank Soal</p>
                        <h3 class="text-lg font-semibold text-gray-900 line-clamp-2">{{ $bank->name }}</h3>
                        <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ Str::limit($bank->description, 80) }}</p>
                    </div>
                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary flex-shrink-0">
                        {{ $bank->questions_count }} Soal
                    </span>
                </div>
                <div class="flex flex-wrap gap-2 text-xs text-gray-500 mb-auto">
                    <span class="rounded-full bg-gray-100 px-3 py-1">Sub bank: {{ $bank->children->count() }}</span>
                    <span class="rounded-full bg-gray-100 px-3 py-1">Dibuat: {{ optional($bank->created_at)->format('d M Y') }}</span>
                </div>
                <div class="flex items-center gap-2 mt-4 pt-3 border-t border-gray-100">
                    <button type="button" onclick="editBank({{ $bank->id }}, '{{ addslashes($bank->name) }}', '{{ addslashes($bank->description ?? '') }}')"
                        class="flex-1 inline-flex items-center justify-center rounded-lg border border-gray-300 text-gray-600 px-3 py-2 text-xs font-medium hover:bg-gray-50">
                        <i class="ri-edit-line mr-1"></i>Edit
                    </button>
                    <button type="button" onclick="deleteBank({{ $bank->id }}, '{{ addslashes($bank->name) }}', {{ $bank->questions_count + $bank->children->sum('questions_count') }})"
                        class="flex-1 inline-flex items-center justify-center rounded-lg border border-red-200 text-red-600 px-3 py-2 text-xs font-medium hover:bg-red-50">
                        <i class="ri-delete-bin-line mr-1"></i>Hapus
                    </button>
                </div>
                <a href="{{ route('admin.question-bank.show', ['questionBank' => $bank->id, 'import_for' => $importTarget]) }}"
                    class="mt-2 inline-flex w-full items-center justify-center rounded-lg border border-primary text-primary px-4 py-2 text-sm font-semibold hover:bg-primary/5">
                    {{ $tryoutDetail ? 'Pilih Bank' : 'Kelola Bank' }}
                </a>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

<x-confirm-modal id="confirmDelete" title="Hapus Bank Soal" message="Apakah Anda yakin?" confirmText="Ya, hapus" confirmVariant="danger" />
<div id="createBankModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 transition">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-semibold text-gray-900">Tambah Bank Soal</h3>
            <button type="button" id="closeCreateBank" class="text-gray-400 hover:text-gray-600">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        <form action="{{ route('admin.question-bank.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bank</label>
                <input type="text" name="name" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    placeholder="Contoh: Bank Soal TPS TKA">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sub Bank Dari</label>
                <select name="parent_id"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <option value="">(Tidak ada - Bank utama)</option>
                    @foreach ($bankOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="3"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20"
                    placeholder="Tuliskan ringkasan isi bank soal"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="cancelCreateBank"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Batal</button>
                <button type="submit"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Bank Modal -->
<div id="editBankModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 transition">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-semibold text-gray-900">Edit Bank Soal</h3>
            <button type="button" id="closeEditBank" class="text-gray-400 hover:text-gray-600">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        <form id="editBankForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="_method" value="PUT">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bank</label>
                <input type="text" name="name" id="editBankName" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" id="editBankDescription" rows="3"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="cancelEditBank"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Batal</button>
                <button type="submit"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('createBankModal');
        const editModal = document.getElementById('editBankModal');
        const openBtn = document.getElementById('openCreateBank');
        const closeBtn = document.getElementById('closeCreateBank');
        const cancelBtn = document.getElementById('cancelCreateBank');
        const closeEditBtn = document.getElementById('closeEditBank');
        const cancelEditBtn = document.getElementById('cancelEditBank');

        function toggleModal(modalEl, show) {
            if (!modalEl) return;
            if (show) {
                modalEl.classList.remove('hidden');
                modalEl.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            } else {
                modalEl.classList.add('hidden');
                modalEl.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }
        }

        openBtn?.addEventListener('click', () => toggleModal(modal, true));
        closeBtn?.addEventListener('click', () => toggleModal(modal, false));
        cancelBtn?.addEventListener('click', () => toggleModal(modal, false));
        modal?.addEventListener('click', (event) => {
            if (event.target === modal) toggleModal(modal, false);
        });

        closeEditBtn?.addEventListener('click', () => toggleModal(editModal, false));
        cancelEditBtn?.addEventListener('click', () => toggleModal(editModal, false));
        editModal?.addEventListener('click', (event) => {
            if (event.target === editModal) toggleModal(editModal, false);
        });
    });

    function editBank(id, name, description) {
        document.getElementById('editBankName').value = name;
        document.getElementById('editBankDescription').value = description || '';
        document.getElementById('editBankForm').action = '/admin/bank-soal/' + id;
        document.getElementById('editBankModal').classList.remove('hidden');
        document.getElementById('editBankModal').classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function deleteBank(id, name, totalQuestions) {
        let message = `Yakin ingin menghapus bank soal "${name}"?`;
        if (totalQuestions > 0) {
            message += `\n\nPERHATIAN: Bank ini berisi ${totalQuestions} soal. Semua soal akan ikut dihapus!`;
        }
        openConfirmModal('confirmDelete', '/admin/bank-soal/' + id, 'DELETE', message);
    }
</script>
@endpush
