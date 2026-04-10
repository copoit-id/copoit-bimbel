@extends('admin.layout.admin')
@section('title', 'Manajemen Soal')
@section('content')

@php
    $isKecermatan = $tryout->assessment_type === 'kecermatan';
    $tryoutDetailId = $tryout->tryoutDetails->first()->tryout_detail_id ?? null;
@endphp

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            @if($package->package_id === 'standalone')
            <x-breadcrumb-item href="{{ route('admin.tryout.index') }}" title="Manajemen Tryout" />
            <x-breadcrumb-item href="" title="Soal" />
            @else
            <x-breadcrumb-item href="{{ route('admin.package.index') }}" title="Manajemen Paket" />
            <x-breadcrumb-item href="" title="Soal" />
            @endif
        </x-slot>
    </x-breadcrumb>
    
    @if($isKecermatan)
        {{-- Tombol untuk Kecermatan --}}
        <x-btn title="Tambah Kolom Kecermatan"
            route="{{ route('admin.kecermatan.create', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id]) }}"
            icon="ri-add-fill">
        </x-btn>
    @else
        {{-- Tombol normal untuk tryout biasa --}}
        <x-btn title="Tambah Soal"
            route="{{ route('admin.package.tryout.soal.create', ['package_id' => $package->package_id, 'tryout_detail_id' => $tryoutDetailId]) }}"
            icon="ri-add-fill">
        </x-btn>
    @endif
</div>
<div class="package-bimbel bg-white p-8 rounded-lg border border-border flex justify-center text-center">
    <x-page-desc title="Manajemen Soal - {{ $tryout->name }}">
    </x-page-desc>
</div>

{{-- Tampilan Khusus Kecermatan --}}
@if($isKecermatan)
<div class="mt-4">
    @if($tryout->kecermatanColumns && $tryout->kecermatanColumns->count() > 0)
        <div class="grid grid-cols-1 gap-4">
            @foreach($tryout->kecermatanColumns as $column)
            <div class="bg-white border border-border rounded-xl p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $column->nama_kolom }}</h3>
                        <div class="flex gap-2 mt-2">
                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $column->jumlah_soal }} Soal
                            </span>
                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                {{ $column->durasi_kolom }} Menit
                            </span>
                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 capitalize">
                                {{ $column->tipe_kolom }}
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.kecermatan.preview', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id, 'column_id' => $column->column_id]) }}"
                            class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-200 flex items-center gap-1 text-sm">
                            <i class="ri-eye-line"></i> Preview
                        </a>
                        <a href="{{ route('admin.kecermatan.edit', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id, 'column_id' => $column->column_id]) }}"
                            class="bg-primary/10 text-primary px-3 py-2 rounded-lg hover:bg-primary/20 flex items-center gap-1 text-sm">
                            <i class="ri-pencil-line"></i> Edit
                        </a>
                        <form action="{{ route('admin.kecermatan.destroy', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id, 'column_id' => $column->column_id]) }}" 
                            method="POST" class="inline" 
                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus kolom ini? Semua soal terkait akan ikut terhapus.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-100 text-red-700 px-3 py-2 rounded-lg hover:bg-red-200 flex items-center gap-1 text-sm">
                                <i class="ri-delete-bin-line"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Preview Kolom --}}
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <p class="text-sm text-gray-600 mb-2">Isi Kolom:</p>
                    <div class="flex gap-4 justify-center">
                        @foreach($column->kolom_data as $item)
                            <div class="w-12 h-12 bg-white border-2 border-gray-300 rounded-lg flex items-center justify-center text-xl font-bold text-gray-800">
                                {{ $item }}
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Baris Soal --}}
                <div class="space-y-2">
                    <p class="text-sm text-gray-600">Baris Soal ({{ $column->rows->count() }} baris):</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2">
                        @foreach($column->rows as $row)
                            <div class="bg-gray-50 px-3 py-2 rounded text-sm truncate" title="Baris {{ $row->row_number }}">
                                <span class="font-medium">Baris {{ $row->row_number }}:</span> 
                                {!! strip_tags($row->row_text) !!}
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Info Soal --}}
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-600">
                        <i class="ri-information-line mr-1"></i>
                        Total {{ $column->jumlah_soal * $column->rows->count() }} soal telah digenerate otomatis ({{ $column->jumlah_soal }} soal × {{ $column->rows->count() }} baris)
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-8 text-center">
            <i class="ri-questionnaire-line text-5xl text-blue-400 mb-4"></i>
            <h3 class="text-lg font-semibold text-blue-900 mb-2">Belum Ada Kolom Kecermatan</h3>
            <p class="text-blue-700 mb-4">Tambahkan kolom kecermatan untuk mulai membuat soal.</p>
            <a href="{{ route('admin.kecermatan.create', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id]) }}"
                class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">
                <i class="ri-add-line"></i> Tambah Kolom Kecermatan
            </a>
        </div>
    @endif
