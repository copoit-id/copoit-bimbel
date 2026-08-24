@extends('user.layout.user')

@section('title', 'Daftar Tryout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Tryout Tersedia</h1>
        <p class="text-gray-600 mt-1">Akses semua tryout dari paket yang Anda miliki</p>
    </div>

    <!-- Tryouts Grid -->
    @if($tryouts->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($tryouts as $tryout)
        @php
        $userAnswer = $tryout->userAnswers->first();
        $totalQuestions = $tryout->getTotalQuestionsAttribute();
        $totalDuration = $tryout->getTotalDurationAttribute();
        @endphp
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center">
                        <i class="ri-file-list-3-line text-2xl text-primary"></i>
                    </div>
                    @if($userAnswer)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $userAnswer->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ $userAnswer->status === 'completed' ? 'Selesai' : 'Sedang dikerjakan' }}
                    </span>
                    @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Belum dikerjakan</span>
                    @endif
                </div>
                
                <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">{{ $tryout->name }}</h3>
                
                <div class="space-y-2 text-sm text-gray-500 mb-4">
                    <div class="flex items-center">
                        <i class="ri-question-line mr-2"></i>
                        <span>{{ $totalQuestions }} Soal</span>
                    </div>
                    <div class="flex items-center">
                        <i class="ri-time-line mr-2"></i>
                        <span>{{ $totalDuration }} Menit</span>
                    </div>
                    @if($tryout->start_date && $tryout->end_date)
                    <div class="flex items-center">
                        <i class="ri-calendar-line mr-2"></i>
                        <span>{{ $tryout->start_date->format('d M') }} - {{ $tryout->end_date->format('d M Y') }}</span>
                    </div>
                    @endif
                </div>
                
                @if($userAnswer && $userAnswer->status === 'completed')
                <div class="flex items-center justify-between pt-4 border-t">
                    <div>
                        <p class="text-xs text-gray-500">Skor</p>
                        <p class="text-lg font-bold text-primary">{{ $userAnswer->score ?? '-' }}</p>
                    </div>
                    <a href="{{ route('user.tryout.result', ['id_package' => $tryout->packages->first()?->package_id ?? 'free', 'id_tryout' => $tryout->tryout_id]) }}" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm">
                        Lihat Hasil
                    </a>
                </div>
                @else
                <a href="{{ route('user.tryout.lobby', ['id_package' => $tryout->packages->first()?->package_id ?? 'free', 'id_tryout' => $tryout->tryout_id]) }}" class="block w-full py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-center">
                    {{ $userAnswer ? 'Lanjutkan' : 'Kerjakan' }}
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-16 bg-white rounded-lg shadow">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-4">
            <i class="ri-file-list-3-line text-4xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">Tidak ada tryout</h3>
        <p class="text-gray-500 mb-4">Anda belum memiliki akses ke tryout apapun.</p>
        <a href="{{ route('user.package.index') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
            <i class="ri-store-3-line mr-2"></i>Lihat Paket
        </a>
    </div>
    @endif
</div>
@endsection
