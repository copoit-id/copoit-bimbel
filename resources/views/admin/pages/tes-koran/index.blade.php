@extends('admin.layout.admin')

@section('title', 'Manajemen Tes Koran')

@section('content')
<div class="container mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Tes Koran</h1>
            <p class="text-gray-600">Kelola tes koran (Pauli & Kraepelin)</p>
        </div>
        <a href="{{ route('admin.tes-koran.create') }}"
           class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <i class="ri-add-line"></i>
            Tambah Tes
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif

    @if($tesKorans->count() > 0)
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Nama Tes</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Jenis</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Durasi</th>
                    <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Kolom</th>
                    <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Status</th>
                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($tesKorans as $tes)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $tes->name }}</div>
                        @if($tes->packages->count() > 0)
                        <div class="text-xs text-gray-500 mt-1">
                            Diassign ke: {{ $tes->packages->pluck('name')->implode(', ') }}
                        </div>
                        @else
                        <div class="text-xs text-orange-500 mt-1">Belum diassign ke paket</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full capitalize">
                            {{ $tes->test_type }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $tes->duration_minutes }} menit
                    </td>
                    <td class="px-4 py-3 text-center text-sm text-gray-600">
                        {{ $tes->columns_count }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($tes->is_active)
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded">Aktif</span>
                        @else
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.tes-koran.results', $tes->id) }}"
                               class="p-2 text-gray-600 hover:text-primary" title="Lihat Hasil">
                                <i class="ri-bar-chart-line"></i>
                            </a>
                            <a href="{{ route('admin.tes-koran.edit', $tes->id) }}"
                               class="p-2 text-gray-600 hover:text-primary" title="Edit">
                                <i class="ri-edit-line"></i>
                            </a>
                            <form action="{{ route('admin.tes-koran.destroy', $tes->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Yakin hapus tes ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-gray-600 hover:text-red-500" title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $tesKorans->links() }}
    </div>
    @else
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
            <i class="ri-file-edit-line text-3xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">Belum ada tes koran</h3>
        <p class="text-gray-500 mb-4">Mulai dengan membuat tes baru</p>
        <a href="{{ route('admin.tes-koran.create') }}"
           class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
            <i class="ri-add-line mr-2"></i>Tambah Tes
        </a>
    </div>
    @endif
</div>
@endsection