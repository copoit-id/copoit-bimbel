@extends('admin.layout.admin')
@section('title', 'Detail Bank Soal')
@section('content')
@php
    $pptImportPreview = session('ppt_import_preview');
@endphp
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
                <button type="button" id="openImportQuestions"
                    class="inline-flex items-center gap-2 rounded-lg border border-green-600 px-4 py-2 text-sm font-semibold text-green-700 hover:bg-green-50">
                    <i class="ri-file-excel-2-line"></i>
                    Import Excel
                </button>
                <button type="button" id="openImportPpt"
                    class="inline-flex items-center gap-2 rounded-lg border border-orange-500 px-4 py-2 text-sm font-semibold text-orange-600 hover:bg-orange-50">
                    <i class="ri-slideshow-3-line"></i>
                    Import PPT
                </button>
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
            <div class="rounded-xl border border-gray-200 p-4 shadow-sm flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs uppercase tracking-wide text-gray-400">Sub Bank</p>
                        <h3 class="text-base font-semibold text-gray-900 line-clamp-2">{{ $child->name }}</h3>
                        <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ Str::limit($child->description, 60) }}</p>
                        <p class="text-xs text-gray-400 mt-2">Dibuat: {{ optional($child->created_at)->format('d M Y') }}</p>
                    </div>
                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary flex-shrink-0">
                        {{ $child->questions_count }} Soal
                    </span>
                </div>
                <div class="flex items-center gap-2 mt-auto pt-3 border-t border-gray-100">
                    <button type="button" onclick="editBank({{ $child->id }}, '{{ addslashes($child->name) }}', '{{ addslashes($child->description ?? '') }}')"
                        class="flex-1 inline-flex items-center justify-center rounded-lg border border-gray-300 text-gray-600 px-3 py-2 text-xs font-medium hover:bg-gray-50">
                        <i class="ri-edit-line mr-1"></i>Edit
                    </button>
                    <button type="button" onclick="deleteBank({{ $child->id }}, '{{ addslashes($child->name) }}', {{ $child->questions_count }})"
                        class="flex-1 inline-flex items-center justify-center rounded-lg border border-red-200 text-red-600 px-3 py-2 text-xs font-medium hover:bg-red-50">
                        <i class="ri-delete-bin-line mr-1"></i>Hapus
                    </button>
                </div>
                <a href="{{ route('admin.question-bank.show', ['questionBank' => $child->id, 'import_for' => $importTarget]) }}"
                    class="mt-2 inline-flex w-full items-center justify-center rounded-lg border border-primary text-primary px-4 py-2 text-sm font-semibold hover:bg-primary/5">
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
                <form method="GET" action="{{ route('admin.question-bank.show', $bank->id) }}" class="flex flex-col sm:flex-row sm:items-center gap-2">
                    @if($importTarget)
                    <input type="hidden" name="import_for" value="{{ $importTarget }}">
                    @endif
                    <select name="sort" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                        <option value="newest" @selected(($questionSort ?? 'newest') === 'newest')>Terbaru</option>
                        <option value="oldest" @selected(($questionSort ?? 'newest') === 'oldest')>Terlama</option>
                    </select>
                    <select name="question_type" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                        <option value="all" @selected(($questionType ?? 'all') === 'all')>Semua Tipe</option>
                        @foreach($questionTypeOptions as $typeOption)
                        <option value="{{ $typeOption }}" @selected(($questionType ?? 'all') === $typeOption)>
                            {{ ucwords(str_replace('_', ' ', $typeOption)) }}
                        </option>
                        @endforeach
                    </select>
                </form>
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
                        <th class="px-4 py-3 text-left">Dibuat</th>
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
                        <td class="px-4 py-3">
                            @php
                                $defaultWeight = (float) ($question->default_weight ?? 0);
                                $metadata = is_array($question->metadata) ? $question->metadata : [];
                                $multipleAnswerMeta = is_array($metadata['multiple_answer'] ?? null) ? $metadata['multiple_answer'] : [];
                                $mtfMeta = is_array($metadata['multiple_true_false'] ?? null) ? $metadata['multiple_true_false'] : [];
                                $multipleAnswerScoringMode = in_array(($multipleAnswerMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
                                    ? $multipleAnswerMeta['scoring_mode']
                                    : 'fullscore';
                                $multipleAnswerTotalScore = (float) ($multipleAnswerMeta['score_correct'] ?? $defaultWeight);
                                $multipleAnswerCorrectCount = max(1, $question->options->where('is_correct', true)->count());
                                $multipleAnswerPerCorrectScore = $multipleAnswerCorrectCount > 0
                                    ? ($multipleAnswerTotalScore / $multipleAnswerCorrectCount)
                                    : $multipleAnswerTotalScore;
                                $mtfScoreCorrect = (float) ($mtfMeta['score_correct'] ?? $defaultWeight);
                            @endphp
                            @if(($question->question_type ?? '') === 'multiple_answer')
                                @if($multipleAnswerScoringMode === 'partial')
                                    Per benar:
                                    {{ rtrim(rtrim(number_format($multipleAnswerPerCorrectScore, 2, '.', ''), '0'), '.') }}
                                @else
                                    Benar semua :
                                    {{ rtrim(rtrim(number_format($multipleAnswerTotalScore, 2, '.', ''), '0'), '.') }}
                                @endif
                            @elseif(($question->question_type ?? '') === 'multiple_true_false')
                                {{ rtrim(rtrim(number_format($mtfScoreCorrect, 2, '.', ''), '0'), '.') }} poin
                            @else
                                {{ $defaultWeight }} poin
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ optional($question->created_at)->format('d M Y H:i') }}</td>
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
                                    method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                        onclick="openConfirmModal('confirmDelete', '{{ route('admin.question-bank.questions.destroy', $question->id) }}', 'DELETE', 'Hapus soal dari bank ini?')"
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

