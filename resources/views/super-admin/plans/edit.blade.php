@extends('super-admin.layouts.app')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('super-admin.plans.index') }}" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg">
            <i class="ri-arrow-left-line text-xl"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold">Edit Plan</h2>
            <p class="text-gray-500">Ubah detail plan {{ $plan->name }}.</p>
        </div>
    </div>

    <div class="bg-white border border-border rounded-xl p-6">
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('super-admin.plans.update', $plan->id) }}">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Plan</label>
                    <input type="text" name="name" value="{{ old('name', $plan->name) }}" 
                        class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Harga (Rp)</label>
                    <input type="number" name="price" value="{{ old('price', $plan->price) }}" 
                        class="w-full border border-gray-200 rounded-lg px-4 py-2" min="0" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Durasi (Hari)</label>
                    <input type="number" name="duration_days" value="{{ old('duration_days', $plan->duration_days) }}" 
                        class="w-full border border-gray-200 rounded-lg px-4 py-2" min="0" required>
                    <p class="text-xs text-gray-500 mt-1">0 = Lifetime</p>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi</label>
                <textarea name="description" rows="2" 
                    class="w-full border border-gray-200 rounded-lg px-4 py-2">{{ old('description', $plan->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Max Package</label>
                    <input type="number" name="max_packages" 
                        value="{{ old('max_packages', $plan->max_packages) }}" 
                        class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
                    <p class="text-xs text-gray-500 mt-1">-1 = Unlimited, 0 = Disable</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Max Users</label>
                    <input type="number" name="max_users" 
                        value="{{ old('max_users', $plan->max_users) }}" 
                        class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
                    <p class="text-xs text-gray-500 mt-1">-1 = Unlimited, 0 = Disable</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Max Bank Soal</label>
                    <input type="number" name="max_question_banks" 
                        value="{{ old('max_question_banks', $plan->max_question_banks) }}" 
                        class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
                    <p class="text-xs text-gray-500 mt-1">-1 = Unlimited, 0 = Disable</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Essay AI</label>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="essay_ai_enabled" value="1" 
                                {{ old('essay_ai_enabled', $plan->essay_ai_enabled) ? 'checked' : '' }}
                                class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Enable</span>
                        </label>
                        <input type="number" name="essay_ai_monthly_limit" 
                            value="{{ old('essay_ai_monthly_limit', $plan->essay_ai_monthly_limit) }}" 
                            class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm w-32"
                            placeholder="Limit/bulan">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">0 = Unlimited</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pengaturan</label>
                    <div class="flex flex-wrap gap-4 items-center">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_trial" value="1" id="is_trial"
                                {{ old('is_trial', $plan->is_trial) ? 'checked' : '' }}
                                class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Trial</span>
                        </label>
                        {{-- Trial Duration - muncul saat trial dicentang --}}
                        @php
                            $isTrialChecked = (bool) old('is_trial', $plan->is_trial);
                        @endphp
                        <div id="trial_duration_wrapper" class="{{ $isTrialChecked ? '' : 'hidden' }}">
                            <input type="number" name="trial_duration_days" 
                                value="{{ old('trial_duration_days', $plan->trial_duration_days ?? 14) }}" 
                                class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm w-24"
                                placeholder="Hari" min="1" {{ $isTrialChecked ? '' : 'disabled' }}>
                            <span class="text-xs text-gray-500 ml-1">hari</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-4 mt-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_default" value="1" 
                                {{ old('is_default', $plan->is_default) ? 'checked' : '' }}
                                class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Default</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" 
                                {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
                                class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Aktif</span>
                        </label>
                    </div>
                </div>
            </div>

            @php
                $savedPlanFeatures = $plan->features_json['plan_features'] ?? [];
                $planFeatures = old('plan_features', array_merge([
                    'affiliate_enabled' => false,
                ], $savedPlanFeatures));
            @endphp
            <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="mb-4">
                    <h3 class="font-semibold text-gray-900">Fitur Plan</h3>
                    <p class="text-sm text-gray-500">Checklist ini menentukan menu/fitur yang tersedia untuk admin dan user.</p>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-4">
                        <input type="hidden" name="plan_features[affiliate_enabled]" value="0">
                        <input type="checkbox" name="plan_features[affiliate_enabled]" value="1"
                            {{ !empty($planFeatures['affiliate_enabled']) ? 'checked' : '' }}
                            class="mt-1 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                        <span>
                            <span class="block text-sm font-semibold text-gray-800">Affiliate</span>
                            <span class="mt-1 block text-xs leading-relaxed text-gray-500">Tampilkan menu Affiliate dan aktifkan preview diskon referral.</span>
                        </span>
                    </label>
                </div>
            </div>

            @php
                $savedProctoringDefaults = $plan->features_json['proctoring_defaults'] ?? [];
                $proctoringDefaults = old('proctoring_defaults', array_merge([
                    'enable_anti_copy' => true,
                    'enable_tab_switch_detection' => true,
                    'enable_webcam_check' => false,
                    'enable_screen_check' => false,
                ], $savedProctoringDefaults));
                $proctoringOptions = [
                    'enable_anti_copy' => [
                        'label' => 'Anti Copy Soal',
                        'description' => 'Default aktif untuk blok copy/cut/klik kanan di halaman ujian.',
                    ],
                    'enable_tab_switch_detection' => [
                        'label' => 'Deteksi Pindah Tab',
                        'description' => 'Default aktif untuk alert dan hitung pelanggaran pindah tab.',
                    ],
                    'enable_webcam_check' => [
                        'label' => 'Webcam Check',
                        'description' => 'Default mati. Jika aktif, admin tryout akan otomatis mencentang kamera.',
                    ],
                    'enable_screen_check' => [
                        'label' => 'Screen Check',
                        'description' => 'Default mati. Jika aktif, admin tryout akan otomatis mencentang screen sharing.',
                    ],
                ];
            @endphp
            <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="mb-4">
                    <h3 class="font-semibold text-gray-900">Fitur Keamanan Ujian</h3>
                    <p class="text-sm text-gray-500">Checklist ini menentukan fitur yang tersedia di form tryout admin.</p>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    @foreach($proctoringOptions as $field => $option)
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-4">
                            <input type="hidden" name="proctoring_defaults[{{ $field }}]" value="0">
                            <input type="checkbox" name="proctoring_defaults[{{ $field }}]" value="1"
                                {{ !empty($proctoringDefaults[$field]) ? 'checked' : '' }}
                                class="mt-1 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">{{ $option['label'] }}</span>
                                <span class="mt-1 block text-xs leading-relaxed text-gray-500">{{ $option['description'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('super-admin.plans.index') }}" 
                    class="px-5 py-2 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50">Batal</a>
                <button type="submit" class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const isTrialCheckbox = document.getElementById('is_trial');
        const trialDurationWrapper = document.getElementById('trial_duration_wrapper');
        const trialDurationInput = trialDurationWrapper ? trialDurationWrapper.querySelector('input[name="trial_duration_days"]') : null;
        
        if (isTrialCheckbox && trialDurationWrapper) {
            function syncTrialDuration() {
                if (isTrialCheckbox.checked) {
                    trialDurationWrapper.classList.remove('hidden');
                    if (trialDurationInput) trialDurationInput.disabled = false;
                } else {
                    trialDurationWrapper.classList.add('hidden');
                    if (trialDurationInput) trialDurationInput.disabled = true;
                }
            }

            isTrialCheckbox.addEventListener('change', syncTrialDuration);
            syncTrialDuration();
        }
    });
</script>
@endsection
