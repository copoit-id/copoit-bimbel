@extends('admin.layout.admin')

@section('title', 'Tambah Kolom Kecermatan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tambah Kolom Kecermatan</h2>
            <p class="text-gray-500">Buat kolom dan soal kecermatan untuk tryout: <strong>{{ $tryout->name }}</strong></p>
        </div>
        @if($package->package_id === 'standalone')
        <a href="{{ route('admin.tryout.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
        @else
        <a href="{{ route('admin.package.tryout.soal', [$package->package_id, $tryout->tryoutDetails->first()->tryout_detail_id ?? $tryout->tryout_id]) }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
        @endif
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.kecermatan.store', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id]) }}" 
        method="POST" 
        id="kecermatanForm"
        onsubmit="prepareFormSubmission()">
        @csrf

        <!-- Form Input Kolom -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-primary text-white px-6 py-3">
                <h3 class="text-lg font-semibold text-center">Form Input Kolom</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nama Kolom -->
                    <div>
                        <label for="nama_kolom" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Kolom <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="nama_kolom" name="nama_kolom" required
                            value="{{ old('nama_kolom') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Contoh: Kolom Huruf A">
                    </div>

                    <!-- Jumlah Soal -->
                    <div>
                        <label for="jumlah_soal" class="block text-sm font-medium text-gray-700 mb-2">
                            Jumlah Soal per Baris <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="jumlah_soal" name="jumlah_soal" min="1" max="100" required
                            value="{{ old('jumlah_soal', 20) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Contoh: 20">
                        <p class="text-xs text-gray-500 mt-1">Jumlah soal yang digenerate untuk setiap baris</p>
                    </div>

                    <!-- Durasi Kolom -->
                    <div>
                        <label for="durasi_kolom" class="block text-sm font-medium text-gray-700 mb-2">
                            Durasi Kolom (Menit) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="durasi_kolom" name="durasi_kolom" min="1" required
                            value="{{ old('durasi_kolom', 10) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Contoh: 10">
                    </div>

                    <!-- Pilihan Tipe Kolom -->
                    <div>
                        <label for="tipe_kolom" class="block text-sm font-medium text-gray-700 mb-2">
                            Pilihan Tipe Kolom <span class="text-red-500">*</span>
                        </label>
                        <select id="tipe_kolom" name="tipe_kolom" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                            <option value="">-- Pilih Tipe Kolom --</option>
                            <option value="huruf" {{ old('tipe_kolom') == 'huruf' ? 'selected' : '' }}>Huruf (A-Z)</option>
                            <option value="angka" {{ old('tipe_kolom') == 'angka' ? 'selected' : '' }}>Angka (1-100)</option>
                            <option value="simbol" {{ old('tipe_kolom') == 'simbol' ? 'selected' : '' }}>Simbol (★, ●, ▲, dll)</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Tipe kolom hanya sebagai keterangan/kategori</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Isi Kolom (A, B, C, D, E) - USER ISI MANUAL -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-primary text-white px-6 py-3">
                <h3 class="text-lg font-semibold text-center">Isi Kolom (A, B, C, D, E)</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-4">Isi 5 nilai untuk kolom. Dari 5 nilai ini akan digenerate soal-soal.</p>
                
                <div class="grid grid-cols-5 gap-4">
                    <!-- Kolom A -->
                    <div class="text-center">
                        <label class="block text-lg font-bold text-gray-800 mb-2">A</label>
                        <input type="text" name="kolom_data[]" required maxlength="10"
                            value="{{ old('kolom_data.0') }}"
                            class="w-full px-3 py-3 border-2 border-primary rounded-lg text-center text-xl font-bold focus:outline-none focus:ring-2 focus:ring-primary/20"
                            placeholder="?" oninput="updatePreview()">
                    </div>
                    <!-- Kolom B -->
                    <div class="text-center">
                        <label class="block text-lg font-bold text-gray-800 mb-2">B</label>
                        <input type="text" name="kolom_data[]" required maxlength="10"
                            value="{{ old('kolom_data.1') }}"
                            class="w-full px-3 py-3 border-2 border-primary rounded-lg text-center text-xl font-bold focus:outline-none focus:ring-2 focus:ring-primary/20"
                            placeholder="?" oninput="updatePreview()">
                    </div>
                    <!-- Kolom C -->
                    <div class="text-center">
                        <label class="block text-lg font-bold text-gray-800 mb-2">C</label>
                        <input type="text" name="kolom_data[]" required maxlength="10"
                            value="{{ old('kolom_data.2') }}"
                            class="w-full px-3 py-3 border-2 border-primary rounded-lg text-center text-xl font-bold focus:outline-none focus:ring-2 focus:ring-primary/20"
                            placeholder="?" oninput="updatePreview()">
                    </div>
                    <!-- Kolom D -->
                    <div class="text-center">
                        <label class="block text-lg font-bold text-gray-800 mb-2">D</label>
                        <input type="text" name="kolom_data[]" required maxlength="10"
                            value="{{ old('kolom_data.3') }}"
                            class="w-full px-3 py-3 border-2 border-primary rounded-lg text-center text-xl font-bold focus:outline-none focus:ring-2 focus:ring-primary/20"
                            placeholder="?" oninput="updatePreview()">
                    </div>
                    <!-- Kolom E -->
                    <div class="text-center">
                        <label class="block text-lg font-bold text-gray-800 mb-2">E</label>
                        <input type="text" name="kolom_data[]" required maxlength="10"
                            value="{{ old('kolom_data.4') }}"
                            class="w-full px-3 py-3 border-2 border-primary rounded-lg text-center text-xl font-bold focus:outline-none focus:ring-2 focus:ring-primary/20"
                            placeholder="?" oninput="updatePreview()">
                    </div>
                </div>
                
                <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-700">
                        <i class="ri-information-line mr-1"></i>
                        <strong>Contoh:</strong> Kalau isi kolom: <strong>4, 5, 1, 2, 3</strong>, maka soalnya bisa:<br>
                        • Pilihan: 4, 5, 1, 2 → Jawaban: <strong>3</strong> (yang tidak ditampilkan)<br>
                        • Pilihan: 5, 1, 2, 3 → Jawaban: <strong>4</strong> (yang tidak ditampilkan)<br>
                        dst.
                    </p>
                </div>
            </div>
        </div>

        <!-- Preview Kolom -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-gray-100 text-gray-800 px-6 py-3 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-center">Preview Kolom</h3>
            </div>
            <div class="p-6">
                <div class="flex gap-4 justify-center flex-wrap" id="previewContent">
                    <div class="w-16 h-16 bg-gray-100 border-2 border-gray-300 rounded-lg flex items-center justify-center text-2xl font-bold text-gray-400">
                        ?
                    </div>
                    <div class="w-16 h-16 bg-gray-100 border-2 border-gray-300 rounded-lg flex items-center justify-center text-2xl font-bold text-gray-400">
                        ?
                    </div>
                    <div class="w-16 h-16 bg-gray-100 border-2 border-gray-300 rounded-lg flex items-center justify-center text-2xl font-bold text-gray-400">
                        ?
                    </div>
                    <div class="w-16 h-16 bg-gray-100 border-2 border-gray-300 rounded-lg flex items-center justify-center text-2xl font-bold text-gray-400">
                        ?
                    </div>
                    <div class="w-16 h-16 bg-gray-100 border-2 border-gray-300 rounded-lg flex items-center justify-center text-2xl font-bold text-gray-400">
                        ?
                    </div>
                </div>
            </div>
        </div>

        <!-- Soal Kecermatan (Baris Soal) -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-primary text-white px-6 py-3">
                <h3 class="text-lg font-semibold text-center">Baris Soal Kecermatan</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-4">Isi baris soal (minimal 1 baris, maksimal 5 baris). Setiap baris akan digenerate {{ old('jumlah_soal', 20) }} soal otomatis.</p>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="barisSoalContainer">
                    
                    <!-- Baris Soal 1 -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="text-center font-medium text-gray-700 mb-3 pb-2 border-b border-gray-200">
                            Baris Soal 1 <span class="text-red-500">*</span>
                        </h4>
                        <textarea id="baris_soal_1" name="baris_soal[]" rows="6" required
                            class="kecermatan-editor w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Masukkan instruksi atau teks untuk baris soal 1...">{{ old('baris_soal.0') }}</textarea>
                    </div>

                    <!-- Baris Soal 2 -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="text-center font-medium text-gray-700 mb-3 pb-2 border-b border-gray-200">
                            Baris Soal 2 <span class="text-red-500">*</span>
                        </h4>
                        <textarea id="baris_soal_2" name="baris_soal[]" rows="6" required
                            class="kecermatan-editor w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Masukkan instruksi atau teks untuk baris soal 2...">{{ old('baris_soal.1') }}</textarea>
                    </div>

                    <!-- Baris Soal 3 -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="text-center font-medium text-gray-700 mb-3 pb-2 border-b border-gray-200">
                            Baris Soal 3 <span class="text-red-500">*</span>
                        </h4>
                        <textarea id="baris_soal_3" name="baris_soal[]" rows="6" required
                            class="kecermatan-editor w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Masukkan instruksi atau teks untuk baris soal 3...">{{ old('baris_soal.2') }}</textarea>
                    </div>

                    <!-- Baris Soal 4 -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="text-center font-medium text-gray-700 mb-3 pb-2 border-b border-gray-200">
                            Baris Soal 4 <span class="text-red-500">*</span>
                        </h4>
                        <textarea id="baris_soal_4" name="baris_soal[]" rows="6" required
                            class="kecermatan-editor w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Masukkan instruksi atau teks untuk baris soal 4...">{{ old('baris_soal.3') }}</textarea>
                    </div>

                    <!-- Baris Soal 5 (Opsional) -->
                    <div class="border border-gray-200 rounded-lg p-4 lg:col-span-2">
                        <h4 class="text-center font-medium text-gray-700 mb-3 pb-2 border-b border-gray-200">
                            Baris Soal 5 (Opsional)
                        </h4>
                        <textarea id="baris_soal_5" name="baris_soal[]" rows="6"
                            class="kecermatan-editor w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Masukkan instruksi atau teks untuk baris soal 5 (opsional)...">{{ old('baris_soal.4') }}</textarea>
                    </div>

                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end space-x-3">
            @if($package->package_id === 'standalone')
            <a href="{{ route('admin.tryout.index') }}"
                class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900">
                Batal
            </a>
            @else
            <a href="{{ route('admin.package.tryout.soal', [$package->package_id, $tryout->tryoutDetails->first()->tryout_detail_id ?? $tryout->tryout_id]) }}"
                class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900">
                Batal
            </a>
            @endif
            <button type="submit"
                class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                <i class="ri-save-line mr-1"></i> Simpan & Generate Soal
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    // Preview dari input manual
    function updatePreview() {
        const inputs = document.querySelectorAll('input[name="kolom_data[]"]');
        const previewContent = document.getElementById('previewContent');
        
        let html = '';
        inputs.forEach((input, index) => {
            const value = input.value.trim();
            const displayValue = value || '?';
            const bgClass = value ? 'bg-primary/10 border-primary text-primary' : 'bg-gray-100 border-gray-300 text-gray-400';
            
            html += `
                <div class="w-16 h-16 ${bgClass} border-2 rounded-lg flex items-center justify-center text-2xl font-bold shadow-sm">
                    ${displayValue}
                </div>
            `;
        });
        
        previewContent.innerHTML = html;
    }

    function prepareFormSubmission() {
        // Sync CKEditor content
        for (let i = 1; i <= 5; i++) {
            const editor = CKEDITOR.instances['baris_soal_' + i];
            if (editor) {
                editor.updateElement();
            }
        }
        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize CKEditor untuk baris soal
        const kecermatanConfig = {
            plugins: 'basicstyles,toolbar,wysiwygarea,elementspath,mathjax,sourcearea,clipboard,undo,format,list,indent,blockquote,table,horizontalrule,link',
            extraPlugins: 'mathjax',
            mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.9/MathJax.js?config=TeX-AMS_HTML',
            mathJaxClass: 'math-tex',
            removePlugins: 'elementspath,save,newpage,preview,print,templates,about,maximize,showblocks,magicline,pagebreak,iframe,flash,smiley,pagebreakutils',
            allowedContent: true,
            forcePasteAsPlainText: false,
            entities: false,
            startupFocus: false,
            toolbarStartupExpanded: true,
            toolbarCanCollapse: false,
            height: 200,
            toolbar: [
                { name: 'math', items: ['Mathjax'] },
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote'] },
                { name: 'insert', items: ['Table', 'HorizontalRule', 'Link'] },
                { name: 'tools', items: ['Source', '-', 'Undo', 'Redo'] }
            ]
        };

        // Initialize editors
        document.querySelectorAll('textarea.kecermatan-editor').forEach((textarea) => {
            if (!textarea.id) {
                textarea.id = 'kecermatan-editor-' + Math.random().toString(36).substr(2, 9);
            }
            CKEDITOR.replace(textarea.id, kecermatanConfig);
        });

        // Initial preview
        updatePreview();

        console.log('Kecermatan create form loaded');
    });
</script>
@endsection
