@extends('admin.layout.admin')

@section('title', 'Preview Kecermatan')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Preview {{ $kecermatan->name }}</h2>
            <div class="mt-1 flex items-center gap-2">
                @if($kecermatan->type === 'kecermatan_polri')
                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">POLRI</span>
                @else
                    <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">TNI</span>
                @endif
                <span class="text-sm text-gray-500">{{ $kecermatan->typeLabel() }}</span>
            </div>
        </div>
        <a href="{{ route('admin.kecermatan.edit', $kecermatan) }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
    </div>

    {{-- Columns Section --}}
    @foreach($kecermatan->columns as $column)
    <div class="bg-white rounded-lg shadow border border-gray-200 p-6 space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <div>
                <h3 class="font-bold text-gray-900 text-lg">{{ $column->name }}</h3>
                <p class="text-xs text-gray-500">{{ $column->duration_seconds }} detik · {{ $column->questions->count() }} soal</p>
            </div>
        </div>

        {{-- POLRI Reference Grid --}}
        @if($kecermatan->type === 'kecermatan_polri')
        <div class="mb-5 grid grid-cols-5 overflow-hidden rounded-lg border border-gray-200 text-center shadow-xs">
            @foreach($column->references ?? [] as $index => $reference)
            <div class="border-r border-gray-200 last:border-r-0">
                <div class="bg-gray-50 px-3 py-2 text-xs font-bold text-gray-500">{{ chr(65 + $index) }}</div>
                <div class="px-3 py-3 font-semibold text-gray-900 text-lg">{{ $reference }}</div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Questions Layout (2-Column Tables) --}}
        @php
            $questions = $column->questions->take(20);
            $half = ceil($questions->count() / 2);
            $leftQuestions = $questions->slice(0, $half);
            $rightQuestions = $questions->slice($half);
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Left Column --}}
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 w-16">No</th>
                            <th class="px-4 py-3">Soal</th>
                            <th class="px-4 py-3 w-40 text-center">Jawaban Benar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($leftQuestions as $question)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-900">{{ $question->sort_order }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                @if($kecermatan->type === 'kecermatan_tni')
                                    {{ $question->payload[0] ?? '-' }} + {{ $question->payload[1] ?? '-' }}
                                @else
                                    {{ implode(' | ', $question->payload ?? []) }}
                                @endif
                            </td>
                            <td class="px-4 py-3 font-semibold text-primary text-center">{{ $question->correct_answer }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Right Column --}}
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 w-16">No</th>
                            <th class="px-4 py-3">Soal</th>
                            <th class="px-4 py-3 w-40 text-center">Jawaban Benar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($rightQuestions as $question)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-900">{{ $question->sort_order }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                @if($kecermatan->type === 'kecermatan_tni')
                                    {{ $question->payload[0] ?? '-' }} + {{ $question->payload[1] ?? '-' }}
                                @else
                                    {{ implode(' | ', $question->payload ?? []) }}
                                @endif
                            </td>
                            <td class="px-4 py-3 font-semibold text-primary text-center">{{ $question->correct_answer }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination Alert --}}
        @if($column->questions->count() > 20)
        <p class="mt-3 text-xs text-gray-500">Menampilkan 20 soal pertama dari {{ $column->questions->count() }} soal.</p>
        @endif
    </div>
    @endforeach
</div>
@endsection
