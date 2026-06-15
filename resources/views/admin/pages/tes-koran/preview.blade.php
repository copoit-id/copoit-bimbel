@extends('admin.layout.admin')

@section('title', 'Preview Tes Koran')

@section('content')
<div class="container mx-auto px-4">
    <x-breadcrumb>
        <x-breadcrumb-item href="{{ route('admin.tes-koran.index') }}" title="Tes Koran" />
        <x-breadcrumb-item href="" title="Preview" />
    </x-breadcrumb>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Preview Tes Koran</h2>
                <p class="text-gray-500">{{ $tesKoran->name }} - {{ $tesKoran->test_type == 'pauli' ? 'Pauli' : 'Kraepelin' }}</p>
            </div>
            <a href="{{ route('admin.tes-koran.edit', $tesKoran) }}"
               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="ri-edit-line mr-1"></i>Edit
            </a>
        </div>

        <div class="space-y-8">
            @foreach($sheets as $sheetIndex => $sheet)
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Lembar:</span>
                            <span class="font-medium ml-1">{{ $sheet['name'] ?? 'Lembar ' . ($sheetIndex + 1) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Durasi/Kolom:</span>
                            <span class="font-medium ml-1">{{ $sheet['column_duration_seconds'] ?? 60 }} detik</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Ukuran:</span>
                            <span class="font-medium ml-1">{{ $sheet['columns_count'] }}×{{ $sheet['rows_count'] }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Operasi:</span>
                            <span class="font-medium ml-1">{{ $tesKoran->operationLabelFor($sheet['operation_type'] ?? 'addition') }}</span>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full">
                        <table class="border-collapse">
                            <thead>
                                <tr>
                                    <th class="w-12 h-8 border border-gray-300 bg-gray-100 text-center text-xs">
                                        No
                                    </th>
                                    @foreach($sheet['columns'] as $columnIndex => $column)
                                    <th class="w-8 h-8 border border-gray-300 bg-gray-100 text-center text-xs">
                                        {{ $columnIndex + 1 }}
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @for($r = 0; $r < $sheet['rows_count']; $r++)
                                <tr>
                                    <td class="w-12 h-8 border border-gray-300 bg-gray-100 text-center text-xs">
                                        {{ $r + 1 }}
                                    </td>
                                    @foreach($sheet['columns'] as $column)
                                    <td class="w-8 h-8 border border-gray-300 text-center font-bold text-lg">
                                        {{ $column[$r] }}
                                    </td>
                                    @endforeach
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h4 class="font-medium text-gray-700 mb-2">Petunjuk:</h4>
            <ul class="text-sm text-gray-600 space-y-1">
                <li>1. Peserta mengerjakan angka berurutan sesuai operasi pada masing-masing lembar.</li>
                <li>2. Arah: <strong>{{ $tesKoran->direction == 'top_to_bottom' ? 'Dari atas ke bawah' : 'Dari bawah ke atas' }}</strong></li>
                <li>3. Durasi kolom mengikuti setting tiap lembar.</li>
                <li>4. Saat waktu kolom habis, sistem memberi instruksi pindah dan mengunci kolom sebelumnya.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
