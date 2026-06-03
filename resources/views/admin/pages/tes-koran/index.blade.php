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
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
        @foreach($tesKorans as $tes)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all p-4 flex flex-col">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <i class="ri-file-edit-line text-lg"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-semibold text-gray-900 text-sm line-clamp-2">{{ $tes->name }}</h3>
                    <p class="mt-1 text-xs text-gray-500 capitalize">
                        {{ $tes->test_type }} · {{ $tes->duration_minutes }}m · {{ $tes->columns_count }}×{{ $tes->rows_count }}
                    </p>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 flex-wrap">
                    @if($tes->is_for_sale && $tes->price > 0)
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-[11px] rounded-full font-medium">
                        Dijual
                    </span>
                    @endif
                    <span class="px-2 py-0.5 {{ $tes->is_active ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-600' }} text-[11px] rounded-full font-medium">
                        {{ $tes->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    <span class="px-2 py-0.5 {{ $tes->is_displayed ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-500' }} text-[11px] rounded-full font-medium">
                        {{ $tes->is_displayed ? 'Tampil' : 'Hidden' }}
                    </span>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                <div class="rounded-lg bg-gray-50 px-3 py-2">
                    <p class="text-gray-500">Harga</p>
                    <p class="font-semibold text-gray-900 truncate">
                        {{ $tes->price > 0 ? 'Rp ' . number_format($tes->price, 0, ',', '.') : 'Tidak dijual' }}
                    </p>
                </div>
                <div class="rounded-lg bg-gray-50 px-3 py-2">
                    <p class="text-gray-500">Hasil</p>
                    <p class="font-semibold text-gray-900">{{ $tes->results_count ?? 0 }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-3 mt-3 border-t border-gray-100">
                <a href="{{ route('admin.tes-koran.results', $tes->id) }}"
                   class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-primary text-xs font-medium">
                    <i class="ri-bar-chart-line"></i>Hasil
                </a>
                <a href="{{ route('admin.tes-koran.edit', $tes->id) }}"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-primary text-primary hover:bg-primary hover:text-white">
                    <i class="ri-edit-line"></i>
                </a>
                <form action="{{ route('admin.tes-koran.destroy', $tes->id) }}" method="POST"
                      onsubmit="return confirm('Yakin hapus tes ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-red-200 text-red-500 hover:bg-red-500 hover:text-white">
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
