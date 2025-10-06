@extends('admin.layout.admin')
@section('title', 'Manajemen Soal')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.tryout.index') }}" title="Manajemen Tryout" />
            <x-breadcrumb-item href="" title="Soal" />
        </x-slot>
    </x-breadcrumb>

    <x-btn title="Tambah Soal" route="{{ route('admin.question.create', $tryout_detail->tryout_detail_id) }}"
        icon="ri-add-fill">
    </x-btn>
</div>
<div class="package-bimbel bg-white p-8 rounded-lg border border-border flex justify-center text-center">
    <x-page-desc direction='item-center' title="Manajemen Soal - {{ $tryout->name }}">
        <x-slot name="description">
            Subtest: {{ $tryout_detail->type_subtest == 'social culture' ? 'Sosial Kultural & Manajerial' :
            strtoupper($tryout_detail->type_subtest) }} • Durasi: {{ $tryout_detail->duration }} menit
        </x-slot>
    </x-page-desc>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mt-4">
    <p>{{ session('success') }}</p>
</div>
@endif

@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mt-4">
    <p>{{ session('error') }}</p>
</div>
@endif

<div class="bg-white p-6 rounded-lg border border-border">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Daftar Soal</h2>
            <p class="text-gray-600 text-sm mt-1">{{ $tryout_detail->type_subtest ?? 'Unknown' }} - {{
                $questions->count() }} soal</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <!-- Import Excel Button -->
            <button type="button" id="importBtn"
                class="px-4 py-2 bg-green text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                <i class="ri-file-excel-2-line"></i>
                Import Excel
            </button>

            <!-- Download Template Button -->
            <a href="{{ asset('template/template_soal.xlsx') }}"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                <i class="ri-download-line"></i>
                Download Template
            </a>

            <a href="{{ route('admin.question.create', $tryout_detail->tryout_detail_id) }}"
                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2">
                <i class="ri-add-line"></i>
                Tambah Soal
            </a>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Import Soal dari Excel</h3>
                <button type="button" id="closeModal" class="text-gray-400 hover:text-gray-600">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <form action="{{ route('admin.question-import.import', $tryout_detail->tryout_detail_id) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-2">
                        File Excel
                    </label>
                    <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                        required>
                    <p class="text-xs text-gray-500 mt-1">Format: .xlsx atau .xls, maksimal 2MB</p>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <h4 class="font-medium text-blue-800 mb-2">Petunjuk:</h4>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• Download template Excel terlebih dahulu</li>
                        <li>• Isi data sesuai format yang sudah disediakan</li>
                        <li>• Hapus baris instruksi sebelum import</li>
                        <li>• Maksimal 100 soal per file</li>
                    </ul>
                </div>

                <div class="flex gap-3">
                    <button type="button" id="cancelBtn"
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-4 space-y-4">
        @forelse ($questions as $index => $question)
        <div class="bg-white border border-border rounded-xl p-6 flex flex-col">
            <div class="flex items-center gap-2 mb-2">
                <span
                    class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-[#0B2B9A]/10 text-[#0B2B9A] border border-[#0B2B9A]/10">
                    Soal #{{ $index + 1 }}
                </span>
                @php
                    $maxWeight = optional($question->questionOptions)->max(function($opt){
                        return is_null($opt->weight) ? 0 : (float)$opt->weight;
                    });
                    $displayWeight = ($maxWeight && $maxWeight > 0) ? $maxWeight : (float)($question->default_weight ?? 0);
                @endphp
                <span
                    class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                    {{ (float) $displayWeight }} poin
                </span>
                @if($question->sound)
                <span
                    class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 border border-purple-200">
                    <i class="ri-volume-up-line mr-1"></i>
                    Audio
                </span>
                @endif
            </div>

            <div class="font-bold text-lg text-gray-900 mb-2">
                {!! $question->question_text !!}
            </div>

            @if($question->sound)
            <div class="mb-3">
                <audio controls controlsList="nodownload" oncontextmenu="return false;" class="w-full max-w-md">
                    <source src="{{ Storage::url($question->sound) }}" type="audio/mpeg">
                    Browser Anda tidak mendukung audio.
                </audio>
            </div>
            @endif

            <div class="mb-2">
                <div class="font-semibold text-gray-700 mb-1">Opsi Jawaban:</div>
                <ul class="space-y-2 text-gray-600">
                    @foreach ($question->questionOptions as $index => $option)
                    @php
                    $optionLabel = chr(65 + $index); // A, B, C, D, E
                    @endphp
                    <li class="flex items-center gap-2 {{ $option->is_correct == 1 ? 'text-green font-medium' : '' }}">
                        <i
                            class="{{ $option->is_correct == 1 ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line' }}"></i>
                        <span class="flex items-center gap-1">{{ $optionLabel }}. {!! $option->option_text !!}</span>
                        @if($question->custom_score == 'yes')
                        <span class="text-xs text-gray-500">({{ $option->weight }} poin)</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>

            @if ($question->explanation)
            <div class="bg-blue-50 border border-primary border-dashed rounded-lg p-4 mt-2">
                <div class="font-semibold text-primary mb-1">Pembahasan</div>
                <div class="text-primary">{!! $question->explanation !!}</div>
            </div>
            @endif

            <div class="flex gap-3 mt-6">
                <x-btn title="Edit Soal"
                    route="{{ route('admin.question.edit', [$tryout_detail->tryout_detail_id, $question->question_id]) }}"
                    icon="ri-pencil-fill">
                </x-btn>
                <form
                    action="{{ route('admin.question.destroy', [$tryout_detail->tryout_detail_id, $question->question_id]) }}"
                    method="POST" onsubmit="return confirmDelete(event)" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 bg-red text-white rounded-lg hover:bg-red/90 transition-colors flex items-center gap-2">
                        <i class="ri-delete-bin-5-fill"></i>
                        Hapus Soal
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="bg-white border border-border rounded-xl p-12 text-center">
            <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="ri-question-line text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada soal</h3>
            <p class="text-gray-500 mb-4">Mulai dengan membuat soal pertama untuk subtest ini</p>
            <a href="{{ route('admin.question.create', $tryout_detail->tryout_detail_id) }}"
                class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 inline-flex items-center gap-2">
                <i class="ri-add-line"></i>
                Tambah Soal
            </a>
        </div>
        @endforelse
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const importBtn = document.getElementById('importBtn');
    const importModal = document.getElementById('importModal');
    const closeModal = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');

    // Show modal
    importBtn.addEventListener('click', function() {
        importModal.classList.remove('hidden');
    });

    // Hide modal
    function hideModal() {
        importModal.classList.add('hidden');
    }

    closeModal.addEventListener('click', hideModal);
    cancelBtn.addEventListener('click', hideModal);

    // Hide modal when clicking outside
    importModal.addEventListener('click', function(e) {
        if (e.target === importModal) {
            hideModal();
        }
    });
});

    // Delete confirmation function
    function confirmDelete(event) {
        event.preventDefault();

        if (confirm('Apakah Anda yakin ingin menghapus soal ini? Tindakan ini tidak dapat dibatalkan.')) {
            event.target.submit();
            return true;
        }
        return false;
    }
</script>
@endsection
