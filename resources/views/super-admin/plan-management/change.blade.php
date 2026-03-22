@extends('super-admin.layouts.app')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('super-admin.plan-management.index') }}" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg">
            <i class="ri-arrow-left-line text-xl"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold">Ubah Plan</h2>
            <p class="text-gray-500">Pilih plan untuk project ini.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Current Plan Info --}}
    @if($currentSubscription)
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-700">
                Plan saat ini: <strong>{{ $currentSubscription->plan->name }}</strong> ({{ $currentSubscription->status }})
            </p>
        </div>
    @endif

    {{-- Change Form --}}
    <div class="bg-white border border-border rounded-xl p-6">
        <form method="POST" action="{{ route('super-admin.plan-management.assign') }}" class="space-y-6">
            @csrf

            {{-- Plan Selection --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-3">Pilih Plan</label>
                <div class="space-y-3">
                    @foreach($plans as $plan)
                        <label class="flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-primary transition {{ old('plan_id', $currentSubscription?->plan_id) == $plan->id ? 'border-primary bg-gray-50' : '' }}">
                            <input type="radio" name="plan_id" value="{{ $plan->id }}" 
                                {{ old('plan_id', $currentSubscription?->plan_id) == $plan->id ? 'checked' : '' }}
                                class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <div class="ml-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-900">{{ $plan->name }}</span>
                                    <span class="text-gray-900 font-medium">{{ $plan->formatted_price }}</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $plan->max_packages_text }} Package • 
                                    {{ $plan->max_users_text }} Users • 
                                    {{ $plan->max_question_banks_text }} Bank Soal • 
                                    Essay AI: {{ $plan->essay_ai_limit_text }}
                                </p>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Status & Dates --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full border border-gray-200 rounded-lg px-4 py-2">
                        <option value="active" {{ old('status', $currentSubscription?->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="trial" {{ old('status', $currentSubscription?->status) == 'trial' ? 'selected' : '' }}>Trial</option>
                        <option value="suspended" {{ old('status', $currentSubscription?->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mulai Berlaku</label>
                    <input type="datetime-local" name="starts_at" 
                        value="{{ old('starts_at', $currentSubscription?->starts_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}"
                        class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Berakhir (Opsional)</label>
                    <input type="datetime-local" name="expires_at" 
                        value="{{ old('expires_at', $currentSubscription?->expires_at?->format('Y-m-d\TH:i')) }}"
                        class="w-full border border-gray-200 rounded-lg px-4 py-2">
                    <p class="text-xs text-gray-500 mt-1">Kosongkan untuk lifetime</p>
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan</label>
                <textarea name="notes" rows="2" 
                    class="w-full border border-gray-200 rounded-lg px-4 py-2">{{ old('notes', $currentSubscription?->notes) }}</textarea>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('super-admin.plan-management.index') }}" 
                    class="px-5 py-2 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50">Batal</a>
                <button type="submit" class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Simpan Plan</button>
            </div>
        </form>
    </div>
</div>
@endsection
