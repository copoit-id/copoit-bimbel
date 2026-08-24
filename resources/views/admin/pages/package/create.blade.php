@extends('admin.layout.admin')

@php
    $allowVideoThumbnail = $clientBranding['allow_video_thumbnail'] ?? false;
    $videoExtensions = ['mp4', 'webm', 'mov', 'm4v'];
    $selectedPriceType = old('type_price', $package->type_price ?? 'paid');
    $selectedClaimRequirementType = old('free_claim_requirement_type', $package->free_claim_requirement_type ?? 'manual_proof');
    $selectedClaimTryoutId = old('free_claim_tryout_id', $package->free_claim_tryout_id ?? '');
    $oldFeatures = old('features');
    $selectedFeatures = is_array($oldFeatures) ? $oldFeatures : [''];
    $selectedAccessDurationUnit = old('access_duration_unit', $package->access_duration_unit ?? 'forever');
    $selectedAccessDurationValue = old('access_duration_value', $package->access_duration_value ?? 1);

    if (!is_array($oldFeatures) && isset($package) && $package->features) {
        $decodedFeatures = json_decode($package->features, true);
        $selectedFeatures = is_array($decodedFeatures)
            ? $decodedFeatures
            : preg_split('/\r\n|\r|\n/', $package->features);
    }

    $selectedFeatures = array_values(array_filter(array_map(
        fn ($feature) => trim((string) $feature),
        $selectedFeatures
    )));
    $selectedFeatures = !empty($selectedFeatures) ? $selectedFeatures : [''];
@endphp

@section('content')
@php
    $planModules = app(\App\Services\PlanModuleService::class);
    $canManageTryout = $planModules->allows('tryout');
    $canManageCertification = $planModules->allows('certification');
    $canManageTesKoran = $planModules->allows('tes_koran')
        && (auth()->user()?->hasPermission('tes_koran', 'view') ?? false);
