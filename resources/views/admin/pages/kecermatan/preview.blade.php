@extends('admin.layout.admin')

@section('title', 'Preview Soal Kecermatan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Preview Soal Kecermatan</h2>
            <p class="text-gray-500">{{ $column->nama_kolom }} - Tryout: <strong>{{ $tryout->name }}</strong></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.kecermatan.index', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id]) }}"
                class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2">
                <i class="ri-arrow-left-line"></i>
                Kembali
            </a>
        </div>
    </div>

    <!-- Info Panel -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex flex-wrap gap-4 text-sm">
            <span class="text-blue-800">
                <i class="ri-question-line mr-1"></i>
                <strong>{{ $column->jumlah_soal }}</strong> Soal per Baris
            </span>
            <span class="text-blue-800">
                <i class="ri-list-check mr-1"></i>
                <strong>{{ $column->rows->count() }}</strong> Baris
            </span>
            <span class="text-blue-800">
                <i class="ri-calculator-line mr-1"></i>
                Total: <strong>{{ $column->jumlah_soal * $column->rows->count() }}</strong> Soal
            </span>
            <span class="text-blue-800">
                <i class="ri-time-line mr-1"></i>
                Durasi: <strong>{{ $column->durasi_kolom }}</strong> Menit
            </span>
        </div>
    </div>

    <!-- Preview Kolom -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="bg-primary text-white px-6 py-3">
            <h3 class="text-lg font-semibold text-center">Kolom Acuan</h3>
        </div>
        <div class="p-6">
            <p class="text-center text-gray-600 mb-4">
                <i class="ri-information-line mr-1"></i>
                Peserta harus mencari huruf/angka/simbol yang <strong>TIDAK ADA</strong> di kolom berikut:
            </p>
            <div class="flex gap-4 justify-center flex-wrap">
                @foreach($column->kolom_data as $item)
                    <div class="w-16 h-16 bg-primary/10 border-2 border-primary rounded-lg flex items-center justify-center text-3xl font-bold text-primary shadow-sm">
                        {{ $item }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Preview Soal per Baris -->
    @foreach($column->rows as $row)
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="bg-gray-100 px-6 py-3 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Baris {{ $row->row_number }}</h3>
            <div class="text-gray-600 text-sm mt-1">{!! $row->row_text !!}</div>
        </div>
        
        <div class="p-6">
            @php
                $questions = $row->questions()->orderBy('question_number')->take(5)->get();
                $remainingCount = $row->questions()->count() - 5;
            @endphp
            
            <div class="space-y-4">
                @foreach($questions as $question)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 bg-primary text-white rounded-full text-sm font-bold">
                            {{ $question->question_number }}
                        </span>
                        <span class="text-sm text-gray-500">Pilihlah yang tidak ada di kolom:</span>
                    </div>
                    
                    <div class="grid grid-cols-4 gap-3">
                        @php
                            $options = [
                                'A' => $question->option_a,
                                'B' => $question->option_b,
                                'C' => $question->option_c,
                                'D' => $question->option_d,
                            ];
                        @endphp
                        
                        @foreach($options as $key => $value)
                            <div class="text-center p-3 rounded-lg border {{ $question->correct_answer == $key ? 'border-green-500 bg-green-50' : 'border-gray-200 bg-gray-50' }}">
                                <span class="font-bold {{ $question->correct_answer == $key ? 'text-green-700' : 'text-gray-700' }}">{{ $key }}.</span>
                                <span class="text-xl ml-1 {{ $question->correct_answer == $key ? 'text-green-700 font-bold' : 'text-gray-800' }}">{{ $value }}</span>
                                @if($question->correct_answer == $key)
                                    <div class="text-xs text-green-600 mt-1">
                                        <i class="ri-check-line"></i> Jawaban Benar
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            @if($remainingCount > 0)
            <div class="mt-4 text-center">
                <span class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm">
                    <i class="ri-more-line mr-1"></i>
                    dan {{ $remainingCount }} soal lainnya...
                </span>
            </div>
            @endif
        </div>
    </div>
    @endforeach

    <!-- Action Buttons -->
    <div class="flex items-center justify-between bg-white rounded-lg border border-gray-200 p-4">
        <div class="text-sm text-gray-600">
            <i class="ri-information-line mr-1"></i>
            Preview menampilkan maksimal 5 soal per baris. Total ada {{ $column->jumlah_soal }} soal per baris.
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.kecermatan.edit', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id, 'column_id' => $column->column_id]) }}"
                class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 flex items-center gap-2">
                <i class="ri-pencil-line"></i> Edit Kolom
            </a>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .question-preview-item {
        transition: all 0.2s;
    }
    .question-preview-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
</style>
@endsection