<!-- Import PPT Upload Modal -->
<div id="importPptModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 transition">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-900">Import Soal dari PPT</h3>
            <button type="button" data-close-import-ppt class="text-gray-400 hover:text-gray-600">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        <form action="{{ route('admin.question-bank.questions.import-ppt.preview', $bank->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @if($importTarget)
            <input type="hidden" name="import_for" value="{{ $importTarget }}">
            @endif
            <div class="rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                PPT akan diparse dulu dan ditampilkan sebagai preview. Data belum masuk database sebelum tombol <span class="font-semibold">Simpan ke Bank Soal</span> ditekan.
            </div>
            <div>
                <label for="ppt_file" class="mb-2 block text-sm font-medium text-gray-700">File PPTX</label>
                <input type="file" id="ppt_file" name="ppt_file" accept=".pptx" required
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                <p class="mt-1 text-xs text-gray-500">Format: .pptx, maksimal 10MB. Gunakan teks asli, bukan slide hasil scan/gambar.</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <h4 class="mb-2 text-sm font-semibold text-gray-800">Format yang didukung</h4>
                <ul class="space-y-1 text-sm text-gray-600">
                    <li>SOAL NOMOR 1</li>
                    <li>A. Opsi A sampai E. Opsi E</li>
                    <li>Jawaban: A</li>
                    <li>Pembahasan atau penjelasan ditaruh setelah baris jawaban.</li>
                </ul>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" data-close-import-ppt
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit"
                    class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600">
                    Preview
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Import PPT Preview Modal -->
<div id="pptPreviewModal"
    class="fixed inset-0 z-50 hidden items-start justify-center overflow-hidden bg-black/40 px-3 py-4 transition sm:px-4">
    <div class="flex h-[calc(100vh-2rem)] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="shrink-0 flex items-start justify-between gap-4 border-b border-gray-100 px-4 py-4 sm:px-6">
            <div>
                <h3 class="text-xl font-semibold text-gray-900">Preview Import PPT</h3>
                <p class="text-sm text-gray-500">
                    Cek hasil parsing dan atur skor opsi sebelum menyimpan ke bank soal.
                </p>
            </div>
            <button type="button" data-close-ppt-preview class="text-gray-400 hover:text-gray-600">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        <form id="pptPreviewForm" action="{{ route('admin.question-bank.questions.import-ppt.store', $bank->id) }}" method="POST" class="flex min-h-0 flex-1 flex-col overflow-hidden">
            @csrf
            @if($importTarget)
            <input type="hidden" name="import_for" value="{{ $importTarget }}">
            @endif
            <input type="hidden" name="questions_json" id="pptQuestionsJson">

            <div class="shrink-0 border-b border-gray-100 px-4 py-4 sm:px-6">
                <div id="pptPreviewSummary" class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800"></div>
                <div id="pptPreviewWarnings" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"></div>
                <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <div class="grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
                        <div>
                            <label for="pptGlobalCorrectScore" class="mb-1 block text-xs font-semibold text-gray-600">Skor jawaban benar</label>
                            <input type="number" id="pptGlobalCorrectScore" step="0.1" min="0" max="999" value="1"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                        </div>
                        <div>
                            <label for="pptGlobalWrongScore" class="mb-1 block text-xs font-semibold text-gray-600">Skor jawaban salah</label>
                            <input type="number" id="pptGlobalWrongScore" step="0.1" min="0" max="999" value="0"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                        </div>
                        <button type="button" id="applyPptGlobalScores"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-900">
                            <i class="ri-magic-line"></i>
                            Apply ke Semua
                        </button>
                    </div>
                </div>
            </div>

            <div id="pptPreviewList" class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain px-4 py-4 sm:px-6"></div>

            <div class="shrink-0 flex flex-col gap-2 border-t border-gray-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="text-xs text-gray-500">
                    Perubahan skor di modal ini belum masuk DB sampai disimpan. Opsi benar otomatis mengikuti dropdown jawaban benar.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" data-close-ppt-preview
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                        Simpan ke Bank Soal
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Import Questions Modal -->
<div id="importQuestionsModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 transition">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-900">Import Soal dari Excel</h3>
            <button type="button" data-close-import-questions class="text-gray-400 hover:text-gray-600">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        <form action="{{ route('admin.question-bank.questions.import', $bank->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @if($importTarget)
            <input type="hidden" name="import_for" value="{{ $importTarget }}">
            @endif
            <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                Import akan ditambahkan ke <span class="font-semibold">{{ $bank->name }}</span>.
            </div>
            <div>
                <label for="excel_file" class="mb-2 block text-sm font-medium text-gray-700">File Excel</label>
                <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls" required
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                <p class="mt-1 text-xs text-gray-500">Format: .xlsx atau .xls, maksimal 2MB.</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <h4 class="mb-2 text-sm font-semibold text-gray-800">Petunjuk</h4>
                <ul class="space-y-1 text-sm text-gray-600">
                    <li>Download template Excel terlebih dahulu.</li>
                    <li>Format kolom sama persis dengan manajemen soal.</li>
                    <li>Hapus baris instruksi sebelum import.</li>
                    <li>Maksimal 100 soal per file.</li>
                </ul>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('admin.question-bank.questions.import-template', $bank->id) }}"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="ri-download-line"></i>
                    Template
                </a>
                <button type="button" data-close-import-questions
                    class="inline-flex flex-1 items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit"
                    class="inline-flex flex-1 items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                    Import
                </button>
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

