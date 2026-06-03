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
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($tesKorans as $tes)
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all p-5 flex flex-col">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <i class="ri-file-edit-line text-2xl"></i>
                </div>
                <div class="flex items-center gap-2">
                    @if($tes->is_for_sale && $tes->price > 0)
                    <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">
                        <i class="ri-shopping-cart-line mr-0.5"></i>Dijual
                    </span>
                    @endif
                    <span class="px-2.5 py-1 {{ $tes->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }} text-xs rounded-full font-medium">
                        {{ $tes->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            </div>

            <div class="flex-1">
                <h3 class="font-bold text-gray-900 text-lg line-clamp-2">{{ $tes->name }}</h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="px-2.5 py-1 bg-blue-100 text-blue-700 text-xs rounded-full capitalize">
                        {{ $tes->test_type }}
                    </span>
                    <span class="px-2.5 py-1 {{ $tes->is_displayed ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-500' }} text-xs rounded-full">
                        <i class="ri-{{ $tes->is_displayed ? 'eye-line' : 'eye-off-line' }} mr-0.5"></i>{{ $tes->is_displayed ? 'Tampil' : 'Hidden' }}
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-3 mt-5">
                    <div class="rounded-xl bg-gray-50 p-3">
                        <p class="text-xs text-gray-500">Durasi</p>
                        <p class="font-semibold text-gray-900">{{ $tes->duration_minutes }}m</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-3">
                        <p class="text-xs text-gray-500">Kolom</p>
                        <p class="font-semibold text-gray-900">{{ $tes->columns_count }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-3">
                        <p class="text-xs text-gray-500">Baris</p>
                        <p class="font-semibold text-gray-900">{{ $tes->rows_count }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mt-3">
                    <div class="rounded-xl bg-gray-50 p-3">
                        <p class="text-xs text-gray-500">Harga</p>
                        <p class="font-semibold text-gray-900">
                            {{ $tes->price > 0 ? 'Rp ' . number_format($tes->price, 0, ',', '.') : 'Tidak dijual' }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-3">
                        <p class="text-xs text-gray-500">Hasil</p>
                        <p class="font-semibold text-gray-900">{{ $tes->results_count ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-4 mt-4 border-t border-gray-100">
                <a href="{{ route('admin.tes-koran.results', $tes->id) }}"
                   class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-primary text-sm font-medium">
                    <i class="ri-bar-chart-line"></i>Hasil
                </a>
                <a href="{{ route('admin.tes-koran.edit', $tes->id) }}"
                   class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-primary text-primary hover:bg-primary hover:text-white">
                    <i class="ri-edit-line"></i>
                </a>
                <form action="{{ route('admin.tes-koran.destroy', $tes->id) }}" method="POST"
                      onsubmit="return confirm('Yakin hapus tes ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-red-200 text-red-500 hover:bg-red-500 hover:text-white">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </form>
            </div>
        </div>
        @endforeach
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
