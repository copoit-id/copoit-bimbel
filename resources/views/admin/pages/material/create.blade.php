@extends('admin.layout.admin')

@section('title', 'Tambah Materi')

@section('content')
@php
    $materialCategoryTree = $categories->map(function ($category) {
        return [
            'id' => $category->category_id,
            'name' => $category->name,
            'children' => $category->activeChildren->map(function ($child) {
                return [
                    'id' => $child->category_id,
                    'name' => $child->name,
                ];
            })->values(),
        ];
    })->values();
    $selectedAccessDurationUnit = old('access_duration_unit', 'forever');
    $selectedAccessDurationValue = old('access_duration_value', 1);
@endphp
<div class="container mx-auto px-4">
    <div class="mb-6">
        <a href="{{ route('admin.material.index') }}" class="text-gray-600 hover:text-gray-800 flex items-center gap-1 mb-2">
            <i class="ri-arrow-left-line"></i>
            Kembali ke Daftar Materi
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Tambah Materi Baru</h1>
    </div>

    @if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.material.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Materi <span class="text-red-500">*</span></label>
                        <input type="text" name="title" id="materialTitle" value="{{ old('title') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Materi <span class="text-red-500">*</span></label>
                        <select name="type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" onchange="updatePlaceholder()">
                            <option value="video" {{ old('type') == 'video' ? 'selected' : '' }}>Video</option>
                            <option value="document" {{ old('type') == 'document' ? 'selected' : '' }}>Dokumen/PDF</option>
                            <option value="live_session" {{ old('type') == 'live_session' ? 'selected' : '' }}>{{ $clientBranding['live_session_label'] ?? 'Kelas Belajar' }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL Konten <span class="text-red-500">*</span></label>
                        <input type="url" name="content_url" id="contentUrl" value="{{ old('content_url') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="https://...">
                        <p class="text-xs text-gray-500 mt-1" id="urlHint">Masukkan URL video (YouTube, Vimeo, dll)</p>
                        <p class="text-xs mt-1 hidden" id="driveTitleStatus"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Durasi (menit)</label>
                        <input type="number" name="duration_minutes" value="{{ old('duration_minutes') }}" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Contoh: 30">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Urut</label>
                        <input type="number" name="order_number" value="{{ old('order_number') }}" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="0">
                        <p class="text-xs text-gray-500 mt-1">Biarkan kosong untuk urutan terakhir</p>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-5">
                        <?php $selectedTypePrice = old('type_price', 'paid'); ?>
                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-gray-800">Akses & Penjualan</h3>
                            <p class="text-sm text-gray-500 mt-1">Atur apakah materi tampil di user dan bisa dibeli terpisah.</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <input type="checkbox" name="is_displayed" value="1" {{ old('is_displayed', true) ? 'checked' : '' }} class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                                <span>
                                    <span class="block text-sm font-semibold text-gray-800">Tampilkan di user</span>
                                    <span class="block text-xs text-gray-500 mt-1">Jika mati, materi tidak muncul di katalog user.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <input type="checkbox" id="is_for_sale" name="is_for_sale" value="1" {{ old('is_for_sale') ? 'checked' : '' }} class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                                <span>
                                    <span class="block text-sm font-semibold text-gray-800">Dijual terpisah</span>
                                    <span class="block text-xs text-gray-500 mt-1">Jika mati, materi tampil tapi tidak bisa dibeli individual.</span>
                                </span>
                            </label>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="type_price" class="block text-sm font-medium text-gray-700 mb-2">Tipe Harga</label>
                                <select id="type_price" name="type_price" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="paid" @selected($selectedTypePrice === 'paid')>Berbayar</option>
                                    <option value="free_unconditional" @selected($selectedTypePrice === 'free_unconditional')>Gratis Tanpa Syarat</option>
                                    <option value="free_conditional" @selected($selectedTypePrice === 'free_conditional')>Gratis Bersyarat</option>
                                </select>
                            </div>
                            <div id="price-wrapper">
                                <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Harga (Rp)</label>
                                <input type="number" id="price" name="price" value="{{ old('price', 0) }}" min="0" step="1" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="0">
                            </div>
                        </div>
                        <div id="conditional-requirement-wrapper" class="mt-4 {{ $selectedTypePrice === 'free_conditional' ? '' : 'hidden' }}">
                            <label for="conditional_requirement" class="block text-sm font-medium text-gray-700 mb-2">Syarat Akses Gratis</label>
                            <textarea id="conditional_requirement" name="conditional_requirement" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Syarat akses gratis bersyarat">{{ old('conditional_requirement') }}</textarea>
                        </div>
                        <div id="access-duration-wrapper" class="mt-4 {{ old('is_for_sale') ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Durasi Akses Setelah Dibeli</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <select name="access_duration_unit" id="access_duration_unit"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="forever" @selected($selectedAccessDurationUnit === 'forever')>Selamanya</option>
                                    <option value="day" @selected($selectedAccessDurationUnit === 'day')>Hari</option>
                                    <option value="week" @selected($selectedAccessDurationUnit === 'week')>Minggu</option>
                                    <option value="month" @selected($selectedAccessDurationUnit === 'month')>Bulan</option>
                                    <option value="year" @selected($selectedAccessDurationUnit === 'year')>Tahun</option>
                                </select>
                                <input type="number" name="access_duration_value" id="access_duration_value"
                                    value="{{ $selectedAccessDurationValue }}" min="1" max="1200"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary"
                                    placeholder="Jumlah">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Dipakai untuk pembelian materi terpisah. Jika materi masuk paket, aksesnya mengikuti durasi paket.</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Thumbnail</label>
                        <input type="file" name="thumbnail" accept="image/*" id="thumbnailInput" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-xs text-gray-500 mt-1">Rasio ideal: <strong>16:9</strong> (contoh: 640x360, 1280x720). Format: JPG, PNG, GIF. Maks: 2MB</p>
                        <div id="thumbnailPreview" class="mt-3 hidden">
                            <div class="text-xs text-gray-500 mb-2">Preview (16:9):</div>
                            <div class="bg-gray-100 rounded-lg overflow-hidden border border-gray-300" style="aspect-ratio: 16/9; max-width: 320px;">
                                <img id="previewImage" src="" alt="Preview" class="w-full h-full object-cover">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Utama</label>
                            <select id="goalCategoryId" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">Pilih kategori utama</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->category_id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Materi</label>
                            <select name="category_id" id="materialCategoryId" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" disabled>
                                <option value="">Pilih kategori utama terlebih dahulu</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1" id="materialCategoryHint">Opsi kategori materi mengikuti kategori utama yang dipilih.</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <textarea name="description" rows="5" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Deskripsi singkat tentang materi...">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-6 border-t">
                <a href="{{ route('admin.material.index') }}" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Batal</a>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    <i class="ri-save-line mr-1"></i>
                    Simpan Materi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function updatePlaceholder() {
        const type = document.querySelector('select[name="type"]').value;
        const urlInput = document.getElementById('contentUrl');
        const hint = document.getElementById('urlHint');
        
        switch(type) {
            case 'video':
                urlInput.placeholder = 'https://youtube.com/watch?v=...';
                hint.textContent = 'Masukkan URL video (YouTube, Vimeo, Google Drive, dll)';
                break;
            case 'document':
                urlInput.placeholder = 'https://drive.google.com/file/d/...';
                hint.textContent = 'Masukkan URL dokumen/PDF (Google Drive, Dropbox, dll)';
                break;
            case 'live_session':
                urlInput.placeholder = 'https://zoom.us/j/...';
                hint.textContent = @js('Masukkan URL ' . strtolower($clientBranding['live_session_label'] ?? 'kelas belajar') . ' (Zoom, Google Meet, dll)');
                break;
        }
    }
    
    // Initialize on load
    updatePlaceholder();

    const accessDurationUnit = document.getElementById('access_duration_unit');
    const accessDurationValue = document.getElementById('access_duration_value');
    const saleCheckbox = document.getElementById('is_for_sale');
    const typePriceSelect = document.getElementById('type_price');
    const priceInput = document.getElementById('price');
    const priceWrapper = document.getElementById('price-wrapper');
    const requirementWrapper = document.getElementById('conditional-requirement-wrapper');
    const requirementInput = document.getElementById('conditional_requirement');
    const accessDurationWrapper = document.getElementById('access-duration-wrapper');
    const syncAccessDuration = () => {
        if (!accessDurationUnit || !accessDurationValue) return;
        const isForSale = saleCheckbox?.checked ?? false;
        const typePrice = typePriceSelect?.value || 'paid';
        priceWrapper?.classList.toggle('hidden', !isForSale || typePrice !== 'paid');
        if (priceInput) {
            priceInput.disabled = !isForSale || typePrice !== 'paid';
            if (typePrice !== 'paid') priceInput.value = 0;
        }
        requirementWrapper?.classList.toggle('hidden', !isForSale || typePrice !== 'free_conditional');
        if (requirementInput) requirementInput.disabled = !isForSale || typePrice !== 'free_conditional';
        accessDurationWrapper?.classList.toggle('hidden', !isForSale);
        accessDurationUnit.disabled = !isForSale;
        accessDurationValue.disabled = !isForSale || accessDurationUnit.value === 'forever';
    };
    syncAccessDuration();
    accessDurationUnit?.addEventListener('change', syncAccessDuration);
    saleCheckbox?.addEventListener('change', syncAccessDuration);
    typePriceSelect?.addEventListener('change', syncAccessDuration);

    const materialCategoryTree = @json($materialCategoryTree);
    const selectedMaterialCategoryId = Number(@json((int) old('category_id', 0)));
    const goalCategorySelect = document.getElementById('goalCategoryId');
    const materialCategorySelect = document.getElementById('materialCategoryId');
    const materialCategoryHint = document.getElementById('materialCategoryHint');

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const findGoalCategoryId = (categoryId) => {
        if (!categoryId) return '';

        for (const goal of materialCategoryTree) {
            if (Number(goal.id) === Number(categoryId)) {
                return goal.id;
            }

            if ((goal.children || []).some(child => Number(child.id) === Number(categoryId))) {
                return goal.id;
            }
        }

        return '';
    };

    const renderMaterialCategoryOptions = (goalId, selectedId = '') => {
        if (!goalCategorySelect || !materialCategorySelect) return;

        const goal = materialCategoryTree.find(item => Number(item.id) === Number(goalId));
        materialCategorySelect.innerHTML = '';

        if (!goal) {
            materialCategorySelect.disabled = true;
            materialCategorySelect.innerHTML = '<option value="">Pilih kategori utama terlebih dahulu</option>';
            if (materialCategoryHint) {
                materialCategoryHint.textContent = 'Opsi kategori materi mengikuti kategori utama yang dipilih.';
            }
            return;
        }

        materialCategorySelect.disabled = false;
        const options = [
            { id: goal.id, name: `Semua ${goal.name}` },
            ...(goal.children || []),
        ];

        materialCategorySelect.innerHTML = options
            .map(option => `<option value="${option.id}" ${Number(option.id) === Number(selectedId) ? 'selected' : ''}>${escapeHtml(option.name)}</option>`)
            .join('');

        if (materialCategoryHint) {
            materialCategoryHint.textContent = (goal.children || []).length > 0
                ? 'Pilih kategori materi untuk kategori utama ini.'
                : 'Kategori utama ini belum punya kategori materi khusus. Materi akan disimpan ke kategori utama ini.';
        }
    };

    if (goalCategorySelect && materialCategorySelect) {
        const initialGoalCategoryId = findGoalCategoryId(selectedMaterialCategoryId);
        if (initialGoalCategoryId) {
            goalCategorySelect.value = initialGoalCategoryId;
            renderMaterialCategoryOptions(initialGoalCategoryId, selectedMaterialCategoryId);
        }

        goalCategorySelect.addEventListener('change', () => {
            renderMaterialCategoryOptions(goalCategorySelect.value, goalCategorySelect.value);
        });
    }

    const materialTitleInput = document.getElementById('materialTitle');
    const contentUrlInput = document.getElementById('contentUrl');
    const driveTitleStatus = document.getElementById('driveTitleStatus');
    let materialTitleTouched = Boolean(materialTitleInput?.value.trim());
    let driveTitleTimer = null;
    let driveTitleRequestId = 0;

    const isGoogleDriveUrl = (value) => {
        try {
            const url = new URL(value);
            return ['drive.google.com', 'docs.google.com'].includes(url.hostname) ||
                url.hostname.endsWith('.drive.google.com') ||
                url.hostname.endsWith('.docs.google.com');
        } catch (error) {
            return false;
        }
    };

    const setDriveTitleStatus = (message, type = 'muted') => {
        if (!driveTitleStatus) return;
        driveTitleStatus.textContent = message;
        driveTitleStatus.classList.toggle('hidden', !message);
        driveTitleStatus.className = `text-xs mt-1 ${message ? '' : 'hidden'} ${type === 'error' ? 'text-amber-600' : 'text-gray-500'}`;
    };

    const shouldAutoFillTitle = () => {
        return materialTitleInput &&
            (!materialTitleTouched || materialTitleInput.dataset.autofilled === 'true' || materialTitleInput.value.trim() === '');
    };

    const fetchDriveTitle = async () => {
        const url = contentUrlInput?.value.trim() || '';
        if (!url || !isGoogleDriveUrl(url) || !shouldAutoFillTitle()) {
            setDriveTitleStatus('');
            return;
        }

        const requestId = ++driveTitleRequestId;
        setDriveTitleStatus('Membaca judul dari Google Drive...');

        try {
            const response = await fetch('{{ route('admin.material.drive-title') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ url }),
            });
            const data = await response.json().catch(() => ({}));

            if (requestId !== driveTitleRequestId || !shouldAutoFillTitle()) {
                return;
            }

            if (response.ok && data.title) {
                materialTitleInput.value = data.title;
                materialTitleInput.dataset.autofilled = 'true';
                setDriveTitleStatus('Judul otomatis diisi dari Google Drive.');
            } else {
                setDriveTitleStatus(data.message || 'Judul file Drive tidak bisa dibaca.', 'error');
            }
        } catch (error) {
            if (requestId === driveTitleRequestId) {
                setDriveTitleStatus('Gagal membaca judul file Drive.', 'error');
            }
        }
    };

    materialTitleInput?.addEventListener('input', () => {
        materialTitleTouched = true;
        materialTitleInput.dataset.autofilled = 'false';
    });

    contentUrlInput?.addEventListener('input', () => {
        clearTimeout(driveTitleTimer);
        driveTitleTimer = setTimeout(fetchDriveTitle, 500);
    });

    contentUrlInput?.addEventListener('paste', () => {
        clearTimeout(driveTitleTimer);
        driveTitleTimer = setTimeout(fetchDriveTitle, 150);
    });

    // Thumbnail preview
    const thumbnailInput = document.getElementById('thumbnailInput');
    const thumbnailPreview = document.getElementById('thumbnailPreview');
    const previewImage = document.getElementById('previewImage');

    if (thumbnailInput) {
        thumbnailInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    previewImage.src = event.target.result;
                    thumbnailPreview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });
    }
</script>
@endsection
