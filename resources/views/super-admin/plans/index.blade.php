@extends('super-admin.layouts.app')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">Manajemen Plan</h2>
            <p class="text-gray-500">Kelola template plan untuk project.</p>
        </div>
        <a href="{{ route('super-admin.plans.create') }}" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
            Tambah Plan
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Plans List --}}
    <div class="bg-white border border-border rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Daftar Plan</h3>
        <div class="space-y-4">
            @forelse($plans as $plan)
                <div class="border border-gray-200 rounded-xl p-5">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-1">
                                <h4 class="text-lg font-semibold text-gray-900">{{ $plan->name }}</h4>
                                @if($plan->is_default)
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded">Default</span>
                                @endif
                                @if($plan->is_trial)
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded">Trial</span>
                                @endif
                                @if(!$plan->is_active)
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded">Nonaktif</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 mb-2">{{ $plan->slug }} • {{ $plan->formatted_price }} • {{ $plan->duration_days == 0 ? 'Lifetime' : $plan->duration_days . ' hari' }}</p>
                            <div class="text-sm text-gray-600 plan-description">{!! $plan->description ?? '-' !!}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('super-admin.plans.edit', $plan->id) }}" 
                                class="px-3 py-1.5 text-xs font-semibold rounded-full border border-primary text-primary hover:bg-primary hover:text-white transition">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('super-admin.plans.destroy', $plan->id) }}" 
                                onsubmit="return confirm('Yakin ingin menghapus plan ini?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                    class="px-3 py-1.5 text-xs font-semibold rounded-full border border-red-400 text-red-500 hover:bg-red-500 hover:text-white transition">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Limits Detail --}}
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Package:</span>
                                <span class="font-medium ml-1">{{ $plan->max_packages_text }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Users:</span>
                                <span class="font-medium ml-1">{{ $plan->max_users_text }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Bank Soal:</span>
                                <span class="font-medium ml-1">{{ $plan->max_question_banks_text }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Essay AI:</span>
                                <span class="font-medium ml-1">{{ $plan->essay_ai_limit_text }}</span>
                            </div>
                        </div>
                        @php
                            $proctoringDefaults = array_merge([
                                'enable_anti_copy' => true,
                                'enable_tab_switch_detection' => true,
                                'enable_webcam_check' => false,
                                'enable_screen_check' => false,
                            ], $plan->features_json['proctoring_defaults'] ?? []);
                            $securityLabels = [
                                'enable_anti_copy' => 'Anti Copy',
                                'enable_tab_switch_detection' => 'Pindah Tab',
                                'enable_webcam_check' => 'Webcam',
                                'enable_screen_check' => 'Screen',
                            ];
                        @endphp
                        <div class="mt-4 flex flex-wrap gap-2 text-xs">
                            @foreach($securityLabels as $field => $label)
                                <span class="rounded-full px-2 py-1 {{ !empty($proctoringDefaults[$field]) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $label }}: {{ !empty($proctoringDefaults[$field]) ? 'Aktif' : 'Mati' }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500">Belum ada plan.</div>
            @endforelse
        </div>
    </div>
</div>

@push('styles')
<style>
.plan-description p { margin-bottom: 0.5rem; }
.plan-description p:last-child { margin-bottom: 0; }
.plan-description ul { list-style-type: disc; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.plan-description ol { list-style-type: decimal; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.plan-description a { color: #10b981; text-decoration: underline; }
.plan-description strong { font-weight: 600; }
.plan-description em { font-style: italic; }
</style>
@endpush
@endsection
