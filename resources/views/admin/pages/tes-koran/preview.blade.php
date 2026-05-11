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

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="grid grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Durasi:</span>
                    <span class="font-medium ml-1">{{ $tesKoran->duration_minutes }} menit</span>
                </div>
                <div>
                    <span class="text-gray-500">Kolom:</span>
                    <span class="font-medium ml-1">{{ $tesKoran->columns_count }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Baris:</span>
                    <span class="font-medium ml-1">{{ $tesKoran->rows_count }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Arah:</span>
                    <span class="font-medium ml-1">{{ $tesKoran->direction == 'top_to_bottom' ? 'Atas ke Bawah' : 'Bawah ke Atas' }}</span>
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
                            @for($c = 0; $c < $tesKoran->columns_count; $c++)
                            <th class="w-8 h-8 border border-gray-300 bg-gray-100 text-center text-xs">
                                {{ $c + 1 }}
                            </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @for($r = 0; $r < $tesKoran->rows_count; $r++)
                        <tr>
                            <td class="w-12 h-8 border border-gray-300 bg-gray-100 text-center text-xs">
                                {{ $r + 1 }}
                            </td>
                            @for($c = 0; $c < $tesKoran->columns_count; $c++)
                            <td class="w-8 h-8 border border-gray-300 text-center font-bold text-lg">
                                {{ $columns[$c][$r] }}
                            </td>
                            @endfor
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h4 class="font-medium text-gray-700 mb-2">Petunjuk:</h4>
            <ul class="text-sm text-gray-600 space-y-1">
                <li>1. Peserta menjumlahkan angka berurutan (misal: 3 + 5 = 8)</li>
                <li>2. Jika hasil penjumlahan lebih dari 9, hanya tulis digit terakhir (14 → 4)</li>
                <li>3. Arah: <strong>{{ $tesKoran->direction == 'top_to_bottom' ? 'Dari atas ke bawah (Pauli)' : 'Dari bawah ke atas (Kraepelin)' }}</strong></li>
                <li>4. Jika听见 perintah "garis", buat garis dan lanjut ke kolom berikutnya</li>
            </ul>
        </div>
    </div>
</div>
@endsection