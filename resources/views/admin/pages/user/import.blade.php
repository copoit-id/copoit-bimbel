@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Import Users</h2>
            <p class="text-gray-500">Import data pengguna dari file CSV</p>
        </div>
        <a href="{{ route('admin.user.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
    </div>

    <div class="bg-white p-8 rounded-lg border border-border">
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-2">Panduan Import</h3>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                    <li>File harus dalam format CSV dengan separator titik koma (;)</li>
                    <li>Kolom yang diperlukan: name, email, username (opsional), password (opsional), date_of_birth
                        (opsional), status (opsional)</li>
                    <li>Email harus unique, jika sudah ada akan dilewati</li>
                    <li>Password default: "password123" jika tidak diisi</li>
                    <li>Username akan di-generate otomatis jika kosong</li>
                    <li>Status default: "active" jika tidak diisi</li>
                    <li>Format tanggal lahir: DD/MM/YYYY atau DD/MM/YY</li>
                    <li>Maksimal ukuran file 10MB</li>
                </ul>
            </div>

            <div class="flex gap-4">
                <a href="{{ route('admin.user.import.template') }}"
                    class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 flex items-center gap-2">
                    <i class="ri-download-line"></i>
                    Download Template
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <i class="ri-check-circle-line text-green-500 mr-2"></i>
                <div>
                    <p class="text-green-800">{{ session('success') }}</p>
                    @if(session('import_errors') && count(session('import_errors')) > 0)
                    <details class="mt-2">
                        <summary class="cursor-pointer text-sm text-green-700">Lihat error detail ({{
                            count(session('import_errors')) }} error)</summary>
                        <ul class="mt-2 text-sm text-red-600 space-y-1 max-h-32 overflow-y-auto">
                            @foreach(session('import_errors') as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </details>
                    @endif
                </div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <i class="ri-error-warning-line text-red-500 mr-2"></i>
                <p class="text-red-800">{{ session('error') }}</p>
            </div>
        </div>
        @endif

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <i class="ri-error-warning-line text-red-500 mr-2"></i>
                <div>
                    <p class="text-red-800 font-medium">Terjadi kesalahan:</p>
                    <ul class="mt-1 text-sm text-red-600">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <form action="{{ route('admin.user.import.process') }}" method="POST" enctype="multipart/form-data"
            id="importForm">
            @csrf
            <div class="space-y-6">
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Upload File CSV</label>
                    <div class="flex items-center justify-center w-full">
                        <label for="csv_file" id="dropzone"
                            class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6" id="dropzone-content">
                                <i class="ri-upload-cloud-2-line text-4xl text-gray-500 mb-2"></i>
                                <p class="mb-2 text-sm text-gray-500">
                                    <span class="font-semibold">Klik untuk upload</span> atau drag and drop
                                </p>
                                <p class="text-xs text-gray-500">CSV file (MAX. 10MB)</p>
                            </div>
                            <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt" class="hidden"
                                required />
                        </label>
                    </div>
                    <div id="file-info" class="hidden mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center gap-2">
                            <i class="ri-file-text-line text-blue-600"></i>
                            <span id="file-name" class="text-sm text-blue-800"></span>
                            <span id="file-size" class="text-xs text-blue-600"></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-4">
                    <button type="button" onclick="resetForm()"
                        class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5">
                        Reset
                    </button>
                    <button type="submit" id="submitBtn"
                        class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="submitText">Import Data</span>
                        <i id="submitLoader" class="ri-loader-4-line animate-spin hidden ml-2"></i>
                    </button>
                </div>
            </div>
        </form>
        @if(session('import_token'))
        <div class="mt-8 bg-white p-6 rounded-lg border border-border">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Progress Import</h3>
                <span class="text-xs text-gray-500">Token: {{ session('import_token') }}</span>
            </div>

            <div class="w-full bg-gray-100 rounded-full h-3 mb-3 overflow-hidden">
                <div id="bar" class="bg-primary h-3 rounded-full transition-all" style="width:0%"></div>
            </div>

            <div class="grid grid-cols-3 gap-4 text-sm">
                <div class="p-3 rounded-lg bg-gray-50">
                    <div class="text-gray-500">Processed</div>
                    <div id="processed" class="font-semibold">0</div>
                </div>
                <div class="p-3 rounded-lg bg-green-50">
                    <div class="text-green-700">Imported</div>
                    <div id="imported" class="font-semibold text-green-700">0</div>
                </div>
                <div class="p-3 rounded-lg bg-yellow-50">
                    <div class="text-yellow-700">Skipped</div>
                    <div id="skipped" class="font-semibold text-yellow-700">0</div>
                </div>
            </div>

            <div class="mt-3 text-xs text-gray-500">
                Status: <span id="statusText">Berjalan...</span> • Updated: <span id="updatedAt">-</span>
            </div>
        </div>
        @endif

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('csv_file');
    const dropzone = document.getElementById('dropzone');
    const dropzoneContent = document.getElementById('dropzone-content');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const submitLoader = document.getElementById('submitLoader');
    const form = document.getElementById('importForm');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, unhighlight, false);
    });

    dropzone.addEventListener('drop', handleDrop, false);

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight(e) {
        dropzone.classList.add('border-primary', 'bg-primary/5');
    }

    function unhighlight(e) {
        dropzone.classList.remove('border-primary', 'bg-primary/5');
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        handleFileSelect();
    }

    fileInput.addEventListener('change', handleFileSelect);

    function handleFileSelect() {
        const file = fileInput.files[0];
        if (file) {
            if (!file.name.toLowerCase().endsWith('.csv')) {
                alert('Please select a CSV file');
                resetForm();
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB');
                resetForm();
                return;
            }

            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.classList.remove('hidden');

            dropzoneContent.innerHTML = `
                <i class="ri-file-check-line text-4xl text-green-500 mb-2"></i>
                <p class="mb-2 text-sm text-green-600">
                    <span class="font-semibold">File siap diupload</span>
                </p>
                <p class="text-xs text-gray-500">Klik untuk ganti file</p>
            `;
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitText.textContent = 'Processing...';
        submitLoader.classList.remove('hidden');
    });

    window.resetForm = function() {
        fileInput.value = '';
        fileInfo.classList.add('hidden');
        dropzoneContent.innerHTML = `
            <i class="ri-upload-cloud-2-line text-4xl text-gray-500 mb-2"></i>
            <p class="mb-2 text-sm text-gray-500">
                <span class="font-semibold">Klik untuk upload</span> atau drag and drop
            </p>
            <p class="text-xs text-gray-500">CSV file (MAX. 10MB)</p>
        `;
        submitBtn.disabled = false;
        submitText.textContent = 'Import Data';
        submitLoader.classList.add('hidden');
    };
});
</script>
@if(session('import_token'))
<script>
    (function(){
  const token = @json(session('import_token'));
  const statusUrl = @json(route('user.import.status', ['token' => '__TOKEN__'])).replace('__TOKEN__', token);

  const el = {
    bar: document.getElementById('bar'),
    processed: document.getElementById('processed'),
    imported: document.getElementById('imported'),
    skipped: document.getElementById('skipped'),
    statusText: document.getElementById('statusText'),
    updatedAt: document.getElementById('updatedAt'),
  };

  let fakeMax = 0; // fallback kalau total tidak diketahui

  async function poll() {
    try {
      const res = await fetch(statusUrl, { cache: 'no-store' });
      const data = await res.json();
      const p = data.progress || {};
      const done = !!data.done;

      const processed = p.processed || 0;
      const imported  = p.imported  || 0;
      const skipped   = p.skipped   || 0;

      // Update angka
      el.processed.textContent = processed.toLocaleString();
      el.imported.textContent  = imported.toLocaleString();
      el.skipped.textContent   = skipped.toLocaleString();
      el.updatedAt.textContent = p.updated_at || '-';
      el.statusText.textContent = done ? 'Selesai ✅' : 'Berjalan...';

      // Progress bar (tanpa total). Naik pelan sampai 90%, lalu 100% saat done
      if (!done) {
        fakeMax = Math.max(fakeMax, processed);
        const percent = Math.min(90, Math.max(5, Math.round((processed / (fakeMax || 1)) * 90)));
        el.bar.style.width = percent + '%';
      } else {
        el.bar.style.width = '100%';
      }

      if (!done) setTimeout(poll, 2000);
    } catch (e) {
      setTimeout(poll, 4000);
    }
  }
  poll();
})();
</script>
@endif

@endsection
