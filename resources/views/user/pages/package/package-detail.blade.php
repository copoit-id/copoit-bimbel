@extends('user.layout.user')

@section('title', $package->name)

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex items-center text-sm text-gray-500">
            <a href="{{ route('user.package.my') }}" class="hover:text-primary">Paket Saya</a>
            <i class="ri-arrow-right-s-line mx-2"></i>
            <span class="text-gray-900 truncate max-w-xs">{{ $package->name }}</span>
        </nav>
    </div>

    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $package->name }}</h1>
                @if($package->description)
                <div class="text-gray-600 mt-2 package-description">{!! $package->description !!}</div>
                @endif
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500">Progress</div>
                <div class="text-2xl font-bold text-primary">{{ $materialProgress->count() + $tryoutProgress->count() }} / {{ $package->materials->count() + $package->tryouts->count() }}</div>
            </div>
        </div>
    </div>

    <!-- Learning Path -->
    <div class="space-y-4">
        @php $stepNumber = 1; @endphp
        
        <!-- Materials -->
        @foreach($package->materials as $material)
        @php
        $progress = $materialProgress->get($material->material_id);
        $isCompleted = $progress && $progress->is_completed;
        $isInProgress = $progress && $progress->is_in_progress;
        @endphp
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="flex items-center p-4">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-10 h-10 rounded-full {{ $isCompleted ? 'bg-green-500' : ($isInProgress ? 'bg-yellow-500' : 'bg-gray-200') }} flex items-center justify-center text-white font-bold">
                        @if($isCompleted)
                        <i class="ri-check-line"></i>
                        @else
                        {{ $stepNumber }}
                        @endif
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center">
                        <i class="{{ $material->type_icon }} text-gray-400 mr-2"></i>
                        <h3 class="font-medium text-gray-900">{{ $material->title }}</h3>
                    </div>
                    <p class="text-sm text-gray-500 truncate">{{ $material->description ?: 'Materi Pembelajaran' }}</p>
                    @if($isInProgress)
                    <div class="mt-2 w-32">
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-yellow-500 h-1.5 rounded-full" style="width: {{ $progress->progress_percentage }}%"></div>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="flex-shrink-0 ml-4">
                    <a href="{{ route('user.material.show', $material->material_id) }}" class="px-4 py-2 {{ $isCompleted ? 'bg-green-100 text-green-700' : 'bg-primary text-white' }} rounded-lg hover:opacity-90 text-sm">
                        {{ $isCompleted ? 'Ulangi' : ($isInProgress ? 'Lanjutkan' : 'Mulai') }}
                    </a>
                </div>
            </div>
        </div>
        @php $stepNumber++; @endphp
        @endforeach
        
        <!-- Tryouts -->
        @foreach($package->tryouts as $tryout)
        @php
        $answers = $tryoutProgress->get($tryout->tryout_id);
        $isCompleted = $answers && $answers->where('status', 'completed')->isNotEmpty();
        $isInProgress = $answers && $answers->where('status', 'in_progress')->isNotEmpty();
        @endphp
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="flex items-center p-4">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-10 h-10 rounded-full {{ $isCompleted ? 'bg-green-500' : ($isInProgress ? 'bg-yellow-500' : 'bg-gray-200') }} flex items-center justify-center text-white font-bold">
                        @if($isCompleted)
                        <i class="ri-check-line"></i>
                        @else
                        {{ $stepNumber }}
                        @endif
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center">
                        <i class="ri-file-list-3-line text-gray-400 mr-2"></i>
                        <h3 class="font-medium text-gray-900">{{ $tryout->name }}</h3>
                    </div>
                    <p class="text-sm text-gray-500">Tryout • {{ $tryout->getTotalQuestionsAttribute() }} Soal • {{ $tryout->getTotalDurationAttribute() }} Menit</p>
                    @if($isCompleted && $answers->first()->score)
                    <div class="mt-1 text-sm">
                        <span class="text-gray-500">Skor: </span>
                        <span class="font-medium text-primary">{{ $answers->first()->score }}</span>
                    </div>
                    @endif
                </div>
                <div class="flex-shrink-0 ml-4">
                    @if($isCompleted)
                    <a href="{{ route('user.tryout.result', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]) }}" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 text-sm">
                        Lihat Hasil
                    </a>
                    @else
                    <a href="{{ route('user.tryout.lobby', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]) }}" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm">
                        {{ $isInProgress ? 'Lanjutkan' : 'Kerjakan' }}
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @php $stepNumber++; @endphp
        @endforeach
        
        @if($package->materials->isEmpty() && $package->tryouts->isEmpty())
        <div class="text-center py-12 bg-white rounded-lg shadow">
            <i class="ri-folder-open-line text-4xl text-gray-400 mb-2"></i>
            <p class="text-gray-500">Paket ini belum memiliki materi atau tryout.</p>
        </div>
        @endif
    </div>
</div>

@section('styles')
<style>
.package-description p { margin-bottom: 0.75rem; }
.package-description p:last-child { margin-bottom: 0; }
.package-description ul { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 0.75rem; }
.package-description ol { list-style-type: decimal; padding-left: 1.5rem; margin-bottom: 0.75rem; }
.package-description a { color: #10b981; text-decoration: underline; }
.package-description strong { font-weight: 600; }
.package-description em { font-style: italic; }
.package-description h1, .package-description h2, .package-description h3 { font-weight: 700; margin-bottom: 0.5rem; }
.package-description h1 { font-size: 1.5rem; }
.package-description h2 { font-size: 1.25rem; }
.package-description h3 { font-size: 1.125rem; }
</style>
@endsection
@endsection
