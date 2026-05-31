@extends('admin.layout.admin')

@section('title', 'Manajemen Tes Koran')

@section('content')
<div class="container mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Tes Koran</h1>
            <p class="text-gray-600">Kelola tes koran (Pauli & Kraepelin)</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.tes-koran.create') }}"
               class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <i class="ri-add-line"></i>
                Tambah Tes
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif

    @if($packages->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($packages as $package)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded-full font-medium">
                        <i class="ri-file-edit-line mr-1"></i>Tes Koran
                    </span>
                    @if($package->status === 'active')
                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded">Aktif</span>
                    @else
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">Nonaktif</span>
                    @endif
                </div>

                <h3 class="font-bold text-lg text-gray-800 mb-2">{{ $package->name }}</h3>
                <p class="text-sm text-gray-500 mb-4 line-clamp-2">{{ $package->description ?? 'Tidak ada deskripsi' }}</p>

                <div class="space-y-2 mb-4">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="ri-file-list-3-line mr-2 text-primary"></i>
                        <span>{{ $package->tesKorans->count() }} Tes</span>
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="ri-time-line mr-2 text-primary"></i>
                        <span>{{ $package->duration ?? 30 }} Hari Akses</span>
                    </div>
                </div>

                @if($package->tesKorans->count() > 0)
                <div class="border-t pt-4 mb-4">
                    <p class="text-xs text-gray-500 mb-2">Tes dalam paket:</p>
                    @foreach($package->tesKorans->take(3) as $tes)
                    <div class="flex items-center justify-between py-1 text-sm">
                        <span class="truncate flex-1">{{ $tes->name }}</span>
                        <div class="flex items-center gap-2 ml-2">
                            @if($tes->is_active)
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            @else
                            <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                            @endif
                            <span class="text-xs text-gray-400">{{ $tes->duration_minutes }} menit</span>
                        </div>
                    </div>
                    @endforeach
                    @if($package->tesKorans->count() > 3)
                    <p class="text-xs text-gray-400 mt-1">+{{ $package->tesKorans->count() - 3 }} tes lainnya</p>
                    @endif
                </div>
                @endif

                <div class="flex gap-2">
                    <a href="{{ route('admin.tes-koran.create') . '?package_id=' . $package->package_id }}"
                       class="flex-1 text-center bg-primary text-white px-3 py-2 rounded-lg text-sm hover:bg-primary/90">
                        <i class="ri-add-line mr-1"></i>Tambah Tes
                    </a>
                    <a href="{{ route('admin.tes-koran.index') }}?package={{ $package->package_id }}"
                       class="p-2 border border-gray-300 rounded-lg hover:bg-gray-50" title="Lihat Hasil">
                        <i class="ri-bar-chart-line text-gray-600"></i>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
            <i class="ri-file-edit-line text-3xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">Belum ada paket Tes Koran</h3>
        <p class="text-gray-500 mb-4">Mulai dengan membuat paket baru</p>
        <a href="{{ route('admin.tes-koran.create-package') }}"
           class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
            <i class="ri-add-line mr-2"></i>Tambah Paket
        </a>
    </div>
    @endif
</div>
@endsection