</div>
@else
{{-- Tampilan Normal untuk Tryout Biasa --}}
<div class="mt-4 space-y-4">
    @foreach ($questions as $question)
    <div class="bg-white border border-border rounded-xl p-6 flex flex-col">
        <div class="flex items-center gap-2 mb-2">
            <span
                class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-[#0B2B9A]/10 text-[#0B2B9A] border border-[#0B2B9A]/10">
                {{ ($question->question_type ?? 'multiple_choice') === 'multiple_answer' ? 'Multiple Answer' : 'Pilihan Ganda' }}
            </span>
            @php
                $maxWeight = optional($question->questionOptions)->max(function($opt){
                    return is_null($opt->weight) ? 0 : (float)$opt->weight;
                });
                $displayWeight = ($maxWeight && $maxWeight > 0) ? $maxWeight : (float)($question->default_weight ?? 0);
                $metadata = is_array($question->metadata) ? $question->metadata : [];
                $multipleAnswerMeta = is_array($metadata['multiple_answer'] ?? null) ? $metadata['multiple_answer'] : [];
                $multipleAnswerScoringMode = in_array(($multipleAnswerMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
                    ? $multipleAnswerMeta['scoring_mode']
                    : 'fullscore';
                $multipleAnswerTotalScore = (float) ($multipleAnswerMeta['score_correct'] ?? $displayWeight);
                $multipleAnswerCorrectCount = max(1, $question->questionOptions->where('is_correct', true)->count());
                $multipleAnswerPerCorrectScore = $multipleAnswerCorrectCount > 0
                    ? ($multipleAnswerTotalScore / $multipleAnswerCorrectCount)
                    : $multipleAnswerTotalScore;
            @endphp
            @if(($question->question_type ?? '') === 'multiple_answer')
                @if($multipleAnswerScoringMode === 'partial')
                <span
                    class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                    Per jawaban benar: {{ rtrim(rtrim(number_format($multipleAnswerPerCorrectScore, 2, '.', ''), '0'), '.') }}
                    (dari {{ rtrim(rtrim(number_format($multipleAnswerTotalScore, 2, '.', ''), '0'), '.') }})
                </span>
                @else
                <span
                    class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                    Benar semua : {{ rtrim(rtrim(number_format($multipleAnswerTotalScore, 2, '.', ''), '0'), '.') }}
                </span>
                @endif
            @else
                <span
                    class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                    {{ (float) $displayWeight }} poin
                </span>
            @endif
        </div>

        <div class="question-rich-text font-semibold text-gray-900 leading-relaxed mb-2">
            {!! $question->question_text !!}
        </div>

        <div class="mb-2">
            <div class="font-semibold text-gray-700 mb-1">Opsi Jawaban:</div>
            <ul class="space-y-2 text-gray-600">
                @foreach ($question->questionOptions as $option)
                <li class="flex items-center gap-2  {{$option->is_correct == 1 ? 'text-green' : ''}}">
                    <i
                        class="{{$option->is_correct == 1 ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line'}}"></i>
                    <span>{!! $option->option_text !!}</span>
                </li>
                @endforeach
            </ul>
        </div>

        @if ($question && $question->explanation)
        <div class="bg-blue-50 border border-primary border-dashed rounded-lg p-4 mt-2">
            <div class="font-semibold text-primary mb-1">Pembahasan</div>
            <div class="text-primary">{!! $question->explanation !!}</div>
        </div>
        @endif

        <div class="flex gap-3 mt-6">
            <x-btn title="Edit Soal"
                route="{{ route('admin.package.tryout.soal.edit', ['package_id' => $package->package_id, 'tryout_detail_id' => $tryout->tryoutDetails->first()->tryout_detail_id, 'question_id' => $question->question_id]) }}"
                icon="ri-pencil-fill">
            </x-btn>
            <x-btn color='red' title="Hapus Soal" route="" icon="ri-delete-bin-5-fill">
            </x-btn>
        </div>
    </div>
    @endforeach
</div>
@endif {{-- End isKecermatan --}}
@endsection
@section('scripts')
<script>
    console.log('Dashboard scripts loaded');
</script>
@endsection
@section('styles')