@endphp
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">{{ isset($package) ? 'Edit Paket' : 'Tambah Paket Baru' }}</h2>
            <p class="text-gray-500">{{ isset($package) ? 'Perbarui informasi paket' : 'Buat paket bimbel atau tryout
                baru' }}</p>
        </div>
        <a href="{{ route('admin.package.index', request()->query()) }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
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

    <!-- Create/Edit Form -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <form
            action="{{ isset($package) ? route('admin.package.update', array_merge(request()->query(), ['package_id' => $package->package_id])) : route('admin.package.store') }}"
            method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($package))
            @method('PUT')
            @endif

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nama Paket <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name"
                                value="{{ isset($package) ? $package->name : old('name') }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>

                        <div>
                            <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ $allowVideoThumbnail ? 'Thumbnail (Gambar / Video)' : 'Gambar Paket' }}
                            </label>
                            @if(isset($package) && $package->image)
                            @php
                                $currentThumb = $package->image;
                                $currentExt = strtolower(pathinfo($currentThumb, PATHINFO_EXTENSION));
                                $currentIsVideo = in_array($currentExt, $videoExtensions, true);
                                $currentUrl = Storage::url($currentThumb);
                            @endphp
                            <div class="mb-3">
                                @if($currentIsVideo)
                                <video src="{{ $currentUrl }}" class="w-32 h-20 rounded-lg border object-cover" controls preload="metadata" playsinline></video>
                                <p class="text-sm text-gray-500 mt-1">Video saat ini</p>
                                @else
                                <img src="{{ $currentUrl }}" alt="Current image"
                                    class="w-32 h-20 object-cover rounded-lg border">
                                <p class="text-sm text-gray-500 mt-1">Gambar saat ini</p>
                                @endif
                            </div>
                            @endif
                            <input type="file" id="image" name="image"
                                accept="{{ $allowVideoThumbnail ? 'image/*,video/mp4,video/webm,video/quicktime' : 'image/*' }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <p class="text-sm text-gray-500 mt-1">
                                @if($allowVideoThumbnail)
                                Ukuran gambar ideal: 1500 × 600 px (rasio 5:2). Format gambar: JPG, PNG, WEBP (2MB). Video: MP4/WEBM/MOV (maks 50MB).
                                @else
                                Ukuran ideal: 1500 × 600 px (rasio 5:2). Format: JPG, PNG, WEBP (maks. 2MB).
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="type_package" class="block text-sm font-medium text-gray-700 mb-2">Tipe Paket
                                <span class="text-red-500">*</span></label>
                            <select id="type_package" name="type_package" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="bimbel" {{ (isset($package) && $package->type_package === 'bimbel') ||
                                    old('type_package') === 'bimbel' ? 'selected' : '' }}>Bimbel</option>
                                @if($canManageTryout || (isset($package) && $package->type_package === 'tryout'))
                                    <option value="tryout" {{ (isset($package) && $package->type_package === 'tryout') ||
                                        old('type_package') === 'tryout' ? 'selected' : '' }}>Tryout</option>
                                @endif
                                @if($canManageCertification || (isset($package) && $package->type_package === 'sertifikasi'))
                                    <option value="sertifikasi" {{ (isset($package) && $package->type_package ===
                                        'sertifikasi') || old('type_package') === 'sertifikasi' ? 'selected' : ''
                                        }}>Sertifikasi</option>
                                @endif
                                @if($canManageTesKoran || (isset($package) && $package->type_package === 'tes_koran'))
                                <option value="tes_koran" {{ (isset($package) && $package->type_package ===
                                    'tes_koran') || old('type_package') === 'tes_koran' ? 'selected' : ''
                                    }}>Tes Koran</option>
                                @endif
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status
                                <span class="text-red-500">*</span></label>
                            <select id="status" name="status" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="active" {{ (isset($package) && $package->status === 'active') ||
                                    old('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="inactive" {{ (isset($package) && $package->status === 'inactive') ||
                                    old('status') === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <div class="mb-5">
                            <h3 class="text-base font-semibold text-gray-800">Akses & Harga Paket</h3>
                            <p class="text-sm text-gray-500 mt-1">Atur visibilitas paket di user dan metode klaim/pembelian.</p>
                        </div>

                        <label class="mb-5 flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <input type="checkbox" name="is_displayed" value="1"
                                {{ old('is_displayed', isset($package) ? ($package->is_displayed ?? true) : true) ? 'checked' : '' }}
                                class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">Tampilkan di halaman user</span>
                                <span class="block text-xs text-gray-500 mt-1">Jika mati, paket tetap ada di admin tapi hilang dari katalog user.</span>
                            </span>
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="type_price" class="block text-sm font-medium text-gray-700 mb-2">Tipe Harga
                                    <span class="text-red-500">*</span></label>
                                <select id="type_price" name="type_price" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <option value="paid" {{ $selectedPriceType === 'paid' ? 'selected' : '' }}>Berbayar</option>
                                    <option value="free_unconditional" {{ $selectedPriceType === 'free_unconditional' ? 'selected' : '' }}>Gratis Tanpa Syarat</option>
                                    <option value="free_conditional" {{ $selectedPriceType === 'free_conditional' ? 'selected' : '' }}>Gratis Bersyarat</option>
                                </select>
                            </div>

                            <div id="price-field"
                                class="{{ $selectedPriceType === 'paid' ? '' : 'hidden' }}">
                                <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Harga <span
                                        class="text-red-500">*</span></label>
                                <input type="number" id="price" name="price" min="0"
                                    value="{{ isset($package) ? $package->price : old('price', 0) }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                        </div>

                        <div id="conditional-requirement-wrapper"
                            class="mt-4 {{ $selectedPriceType === 'free_conditional' ? '' : 'hidden' }}">
                            <label for="free_claim_requirement_type" class="block text-sm font-medium text-gray-700 mb-2">Metode Syarat Klaim <span class="text-red-500">*</span></label>
                            <select id="free_claim_requirement_type" name="free_claim_requirement_type" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="manual_proof" @selected($selectedClaimRequirementType === 'manual_proof')>Upload bukti, lalu verifikasi admin</option>
                                <option value="completed_tryout" @selected($selectedClaimRequirementType === 'completed_tryout')>Selesaikan Tryout tertentu</option>
                            </select>

                            <div id="manual-claim-requirement" class="mt-4 {{ $selectedClaimRequirementType === 'manual_proof' ? '' : 'hidden' }}">
                                <label for="conditional_requirement" class="block text-sm font-medium text-gray-700 mb-2">Instruksi upload bukti <span class="text-red-500">*</span></label>
                                <textarea id="conditional_requirement" name="conditional_requirement" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Contoh: Follow akun Instagram @copoit, upload bukti, dll.">{{ old('conditional_requirement', $package->conditional_requirement ?? '') }}</textarea>
                            </div>

                            <div id="tryout-claim-requirement" class="mt-4 {{ $selectedClaimRequirementType === 'completed_tryout' ? '' : 'hidden' }}">
                                <label for="free_claim_tryout_id" class="block text-sm font-medium text-gray-700 mb-2">Tryout yang wajib diselesaikan <span class="text-red-500">*</span></label>
                                <select id="free_claim_tryout_id" name="free_claim_tryout_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <option value="">Pilih Tryout</option>
                                    @foreach($claimTryouts ?? [] as $claimTryout)
                                        <option value="{{ $claimTryout->tryout_id }}" @selected((string) $selectedClaimTryoutId === (string) $claimTryout->tryout_id)>{{ $claimTryout->name }}{{ $claimTryout->is_active ? '' : ' (tidak aktif)' }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-xs text-gray-500">Akses paket langsung aktif setelah peserta memiliki hasil Tryout berstatus selesai.</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Durasi Akses Setelah Dibeli</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <select name="access_duration_unit" id="access_duration_unit"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <option value="forever" @selected($selectedAccessDurationUnit === 'forever')>Selamanya</option>
                                    <option value="day" @selected($selectedAccessDurationUnit === 'day')>Hari</option>
                                    <option value="week" @selected($selectedAccessDurationUnit === 'week')>Minggu</option>
                                    <option value="month" @selected($selectedAccessDurationUnit === 'month')>Bulan</option>
                                    <option value="year" @selected($selectedAccessDurationUnit === 'year')>Tahun</option>
                                </select>
                                <input type="number" name="access_duration_value" id="access_duration_value"
                                       min="1" max="1200" value="{{ $selectedAccessDurationValue }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                       placeholder="Jumlah durasi">
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Pilih Selamanya untuk akses tanpa batas waktu.</p>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                        <textarea id="description" name="description" rows="4"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Masukkan deskripsi paket...">{{ isset($package) ? $package->description : old('description') }}</textarea>
                    </div>

                    <div>
                        <label for="telegram_group_url" class="block text-sm font-medium text-gray-700 mb-2">Link Grub (Opsional)</label>
                        <input type="url" id="telegram_group_url" name="telegram_group_url"
                            value="{{ isset($package) ? $package->telegram_group_url : old('telegram_group_url') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="https://t.me/namagrup">
                        @error('telegram_group_url')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ features: @js($selectedFeatures) }" class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fitur Paket</label>
                        <template x-for="(feature, index) in features" :key="index">
                            <div class="flex gap-2">
                                <input type="text" :name="'features[' + index + ']'" x-model="features[index]"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="Contoh: 50 PDF Materi Lengkap" />

                                <button type="button" @click="features.splice(index, 1)"
                                    class="px-3 py-2 text-red-500 hover:text-red-700 border border-red-300 rounded-lg hover:bg-red-50">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </template>

                        <button type="button" @click="features.push('')"
                            class="flex items-center gap-2 text-sm text-primary hover:text-primary/80 mt-2">
                            <i class="ri-add-line"></i>
                            Tambah Fitur
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end px-6 py-5 space-x-2 border-t border-gray-200">
                <a href="{{ route('admin.package.index', request()->query()) }}"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900">
                    Batal
                </a>
                <button type="submit"
                    class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5">
                    {{ isset($package) ? 'Perbarui Paket' : 'Simpan Paket' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const typePriceSelect = document.getElementById('type_price');
    const priceField = document.getElementById('price-field');
    const priceInput = document.getElementById('price');
    const requirementWrapper = document.getElementById('conditional-requirement-wrapper');
    const requirementInput = document.getElementById('conditional_requirement');
    const claimRequirementType = document.getElementById('free_claim_requirement_type');
    const manualClaimRequirement = document.getElementById('manual-claim-requirement');
    const tryoutClaimRequirement = document.getElementById('tryout-claim-requirement');
    const claimTryoutInput = document.getElementById('free_claim_tryout_id');
    const durationUnit = document.getElementById('access_duration_unit');
    const durationValue = document.getElementById('access_duration_value');

    function toggleFields() {
        if (typePriceSelect.value === 'paid') {
            priceField.classList.remove('hidden');
            priceInput.setAttribute('required', 'required');
            requirementWrapper.classList.add('hidden');
            requirementInput && requirementInput.removeAttribute('required');
            claimTryoutInput && claimTryoutInput.removeAttribute('required');
        } else if (typePriceSelect.value === 'free_conditional') {
            priceField.classList.add('hidden');
            priceInput.value = 0;
            priceInput.removeAttribute('required');
            requirementWrapper.classList.remove('hidden');
            toggleClaimRequirement();
        } else {
            priceField.classList.add('hidden');
            priceInput.value = 0;
            priceInput.removeAttribute('required');
            requirementWrapper.classList.add('hidden');
            requirementInput && requirementInput.removeAttribute('required');
            claimTryoutInput && claimTryoutInput.removeAttribute('required');
        }
    }

    function toggleClaimRequirement() {
        const isTryoutClaim = claimRequirementType && claimRequirementType.value === 'completed_tryout';
        manualClaimRequirement?.classList.toggle('hidden', isTryoutClaim);
        tryoutClaimRequirement?.classList.toggle('hidden', !isTryoutClaim);
        requirementInput?.toggleAttribute('required', !isTryoutClaim && typePriceSelect.value === 'free_conditional');
        claimTryoutInput?.toggleAttribute('required', isTryoutClaim && typePriceSelect.value === 'free_conditional');
    }

    toggleFields();
    typePriceSelect.addEventListener('change', toggleFields);
    claimRequirementType?.addEventListener('change', toggleClaimRequirement);

    function toggleDurationValue() {
        if (!durationUnit || !durationValue) return;
        durationValue.disabled = durationUnit.value === 'forever';
    }

    toggleDurationValue();
    durationUnit?.addEventListener('change', toggleDurationValue);
});
</script>
@endsection
