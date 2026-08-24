@extends('super-admin.layouts.app')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">Plan & Quota</h2>
            <p class="text-gray-500">Monitoring penggunaan plan untuk project ini.</p>
        </div>
        <a href="{{ route('super-admin.plan-management.change') }}" 
            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
            Ubah Plan
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

    @if($usageStats['has_subscription'])
        {{-- Current Plan Card --}}
        <div class="bg-white border border-border rounded-xl p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h3 class="text-xl font-semibold text-gray-900">{{ $usageStats['plan']['name'] }}</h3>
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded">
                            {{ $usageStats['plan']['is_trial'] ? 'Trial' : 'Aktif' }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">
                        @if($usageStats['plan']['expires_at'])
                            Berakhir: {{ \Carbon\Carbon::parse($usageStats['plan']['expires_at'])->format('d M Y') }}
                        @else
                            Lifetime
                        @endif
                    </p>
                    @if($currentSubscription->notes)
                        <p class="text-xs text-gray-400 mt-1">{{ $currentSubscription->notes }}</p>
                    @endif
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Essay AI Bulan Ini</p>
                        <p class="font-medium">
                            @if($usageStats['essay_ai']['enabled'])
                                {{ $usageStats['essay_ai']['unlimited'] ? 'Unlimited' : $usageStats['essay_ai']['used'] . '/' . $usageStats['essay_ai']['limit'] }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route('super-admin.plan-management.reset-essay') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-xs px-3 py-1.5 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50">
                            Reset
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Usage Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Packages --}}
            <div class="bg-white border border-border rounded-xl p-5">
                <div class="text-sm text-gray-500 mb-2">Package</div>
                <div class="text-2xl font-bold text-gray-800">
                    {{ $usageStats['packages']['unlimited'] ? '∞' : $usageStats['packages']['used'] . '/' . $usageStats['packages']['limit'] }}
                </div>
                @if(!$usageStats['packages']['unlimited'])
                    <div class="mt-3">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" style="width: {{ min($usageStats['packages']['percentage'], 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $usageStats['packages']['percentage'] }}% terpakai</p>
                    </div>
                @else
                    <p class="text-xs text-gray-500 mt-1">Unlimited</p>
                @endif
            </div>

            {{-- Users --}}
            <div class="bg-white border border-border rounded-xl p-5">
                <div class="text-sm text-gray-500 mb-2">Users</div>
                <div class="text-2xl font-bold text-gray-800">
                    {{ $usageStats['users']['unlimited'] ? '∞' : $usageStats['users']['used'] . '/' . $usageStats['users']['limit'] }}
                </div>
                @if(!$usageStats['users']['unlimited'])
                    <div class="mt-3">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" style="width: {{ min($usageStats['users']['percentage'], 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $usageStats['users']['percentage'] }}% terpakai</p>
                    </div>
                @else
                    <p class="text-xs text-gray-500 mt-1">Unlimited</p>
                @endif
            </div>

            {{-- Question Banks --}}
            <div class="bg-white border border-border rounded-xl p-5">
                <div class="text-sm text-gray-500 mb-2">Bank Soal</div>
                <div class="text-2xl font-bold text-gray-800">
                    {{ $usageStats['question_banks']['unlimited'] ? '∞' : $usageStats['question_banks']['used'] . '/' . $usageStats['question_banks']['limit'] }}
                </div>
                @if(!$usageStats['question_banks']['unlimited'])
                    <div class="mt-3">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" style="width: {{ min($usageStats['question_banks']['percentage'], 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $usageStats['question_banks']['percentage'] }}% terpakai</p>
                    </div>
                @else
                    <p class="text-xs text-gray-500 mt-1">Unlimited</p>
                @endif
            </div>

            {{-- Essay AI --}}
            <div class="bg-white border border-border rounded-xl p-5">
                <div class="text-sm text-gray-500 mb-2">Essay AI (Bulan Ini)</div>
                @if($usageStats['essay_ai']['enabled'])
                    <div class="text-2xl font-bold text-gray-800">
                        {{ $usageStats['essay_ai']['unlimited'] ? '∞' : $usageStats['essay_ai']['used'] . '/' . $usageStats['essay_ai']['limit'] }}
                    </div>
                    @if(!$usageStats['essay_ai']['unlimited'])
                        <div class="mt-3">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full" style="width: {{ min($usageStats['essay_ai']['percentage'], 100) }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $usageStats['essay_ai']['percentage'] }}% terpakai
                                @if($usageStats['essay_ai']['reset_at'])
                                    • Reset {{ \Carbon\Carbon::parse($usageStats['essay_ai']['reset_at'])->format('d M') }}
                                @endif
                            </p>
                        </div>
                    @else
                        <p class="text-xs text-gray-500 mt-1">Unlimited</p>
                    @endif
                @else
                    <div class="text-lg font-medium text-gray-400">-</div>
                    <p class="text-xs text-gray-400 mt-1">Tidak tersedia</p>
                @endif
            </div>
        </div>

        {{-- Available Plans --}}
        <div class="bg-white border border-border rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Plan Tersedia</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Plan</th>
                            <th class="px-4 py-3 text-left">Harga</th>
                            <th class="px-4 py-3 text-center">Package</th>
                            <th class="px-4 py-3 text-center">Users</th>
                            <th class="px-4 py-3 text-center">Bank Soal</th>
                            <th class="px-4 py-3 text-center">Essay AI</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($plans as $plan)
                            <tr class="{{ $currentSubscription->plan_id == $plan->id ? 'bg-gray-50' : '' }}">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $plan->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $plan->duration_days == 0 ? 'Lifetime' : $plan->duration_days . ' hari' }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $plan->formatted_price }}</td>
                                <td class="px-4 py-3 text-center">{{ $plan->max_packages_text }}</td>
                                <td class="px-4 py-3 text-center">{{ $plan->max_users_text }}</td>
                                <td class="px-4 py-3 text-center">{{ $plan->max_question_banks_text }}</td>
                                <td class="px-4 py-3 text-center">{{ $plan->essay_ai_limit_text }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($currentSubscription->plan_id == $plan->id)
                                        <span class="px-2 py-1 bg-primary text-white text-xs rounded">Aktif</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- No Subscription --}}
        <div class="bg-white border border-border rounded-xl p-8 text-center">
            <p class="text-gray-500 mb-4">Belum ada plan aktif untuk project ini.</p>
            <a href="{{ route('super-admin.plan-management.change') }}" 
                class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                Setup Plan
            </a>
        </div>
    @endif
</div>
@endsection
