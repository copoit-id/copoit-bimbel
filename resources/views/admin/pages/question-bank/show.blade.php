@extends('admin.layout.admin')
@section('title', 'Detail Bank Soal')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('admin.question-bank.index', ['import_for' => $importTarget]) }}"
                class="text-primary hover:underline">Bank Soal</a>
            @foreach ($breadcrumbs as $item)
            <i class="ri-arrow-right-s-line text-gray-400"></i>
            <a href="{{ route('admin.question-bank.show', ['questionBank' => $item->id, 'import_for' => $importTarget]) }}"
                class="text-primary hover:underline">{{ $item->name }}</a>
            @endforeach
        </div>
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $bank->name }}</h1>
                <p class="text-gray-500">{{ $bank->description ?: 'Belum ada deskripsi.' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button id="openCreateSubBank"
                    class="inline-flex items-center gap-2 rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/5">
                    <i class="ri-folder-add-line"></i>
                    Tambah Sub Bank
                </button>
                <a href="{{ route('admin.question-bank.questions.create', ['questionBank' => $bank->id, 'import_for' => $importTarget]) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                    <i class="ri-add-line"></i>
                    Tambah Soal
                </a>
            </div>
        </div>
    </div>

    @if ($tryoutDetail)
    <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-800">
        Menambahkan soal untuk tryout <span class="font-semibold">{{ $tryoutDetail->tryout->name ?? '-' }}</span> -
        subtest <span class="font-semibold">{{ strtoupper($tryoutDetail->type_subtest ?? '-') }}</span>.
        Klik tombol <span class="font-semibold">Gunakan</span> pada soal yang ingin ditambahkan.
    </div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-primary/20 bg-primary/5 px-5 py-4">
            <p class="text-sm text-primary">Sub Bank</p>
            <p class="text-3xl font-semibold text-primary mt-1">{{ $bank->children->count() }}</p>
        </div>
        <div class="rounded-2xl border border-primary/20 bg-primary/5 px-5 py-4">
            <p class="text-sm text-primary">Soal Tersimpan</p>
            <p class="text-3xl font-semibold text-primary mt-1">{{ number_format($questions->total()) }}</p>
        </div>
        <div class="rounded-2xl border border-primary/20 bg-primary/5 px-5 py-4">
            <p class="text-sm text-primary">Terakhir Diperbarui</p>
            <p class="text-lg font-semibold text-primary mt-1">{{ optional($bank->updated_at)->diffForHumans() ?? '-' }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-border bg-white p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Sub Bank</h2>
        </div>
        @if ($bank->children->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-200 p-6 text-center text-gray-500">
            Belum ada sub bank pada level ini.
        </div>
        @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ($bank->children as $child)
            <div class="rounded-xl border border-gray-200 p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-400">Sub Bank</p>
                        <h3 class="text-base font-semibold text-gray-900">{{ $child->name }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ \Illuminate\Support\Str::limit($child->description, 60) }}
                        </p>
                    </div>
                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                        {{ $child->questions_count }} Soal
                    </span>
                </div>
                <a href="{{ route('admin.question-bank.show', ['questionBank' => $child->id, 'import_for' => $importTarget]) }}"
                    class="mt-4 inline-flex w-full items-center justify-center rounded-lg border border-primary text-primary px-4 py-2 text-sm font-semibold hover:bg-primary/5">
                    Lihat Sub Bank
                </a>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="rounded-2xl border border-border bg-white p-6">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Daftar Soal</h2>
                <p class="text-sm text-gray-500">Soal yang disimpan dalam bank ini.</p>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <div class="relative">
                    <input type="text" id="question-search" placeholder="Cari soal..."
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
                </div>
                <div id="question-count" class="text-sm text-gray-500">
                    Menampilkan: <span class="font-medium text-gray-700">{{ $questions->count() }}</span>
                </div>
            </div>
        </div>
        @if ($tryoutDetail)
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <button type="button" id="bulkCloneBtn"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:bg-gray-300">
                <i class="ri-download-line"></i>
                Gunakan Terpilih
            </button>
            <span id="bulkSelectionCount" class="text-sm text-gray-500">0 dipilih</span>
        </div>
        <form id="bulkCloneForm" action="{{ route('admin.question-bank.questions.bulk-clone') }}" method="POST" class="hidden">
            @csrf
            <input type="hidden" name="tryout_detail_id" value="{{ $tryoutDetail->tryout_detail_id }}">
        </form>
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        @if ($tryoutDetail)
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" id="selectAllQuestions"
                                class="rounded border-gray-300 text-primary focus:ring-primary">
                        </th>
                        @endif
                        <th class="px-4 py-3 text-left">Soal</th>
                        <th class="px-4 py-3 text-left">Tipe</th>
                        <th class="px-4 py-3 text-left">Bobot</th>
                        <th class="px-4 py-3 text-left">Terakhir Update</th>
                        <th class="px-4 py-3 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white" id="question-table-body">
                    @forelse ($questions as $question)
                    <tr class="bank-question-row"
                        data-question="{{ strtolower(strip_tags($question->question_text)) }}"
                        data-type="{{ strtolower($question->question_type) }}">
                        @if ($tryoutDetail)
                        <td class="px-4 py-3">
                            <input type="checkbox" class="bank-question-checkbox"
                                value="{{ $question->id }}">
                        </td>
                        @endif
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900">{!! \Illuminate\Support\Str::limit(strip_tags($question->question_text), 80) !!}</div>
                            <div class="text-xs text-gray-500">{{ $question->options->count() }} opsi jawaban</div>
                        </td>
                        <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $question->question_type) }}</td>
                        <td class="px-4 py-3">{{ (float) ($question->default_weight ?? 0) }} poin</td>
                        <td class="px-4 py-3">{{ optional($question->updated_at)->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.question-bank.questions.edit', ['question' => $question->id, 'import_for' => $importTarget]) }}"
                                    class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-100">
                                    <i class="ri-edit-line"></i> Edit
                                </a>
                                @if ($tryoutDetail)
                                <form action="{{ route('admin.question-bank.questions.clone', $question->id) }}"
                                    method="POST">
                                    @csrf
                                    <input type="hidden" name="tryout_detail_id" value="{{ $tryoutDetail->tryout_detail_id }}">
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary/90">
                                        <i class="ri-download-line"></i> Gunakan
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('admin.question-bank.questions.destroy', $question->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Hapus soal dari bank ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100">
                                        <i class="ri-delete-bin-line"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $tryoutDetail ? 6 : 5 }}" class="px-4 py-6 text-center text-gray-500">
                            Belum ada soal tersimpan pada bank ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div id="no-question-results" class="hidden text-center py-10 text-gray-500">
            Tidak ada soal ditemukan.
        </div>
        <div class="mt-4">
            {{ $questions->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Sub Bank Modal -->
<div id="createSubBankModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 transition">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-semibold text-gray-900">Tambah Sub Bank</h3>
            <button type="button" data-close-sub-bank class="text-gray-400 hover:text-gray-600">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        <form action="{{ route('admin.question-bank.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="parent_id" value="{{ $bank->id }}">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Sub Bank</label>
                <input type="text" name="name" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="3"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" data-close-sub-bank
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
        const subBankModal = document.getElementById('createSubBankModal');
        const openSubBtn = document.getElementById('openCreateSubBank');
        const closeSubButtons = document.querySelectorAll('[data-close-sub-bank]');

        const toggleModal = (modal, show) => {
            if (!modal) return;
            if (show) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            } else {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }
        };

        openSubBtn?.addEventListener('click', () => toggleModal(subBankModal, true));
        closeSubButtons.forEach(btn => btn.addEventListener('click', () => toggleModal(subBankModal, false)));
        subBankModal?.addEventListener('click', (event) => {
            if (event.target === subBankModal) toggleModal(subBankModal, false);
        });

        const bulkCloneBtn = document.getElementById('bulkCloneBtn');
        const bulkCloneForm = document.getElementById('bulkCloneForm');
        const selectAll = document.getElementById('selectAllQuestions');
        const checkboxes = document.querySelectorAll('.bank-question-checkbox');
        const bulkSelectionCount = document.getElementById('bulkSelectionCount');
        const questionSearch = document.getElementById('question-search');
        const questionRows = document.querySelectorAll('.bank-question-row');
        const questionCount = document.getElementById('question-count');
        const noQuestionResults = document.getElementById('no-question-results');

        const updateBulkState = () => {
            if (!bulkCloneBtn || !bulkSelectionCount) {
                return;
            }
            const checked = Array.from(checkboxes).filter(cb => cb.checked);
            bulkCloneBtn.disabled = checked.length === 0;
            bulkSelectionCount.textContent = `${checked.length} dipilih`;

            if (selectAll) {
                selectAll.indeterminate = checked.length > 0 && checked.length < checkboxes.length;
                selectAll.checked = checkboxes.length > 0 && checked.length === checkboxes.length;
            }
        };

        selectAll?.addEventListener('change', () => {
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            updateBulkState();
        });

        checkboxes.forEach(cb => cb.addEventListener('change', updateBulkState));

        const filterQuestions = () => {
            if (!questionSearch || !questionCount) {
                return;
            }
            const query = questionSearch.value.toLowerCase().trim();
            let visible = 0;
            questionRows.forEach(row => {
                const text = row.dataset.question || '';
                const type = row.dataset.type || '';
                const match = !query || text.includes(query) || type.includes(query);
                row.classList.toggle('hidden', !match);
                if (match) visible++;
            });

            questionCount.innerHTML = `Menampilkan: <span class="font-medium text-gray-700">${visible}</span>`;
            if (noQuestionResults) {
                noQuestionResults.classList.toggle('hidden', visible > 0);
            }
        };

        questionSearch?.addEventListener('input', filterQuestions);

        bulkCloneBtn?.addEventListener('click', () => {
            if (!bulkCloneForm) {
                return;
            }

            bulkCloneForm.querySelectorAll('input[name="question_ids[]"]').forEach(input => input.remove());
            Array.from(checkboxes)
                .filter(cb => cb.checked)
                .forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'question_ids[]';
                    input.value = cb.value;
                    bulkCloneForm.appendChild(input);
                });

            if (bulkCloneForm.querySelectorAll('input[name="question_ids[]"]').length > 0) {
                bulkCloneForm.submit();
            }
        });

        updateBulkState();
        filterQuestions();
    });
</script>
@endpush
