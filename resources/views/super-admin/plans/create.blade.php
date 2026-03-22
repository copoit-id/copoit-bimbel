@extends('super-admin.layouts.app')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('super-admin.plans.index') }}" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg">
            <i class="ri-arrow-left-line text-xl"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold">Tambah Plan Baru</h2>
            <p class="text-gray-500">Buat template plan baru.</p>
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

        <form method="POST" action="{{ route('super-admin.plans.store') }}">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Plan</label>
                    <input type="text" name="name" value="{{ old('name') }}" 
                        class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Harga (Rp)</label>
                    <input type="number" name="price" value="{{ old('price', 0) }}" 
                        class="w-full border border-gray-200 rounded-lg px-4 py-2" min="0" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Durasi (Hari)</label>
                    <input type="number" name="duration_days" value="{{ old('duration_days', 30) }}" 
                        class="w-full border border-gray-200 rounded-lg px-4 py-2" min="0" required>
                    <p class="text-xs text-gray-500 mt-1">0 = Lifetime</p>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi</label>
                <textarea name="description" rows="2" 
                    class="w-full border border-gray-200 rounded-lg px-4 py-2">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Max Package</label>
                    <input type="number" name="max_packages" value="{{ old('max_packages', 10) }}" 
                        class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
                    <p class="text-xs text-gray-500 mt-1">-1 = Unlimited, 0 = Disable</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Max Users</label>
                    <input type="number" name="max_users" value="{{ old('max_users', 100) }}" 
                        class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
                    <p class="text-xs text-gray-500 mt-1">-1 = Unlimited, 0 = Disable</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Max Bank Soal</label>
                    <input type="number" name="max_question_banks" value="{{ old('max_question_banks', 10) }}" 
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
                                {{ old('essay_ai_enabled') ? 'checked' : '' }}
                                class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Enable</span>
                        </label>
                        <input type="number" name="essay_ai_monthly_limit" 
                            value="{{ old('essay_ai_monthly_limit', 1000) }}" 
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
                                {{ old('is_trial') ? 'checked' : '' }}
                                class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Trial</span>
                        </label>
                        {{-- Trial Duration - muncul saat trial dicentang --}}
                        <div id="trial_duration_wrapper" class="{{ old('is_trial') ? '' : 'hidden' }}">
                            <input type="number" name="trial_duration_days" 
                                value="{{ old('trial_duration_days', 14) }}" 
                                class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm w-24"
                                placeholder="Hari" min="1">
                            <span class="text-xs text-gray-500 ml-1">hari</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-4 mt-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_default" value="1" 
                                {{ old('is_default') ? 'checked' : '' }}
                                class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Default</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('super-admin.plans.index') }}" 
                    class="px-5 py-2 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50">Batal</a>
                <button type="submit" class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Simpan Plan</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const isTrialCheckbox = document.getElementById('is_trial');
        const trialDurationWrapper = document.getElementById('trial_duration_wrapper');
        
        if (isTrialCheckbox && trialDurationWrapper) {
            isTrialCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    trialDurationWrapper.classList.remove('hidden');
                } else {
                    trialDurationWrapper.classList.add('hidden');
                }
            });
        }
    });
</script>
@endsection
