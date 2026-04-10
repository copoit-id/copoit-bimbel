@extends('admin.layout.admin')

@section('title', 'Edit Kolom Kecermatan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit Kolom Kecermatan</h2>
            <p class="text-gray-500">Tryout: <strong>{{ $tryout->name }}</strong></p>
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

    <form action="{{ route('admin.kecermatan.update', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id, 'column_id' => $column->column_id]) }}" 
        method="POST" 
        id="kecermatanForm"
        onsubmit="prepareFormSubmission()">
        @csrf
        @method('PUT')

        <!-- Info Kolom (Read-only) -->
        <div class="bg-yellow-50 rounded-lg border border-yellow-200 p-6">
            <h3 class="text-lg font-semibold text-yellow-800 mb-4">Informasi Kolom</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-yellow-700 mb-1">Tipe Kolom</label>
                    <div class="px-3 py-2 bg-white border border-yellow-300 rounded-lg text-yellow-800 capitalize">
                        {{ $column->tipe_kolom }}
                    </div>
                    <p class="text-xs text-yellow-600 mt-1">Tipe kolom tidak dapat diubah</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-yellow-700 mb-1">Jumlah Soal per Baris</label>
                    <div class="px-3 py-2 bg-white border border-yellow-300 rounded-lg text-yellow-800">
                        {{ $column->jumlah_soal }}
                    </div>
                    <p class="text-xs text-yellow-600 mt-1">Jumlah soal tidak dapat diubah</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-yellow-700 mb-1">Total Baris</label>
                    <div class="px-3 py-2 bg-white border border-yellow-300 rounded-lg text-yellow-800">
                        {{ $column->rows->count() }}
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-yellow-700 mb-1">Total Soal</label>
                    <div class="px-3 py-2 bg-white border border-yellow-300 rounded-lg text-yellow-800">
                        {{ $column->jumlah_soal * $column->rows->count() }}
                    </div>
                </div>
            </div>

            <!-- Edit Isi Kolom -->
            <div class="mt-6">
                <h4 class="text-sm font-medium text-yellow-800 mb-3">Edit Isi Kolom (A, B, C, D, E):</h4>
                <div class="grid grid-cols-5 gap-4">
                    @foreach($column->kolom_data as $index => $item)
                    <div class="text-center">
                        <label class="block text-lg font-bold text-yellow-800 mb-2">{{ ['A', 'B', 'C', 'D', 'E'][$index] }}</label>
                        <input type="text" name="kolom_data[]" required maxlength="10"
                            value="{{ old('kolom_data.' . $index, $item) }}"
                            class="w-full px-3 py-3 border-2 border-yellow-400 rounded-lg text-center text-xl font-bold text-yellow-800 bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400">
                    </div>
                    @endforeach
                </div>
                <p class="text-xs text-yellow-600 mt-2">
                    <i class="ri-information-line mr-1"></i>
                    Mengubah nilai kolom akan mengharuskan regenerate soal. Pastikan data sudah benar sebelum menyimpan.
                </p>
            </div>
        </div>

        <!-- Form Edit -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-primary text-white px-6 py-3">
                <h3 class="text-lg font-semibold text-center">Edit Data Kolom</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nama Kolom -->
                    <div>
                        <label for="nama_kolom" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Kolom <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="nama_kolom" name="nama_kolom" required
                            value="{{ old('nama_kolom', $column->nama_kolom) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Contoh: Kolom Huruf A">
                    </div>

                    <!-- Durasi Kolom -->
                    <div>
                        <label for="durasi_kolom" class="block text-sm font-medium text-gray-700 mb-2">
                            Durasi Kolom (Menit) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="durasi_kolom" name="durasi_kolom" min="1" required
                            value="{{ old('durasi_kolom', $column->durasi_kolom) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Contoh: 10">
                    </div>
                </div>
            </div>
        </div>

        <!-- Baris Soal -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-primary text-white px-6 py-3">
                <h3 class="text-lg font-semibold text-center">Edit Baris Soal</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-4">Edit instruksi untuk setiap baris soal:</p>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($column->rows as $index => $row)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="text-center font-medium text-gray-700 mb-3 pb-2 border-b border-gray-200">
                            Baris Soal {{ $row->row_number }} <span class="text-red-500">*</span>
                        </h4>
                        <textarea id="baris_soal_{{ $row->row_number }}" name="baris_soal[]" rows="6" required
                            class="kecermatan-editor w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Masukkan instruksi untuk baris soal {{ $row->row_number }}...">{{ old('baris_soal.' . $index, $row->row_text) }}</textarea>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between">
            <form action="{{ route('admin.kecermatan.regenerate', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id, 'column_id' => $column->column_id]) }}" 
                method="POST" class="inline"
                onsubmit="return confirm('Yakin ingin generate ulang semua soal? Soal yang ada akan diganti dengan yang baru menggunakan random yang berbeda.');">
                @csrf
                <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 text-sm">
                    <i class="ri-refresh-line"></i> Generate Ulang Soal
                </button>
            </form>
            
            <div class="flex items-center gap-3">
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
                    <i class="ri-save-line mr-1"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    function prepareFormSubmission() {
        // Sync CKEditor content
        @foreach($column->rows as $row)
            const editor{{ $row->row_number }} = CKEDITOR.instances['baris_soal_{{ $row->row_number }}'];
            if (editor{{ $row->row_number }}) {
                editor{{ $row->row_number }}.updateElement();
            }
        @endforeach
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

        console.log('Kecermatan edit form loaded');
    });
</script>
@endsection