<x-confirm-modal id="confirmDelete" title="Hapus Bank Soal" message="Apakah Anda yakin?" confirmText="Ya, hapus" confirmVariant="danger" />

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const subBankModal = document.getElementById('createSubBankModal');
        const openSubBtn = document.getElementById('openCreateSubBank');
        const closeSubButtons = document.querySelectorAll('[data-close-sub-bank]');
        const importQuestionsModal = document.getElementById('importQuestionsModal');
        const openImportQuestionsBtn = document.getElementById('openImportQuestions');
        const closeImportQuestionsButtons = document.querySelectorAll('[data-close-import-questions]');
        const importPptModal = document.getElementById('importPptModal');
        const openImportPptBtn = document.getElementById('openImportPpt');
        const closeImportPptButtons = document.querySelectorAll('[data-close-import-ppt]');
        const pptPreviewModal = document.getElementById('pptPreviewModal');
        const closePptPreviewButtons = document.querySelectorAll('[data-close-ppt-preview]');
        const pptPreviewForm = document.getElementById('pptPreviewForm');
        const pptPreviewList = document.getElementById('pptPreviewList');
        const pptPreviewSummary = document.getElementById('pptPreviewSummary');
        const pptPreviewWarnings = document.getElementById('pptPreviewWarnings');
        const pptQuestionsJson = document.getElementById('pptQuestionsJson');
        const pptGlobalCorrectScore = document.getElementById('pptGlobalCorrectScore');
        const pptGlobalWrongScore = document.getElementById('pptGlobalWrongScore');
        const applyPptGlobalScores = document.getElementById('applyPptGlobalScores');
        const pptImportPreview = @json($pptImportPreview);

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

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const renderPptPreview = (preview) => {
            if (!preview || !pptPreviewList || !pptPreviewSummary) {
                return;
            }

            const questions = Array.isArray(preview.questions) ? preview.questions : [];
            const errors = Array.isArray(preview.errors) ? preview.errors : [];
            pptPreviewSummary.textContent = `${questions.length} soal terbaca dari ${preview.file_name || 'PPT'} (${preview.total_slides || 0} slide).`;

            if (errors.length > 0 && pptPreviewWarnings) {
                pptPreviewWarnings.classList.remove('hidden');
                pptPreviewWarnings.innerHTML = `<p class="font-semibold">Catatan parser:</p><ul class="mt-1 list-disc pl-5">${errors.slice(0, 6).map(error => `<li>${escapeHtml(error)}</li>`).join('')}</ul>`;
            }

            pptPreviewList.innerHTML = questions.map((question, index) => {
                const options = question.options || {};
                const correctAnswer = question.correct_answer || 'A';
                const optionRows = ['A', 'B', 'C', 'D', 'E'].map(letter => {
                    const text = typeof options[letter] === 'object' ? (options[letter]?.text || '') : (options[letter] || '');
                    if (!text) return '';
                    const defaultWeight = letter === correctAnswer ? 1 : 0;

                    return `
                        <div class="grid gap-2 rounded-lg border border-gray-100 bg-gray-50 p-3 md:grid-cols-[40px_minmax(0,1fr)_110px] md:items-start">
                            <div class="font-semibold text-gray-700">${letter}</div>
                            <textarea data-option-text="${letter}" rows="2"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">${escapeHtml(text)}</textarea>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-500">Skor</label>
                                <input type="number" data-option-weight="${letter}" step="0.1" min="0" max="999"
                                    value="${defaultWeight}"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                            </div>
                        </div>
                    `;
                }).join('');
                const optionChoices = ['A', 'B', 'C', 'D', 'E']
                    .filter(letter => options[letter])
                    .map(letter => `<option value="${letter}" ${letter === correctAnswer ? 'selected' : ''}>${letter}</option>`)
                    .join('');
                const questionErrors = Array.isArray(question.errors) && question.errors.length > 0
                    ? `<div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">${question.errors.map(error => escapeHtml(error)).join('<br>')}</div>`
                    : '';

                return `
                    <div class="ppt-question-item rounded-xl border border-gray-200 bg-white p-4 shadow-sm" data-index="${index}" data-slide="${escapeHtml(question.slide || '')}" data-number="${escapeHtml(question.number || '')}">
                        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400">Slide ${escapeHtml(question.slide || '-')}</p>
                                <h4 class="font-semibold text-gray-900">Soal ${escapeHtml(question.number || index + 1)}</h4>
                            </div>
                            <label class="text-sm font-medium text-gray-700">
                                Jawaban benar
                                <select data-correct-answer
                                    class="ml-2 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                    ${optionChoices}
                                </select>
                            </label>
                        </div>
                        ${questionErrors}
                        <div class="mt-3">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Teks Soal</label>
                            <textarea data-question-text rows="4"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">${escapeHtml(question.question_text || '')}</textarea>
                        </div>
                        <div class="mt-3 space-y-2">${optionRows}</div>
                        <div class="mt-3">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Pembahasan</label>
                            <textarea data-explanation rows="3"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">${escapeHtml(question.explanation || '')}</textarea>
                        </div>
                    </div>
                `;
            }).join('');
        };

        const getGlobalScoreValue = (input, fallback) => {
            const value = Number.parseFloat(input?.value ?? '');
            if (Number.isNaN(value)) {
                return fallback;
            }

            return Math.max(0, Math.min(999, value));
        };

        const applyScoresToQuestion = (item) => {
            const correctAnswer = item.querySelector('[data-correct-answer]')?.value || '';
            const correctScore = getGlobalScoreValue(pptGlobalCorrectScore, 1);
            const wrongScore = getGlobalScoreValue(pptGlobalWrongScore, 0);

            ['A', 'B', 'C', 'D', 'E'].forEach(letter => {
                const weightInput = item.querySelector(`[data-option-weight="${letter}"]`);
                if (!weightInput) return;
                weightInput.value = letter === correctAnswer ? correctScore : wrongScore;
            });
        };

        const applyScoresToAllQuestions = () => {
            document.querySelectorAll('.ppt-question-item').forEach(applyScoresToQuestion);
        };

        const collectPptPreview = () => {
            return Array.from(document.querySelectorAll('.ppt-question-item')).map(item => {
                const options = {};
                ['A', 'B', 'C', 'D', 'E'].forEach(letter => {
                    const textInput = item.querySelector(`[data-option-text="${letter}"]`);
                    if (!textInput) return;
                    const weightInput = item.querySelector(`[data-option-weight="${letter}"]`);
                    options[letter] = {
                        text: textInput.value.trim(),
                        weight: Number.parseFloat(weightInput?.value || '0') || 0,
                    };
                });

                return {
                    slide: item.dataset.slide || null,
                    number: item.dataset.number || null,
                    question_text: item.querySelector('[data-question-text]')?.value.trim() || '',
                    explanation: item.querySelector('[data-explanation]')?.value.trim() || '',
                    correct_answer: item.querySelector('[data-correct-answer]')?.value || '',
                    options,
                };
            });
        };

        openSubBtn?.addEventListener('click', () => toggleModal(subBankModal, true));
        closeSubButtons.forEach(btn => btn.addEventListener('click', () => toggleModal(subBankModal, false)));
        subBankModal?.addEventListener('click', (event) => {
            if (event.target === subBankModal) toggleModal(subBankModal, false);
        });

        openImportQuestionsBtn?.addEventListener('click', () => toggleModal(importQuestionsModal, true));
        closeImportQuestionsButtons.forEach(btn => btn.addEventListener('click', () => toggleModal(importQuestionsModal, false)));
        importQuestionsModal?.addEventListener('click', (event) => {
            if (event.target === importQuestionsModal) toggleModal(importQuestionsModal, false);
        });

        openImportPptBtn?.addEventListener('click', () => toggleModal(importPptModal, true));
        closeImportPptButtons.forEach(btn => btn.addEventListener('click', () => toggleModal(importPptModal, false)));
        importPptModal?.addEventListener('click', (event) => {
            if (event.target === importPptModal) toggleModal(importPptModal, false);
        });

        closePptPreviewButtons.forEach(btn => btn.addEventListener('click', () => toggleModal(pptPreviewModal, false)));
        pptPreviewModal?.addEventListener('click', (event) => {
            if (event.target === pptPreviewModal) toggleModal(pptPreviewModal, false);
        });
        pptPreviewForm?.addEventListener('submit', () => {
            if (pptQuestionsJson) {
                pptQuestionsJson.value = JSON.stringify(collectPptPreview());
            }
        });
        applyPptGlobalScores?.addEventListener('click', applyScoresToAllQuestions);
        pptPreviewList?.addEventListener('change', (event) => {
            if (event.target?.matches('[data-correct-answer]')) {
                const item = event.target.closest('.ppt-question-item');
                if (item) {
                    applyScoresToQuestion(item);
                }
            }
        });

        if (pptImportPreview) {
            renderPptPreview(pptImportPreview);
            applyScoresToAllQuestions();
            toggleModal(pptPreviewModal, true);
        }

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

        // Edit Bank modal
        const editModal = document.getElementById('editBankModal');
        const closeEditBtn = document.getElementById('closeEditBank');
        const cancelEditBtn = document.getElementById('cancelEditBank');
        closeEditBtn?.addEventListener('click', () => toggleModal(editModal, false));
        cancelEditBtn?.addEventListener('click', () => toggleModal(editModal, false));
        editModal?.addEventListener('click', (e) => {
            if (e.target === editModal) toggleModal(editModal, false);
        });
    });

    function editBank(id, name, description) {
        document.getElementById('editBankName').value = name;
        document.getElementById('editBankDescription').value = description || '';
        document.getElementById('editBankForm').action = '/admin/bank-soal/' + id;
        const modal = document.getElementById('editBankModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
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
