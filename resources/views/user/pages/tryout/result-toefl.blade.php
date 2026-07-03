@extends('user.layout.tryout')
@section('title', __('Hasil Tes'))
@section('content')

<div class="flex flex-col gap-6 bg-gray-50 py-8 pt-18">
    <!-- Header Section -->
    <div class="bg-white rounded-lg p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $tryout->name }}</h1>
                <p class="text-gray-600">
                    @if($tryout->is_toefl == 1)
                    {{ __('Hasil Tes TOEFL ITP') }}
                    @else
                    {{ __('Hasil Tes Sertifikasi') }}
                    @endif
                </p>
            </div>
            <div class="text-center">
                <div class="bg-primary/10 rounded-lg p-4">
                    <p class="text-sm text-primary font-medium">
                        @if($tryout->is_toefl == 1)
                        {{ __('Skor TOEFL ITP') }}
                        @else
                        {{ __('Skor Total') }}
                        @endif
                    </p>
                    <p class="text-4xl font-bold text-primary">{{ $toeflResults['total_score'] }}</p>
                    @if($tryout->is_toefl == 1 && isset($toeflResults['score_interpretation']))
                    <p class="text-xs text-gray-600">{{ $toeflResults['score_interpretation']['level'] }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Score Interpretation for TOEFL only -->
        @if($tryout->is_toefl == 1 && isset($toeflResults['score_interpretation']))
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-blue-800 mb-2">{{ __('Interpretasi Skor') }}</h3>
            <p class="text-blue-700">{{ $toeflResults['score_interpretation']['description'] }}</p>
        </div>
        @endif
    </div>

    <!-- Section Scores -->
    <div class="bg-white rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Skor Per Bagian') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-{{ count($sectionResults) + 1 }} gap-4">
            @foreach($sectionResults as $result)
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <h4 class="font-medium text-gray-700 mb-2">{{ $result['name'] }}</h4>
                <p class="text-sm text-gray-600">{{ __('Skor Mentah: :score', ['score' => $result['raw_score']]) }}</p>
                <p class="text-2xl font-bold text-primary">{{ $result['scaled_score'] }}</p>
                <p class="text-xs text-gray-500">
                    @if($tryout->is_toefl == 1)
                    {{ __('Skor Skala') }}
                    @else
                    {{ __('Skor Bagian') }}
                    @endif
                </p>
            </div>
            @endforeach
            <div class="text-center p-4 bg-primary/10 rounded-lg">
                <h4 class="font-medium text-primary mb-2">{{ __('Skor Total') }}</h4>
                <p class="text-3xl font-bold text-primary">{{ $toeflResults['total_score'] }}</p>
                <p class="text-xs text-gray-600">
                    @if($tryout->is_toefl == 1)
                    {{ __('Skor Akhir TOEFL') }}
                    @else
                    {{ __('Skor Akhir') }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Detailed Section Results -->
    <div class="grid grid-cols-1 md:grid-cols-{{ count($sectionResults) }} gap-6">
        @foreach($sectionResults as $result)
        <div class="bg-white rounded-lg p-6 shadow-sm">
            <h3 class="font-semibold text-gray-900 mb-4">{{ $result['name'] }}</h3>

            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">{{ __('Skor Mentah:') }}</span>
                    <span class="font-medium">{{ $result['raw_score'] }}/{{ $result['total_questions'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">
                        @if($tryout->is_toefl == 1)
                        {{ __('Skor Skala:') }}
                        @else
                        {{ __('Skor Bagian:') }}
                        @endif
                    </span>
                    <span class="font-medium text-primary">{{ $result['scaled_score'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">{{ __('Benar:') }}</span>
                    <span class="font-medium text-green-600">{{ $result['correct_answers'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">{{ __('Salah:') }}</span>
                    <span class="font-medium text-red-600">{{ $result['wrong_answers'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">{{ __('Belum Dijawab:') }}</span>
                    <span class="font-medium text-gray-500">{{ $result['unanswered'] }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Score Scale (only for TOEFL) -->
    @if($tryout->is_toefl == 1)
    <div class="bg-white rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Skala Skor TOEFL ITP') }}</h3>
        <div class="space-y-2">
            <div
                class="flex justify-between items-center p-2 rounded {{ $toeflResults['total_score'] >= 677 ? 'bg-green-100' : '' }}">
                <span>{{ __('677 - Excellent') }}</span>
                <span class="text-sm text-gray-600">{{ __('Kemampuan bahasa Inggris sangat tinggi') }}</span>
            </div>
            <div
                class="flex justify-between items-center p-2 rounded {{ $toeflResults['total_score'] >= 600 && $toeflResults['total_score'] < 677 ? 'bg-green-100' : '' }}">
                <span>{{ __('600-676 - Very Good') }}</span>
                <span class="text-sm text-gray-600">{{ __('Kemampuan bahasa Inggris sangat baik') }}</span>
            </div>
            <div
                class="flex justify-between items-center p-2 rounded {{ $toeflResults['total_score'] >= 550 && $toeflResults['total_score'] < 600 ? 'bg-blue-100' : '' }}">
                <span>{{ __('550-599 - Good') }}</span>
                <span class="text-sm text-gray-600">{{ __('Kemampuan bahasa Inggris baik') }}</span>
            </div>
            <div
                class="flex justify-between items-center p-2 rounded {{ $toeflResults['total_score'] >= 500 && $toeflResults['total_score'] < 550 ? 'bg-yellow-100' : '' }}">
                <span>{{ __('500-549 - Fair') }}</span>
                <span class="text-sm text-gray-600">{{ __('Kemampuan bahasa Inggris cukup') }}</span>
            </div>
            <div
                class="flex justify-between items-center p-2 rounded {{ $toeflResults['total_score'] >= 450 && $toeflResults['total_score'] < 500 ? 'bg-orange-100' : '' }}">
                <span>{{ __('450-499 - Limited') }}</span>
                <span class="text-sm text-gray-600">{{ __('Kemampuan bahasa Inggris terbatas') }}</span>
            </div>
            <div
                class="flex justify-between items-center p-2 rounded {{ $toeflResults['total_score'] < 450 ? 'bg-red-100' : '' }}">
                <span>{{ __('217-449 - Weak') }}</span>
                <span class="text-sm text-gray-600">{{ __('Kemampuan bahasa Inggris lemah') }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex gap-4 justify-center">
        <a href="{{ $package ? route('user.package.tryout', $package->package_id) : route('user.package.index') }}"
            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
            <i class="ri-arrow-left-line mr-2"></i>{{ $package ? __('Kembali') : __('Kembali ke Tryout') }}
        </a>
        @if(($clientBranding['certificate_management_enabled'] ?? true) && $tryout->is_certification)
        <a href="{{ route('user.certificate.preview', ['package_id' => request()->route('id_package'), 'tryout_id' => $tryout->tryout_id, 'token' => $latestAttemptToken]) }}"
            class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
            <i class="ri-award-line"></i>
            {{ __('Lihat Sertifikat') }}
        </a>
        @endif

        @if($tryout->is_toefl == 1)
        <a href="{{ route('user.package.tryout.ranking', ['id_package' => request()->route('id_package'), 'id_tryout' => $tryout->tryout_id]) }}"
            class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
            <i class="ri-trophy-line"></i>
            {{ __('Lihat Ranking') }}
        </a>
        @endif
    </div>
</div>

@endsection
