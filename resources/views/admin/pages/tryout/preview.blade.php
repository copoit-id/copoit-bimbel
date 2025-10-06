@extends('admin.layout.admin')
@section('title', 'Preview Tryout')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.tryout.index') }}" title="Manajemen Tryout" />
            <x-breadcrumb-item href="" title="Preview Tryout" />
        </x-slot>
    </x-breadcrumb>

    <a href="{{ route('admin.tryout.index') }}"
        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
        <i class="ri-arrow-left-line"></i>
        Kembali
    </a>
</div>

<x-page-desc title="Preview Tryout - {{ $tryout->name }}">
    <x-slot name="description">
        {{ ucfirst($tryout->type_tryout) }} • {{ $tryout->tryoutDetails->count() }} Subtest •
        {{ $tryout->tryoutDetails->sum(function($detail) { return $detail->questions->count(); }) }} Total Soal
    </x-slot>
</x-page-desc>

<!-- Tryout Info Card -->
<div class="bg-white p-6 rounded-lg border border-border mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="text-center p-4 border border-primary bg-primary/10 rounded-lg">
            <h3 class="text-2xl font-bold text-primary">{{ $tryout->tryoutDetails->count() }}</h3>
            <p class="text-sm text-primary">Subtest</p>
        </div>
        <div class="text-center p-4 border border-primary bg-primary/10 rounded-lg">
            <h3 class="text-2xl font-bold text-primary">{{ $tryout->tryoutDetails->sum(function($detail) { return
                $detail->questions->count(); }) }}</h3>
            <p class="text-sm text-primary">Total Soal</p>
        </div>
        <div class="text-center p-4 border border-primary bg-primary/10 rounded-lg">
            <h3 class="text-2xl font-bold text-primary">{{ $tryout->tryoutDetails->sum('duration') }}</h3>
            <p class="text-sm text-primary">Total Durasi (menit)</p>
        </div>
        <div class="text-center p-4 border border-primary bg-primary/10 rounded-lg">
            <h3 class="text-2xl font-bold text-primary">
                @if($tryout->is_certification)
                <i class="ri-award-line"></i> Sertifikasi
                @else
                <i class="ri-file-list-line"></i> Reguler
                @endif
            </h3>
            <p class="text-sm text-primary">Jenis Tryout</p>
        </div>
    </div>
</div>

<!-- Subtest Navigation -->
@if($tryout->tryoutDetails->count() > 1)
<div class="bg-white p-4 rounded-lg border border-border mb-6">
    <h3 class="text-lg font-semibold mb-3">Navigasi Subtest</h3>
    <div class="flex flex-wrap gap-2">
        @foreach($tryout->tryoutDetails as $index => $detail)
        <a href="#subtest-{{ $detail->tryout_detail_id }}"
            class="px-4 py-2 bg-primary/10 text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
            {{ strtoupper($detail->type_subtest) }} ({{ $detail->questions->count() }} soal)
        </a>
        @endforeach
    </div>
</div>
@endif

<!-- Questions by Subtest -->
@foreach($tryout->tryoutDetails as $subtestIndex => $detail)
<div id="subtest-{{ $detail->tryout_detail_id }}" class="bg-white rounded-lg border border-border mb-6">
    <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                </h2>
                <p class="text-gray-600">{{ $detail->questions->count() }} soal • {{ $detail->duration }} menit •
                    Passing Score: {{ $detail->passing_score }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.question.index', $detail->tryout_detail_id) }}"
                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2">
                    <i class="ri-edit-line"></i>
                    Kelola Soal
                </a>
            </div>
        </div>
    </div>

    <div class="p-6">
        @if($detail->questions->count() > 0)
        <div class="space-y-6">
            @foreach($detail->questions as $questionIndex => $question)
            <div class="border border-gray-200 rounded-lg p-6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-primary/10 text-primary">
                        Soal {{ $questionIndex + 1 }}
                    </span>
                    @php
                        // Tampilkan bobot tertinggi dari question_options.
                        // Jika tidak ada bobot/opsi, fallback ke default_weight.
                        $maxWeight = optional($question->questionOptions)->max(function($opt){
                            return is_null($opt->weight) ? 0 : (float)$opt->weight;
                        });
                        $displayWeight = ($maxWeight && $maxWeight > 0) ? $maxWeight : (float)($question->default_weight ?? 0);
                    @endphp
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                        {{ (float) $displayWeight }} poin
                    </span>
                    @if($question->sound)
                    <span
                        class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-purple-100 text-purple-800">
                        <i class="ri-volume-up-line mr-1"></i>
                        Audio
                    </span>
                    @endif
                    @if($question->custom_score == 'yes')
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-amber-100 text-amber-800">
                        Custom Score
                    </span>
                    @endif
                </div>

                <div class="mb-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Pertanyaan:</h4>
                    <p class="text-gray-800">{!! $question->question_text !!}</p>
                </div>

                @if($question->sound)
                <div class="mb-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Audio:</h4>
                    <audio controls class="w-full max-w-md">
                        <source src="{{ Storage::url($question->sound) }}" type="audio/mpeg">
                        Browser Anda tidak mendukung audio.
                    </audio>
                </div>
                @endif

                <div class="mb-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Pilihan Jawaban:</h4>
                    <div class="space-y-2">
                        @foreach($question->questionOptions as $optionIndex => $option)
                        @php
                        $optionLabel = chr(65 + $optionIndex); // A, B, C, D, E
                        @endphp
                        <div
                            class="flex items-center gap-3 p-3 rounded-lg {{ $option->is_correct ? 'bg-green-50 border border-green-200' : 'bg-gray-50' }}">
                            <div class="flex-shrink-0">
                                @if($option->is_correct)
                                <i class="ri-checkbox-circle-fill text-green-600 text-xl"></i>
                                @else
                                <i class="ri-checkbox-blank-circle-line text-gray-400 text-xl"></i>
                                @endif
                            </div>
                            <div class="flex-grow">
                                <span
                                    class="font-medium flex items-center gap-1 {{ $option->is_correct ? 'text-green-800' : 'text-gray-700' }}">
                                    {{ $optionLabel }}. {!! $option->option_text !!}
                                </span>
                                @if($question->custom_score == 'yes')
                                <span
                                    class="ml-2 text-sm {{ $option->is_correct ? 'text-green-600' : 'text-gray-500' }}">
                                    ({{ $option->weight }} poin)
                                </span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                @if($question->explanation)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-800 mb-2">Pembahasan:</h4>
                    <p class="text-blue-700">{!! $question->explanation !!}</p>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="ri-question-line text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada soal</h3>
            <p class="text-gray-500 mb-4">Subtest ini belum memiliki soal</p>
            <a href="{{ route('admin.question.index', $detail->tryout_detail_id) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                <i class="ri-add-line"></i>
                Tambah Soal
            </a>
        </div>
        @endif
    </div>
</div>
@endforeach

@if($tryout->tryoutDetails->isEmpty())
<div class="bg-white rounded-lg border border-border p-12 text-center">
    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
        <i class="ri-file-list-line text-2xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada subtest</h3>
    <p class="text-gray-500">Tryout ini belum memiliki subtest</p>
</div>
@endif

@endsection

@section('scripts')
<script>
    // Smooth scrolling for subtest navigation
    document.querySelectorAll('a[href^="#subtest-"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
</script>
@endsection
