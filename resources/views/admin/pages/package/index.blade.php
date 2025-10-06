@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Manajemen Paket</h2>
            <p class="text-gray-500">Kelola paket bimbel dan tryout</p>
        </div>
        <a href="{{ route('admin.package.create') }}"
            class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 flex items-center gap-2">
            <i class="ri-add-line"></i>
            Tambah Paket
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
        <p>{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        <p>{{ session('error') }}</p>
    </div>
    @endif

    <!-- Package List -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($packages as $package)
        <div class="bg-white px-5 py-5 shadow rounded-lg flex flex-col justify-between">
            <div class="flex flex-col items-start">
                <!-- Package Image -->
                <div class="w-full h-32 bg-gray-300 rounded-xl mb-4 overflow-hidden">
                    @if($package->image)
                    <img src="{{ Storage::url($package->image) }}" alt="{{ $package->name }}"
                        class="w-full h-full object-cover">
                    @else
                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                        <i class="ri-image-line text-3xl text-gray-400"></i>
                    </div>
                    @endif
                </div>

                <div class="flex justify-between items-center w-full">
                    <p class="text-primary bg-primary/10 px-4 py-1 rounded-full mb-2 capitalize">
                        <i class="ri-book-marked-line me-1"></i>{{ $package->type_package }}
                    </p>
                    <!-- Status Badge -->
                    <div class="h-full">
                        @if($package->status === 'active')
                        <div class=" bg-green-100 text-green-700 rounded-full px-4 py-1"><i
                                class="ri-check-fill me-1"></i>Aktif</div>
                        @else
                        <div class=" bg-gray-100 text-gray-700 rounded-full px-4 py-1"><i
                                class="ri-close-fill me-1"></i>Tidak Aktif</div>
                        @endif
                    </div>

                </div>


                <p class="text-lg font-bold text-black">{{ $package->name }}</p>
                <p class="font-light text-gray-600 mb-2">{{ Str::limit($package->description, 80) }}</p>

                @if ($package->price == 0)
                <p class="font-bold text-green-600">Gratis</p>
                @else
                <p class="font-bold text-black">Rp {{ number_format($package->price, 0, ',', '.') }}</p>
                @endif

                <div class="flex flex-col mt-4 gap-2 font-light">
                    @if($package->features)
                    @foreach (json_decode($package->features) as $feature)
                    <span class="text-sm">
                        <i class="ri-checkbox-circle-fill text-green"></i>
                        {{ $feature }}
                    </span>
                    @endforeach
                    @endif
                </div>
            </div>

            <div class="flex gap-2 mt-4">
                @if ($package->type_package == 'bimbel')
                <a href="{{ route('admin.package.tryout.index', ['package_id' => $package->package_id]) }}"
                    class="flex-1 text-center bg-primary text-white px-3 py-2 rounded-lg text-sm hover:bg-primary/90">
                    Tryout
                </a>
                <a href="{{ route('admin.package.class.index', ['package_id' => $package->package_id]) }}"
                    class="flex-1 text-center bg-primary text-white px-3 py-2 rounded-lg text-sm hover:bg-primary/90">
                    Kelas
                </a>
                @elseif ($package->type_package == 'tryout')
                <a href="{{ route('admin.package.tryout.index', ['package_id' => $package->package_id]) }}"
                    class="flex-1 text-center bg-primary text-white px-3 py-2 rounded-lg text-sm hover:bg-primary/90">
                    Tryout
                </a>
                @elseif ($package->type_package == 'sertifikasi')
                <a href="{{ route('admin.package.tryout.index', ['package_id' => $package->package_id]) }}"
                    class="flex-1 text-center bg-primary text-white px-3 py-2 rounded-lg text-sm hover:bg-primary/90">
                    Sertifikasi
                </a>
                @endif

                <a href="{{ route('admin.package.edit', $package->package_id) }}"
                    class="bg-gray-100 hover:bg-primary hover:text-white border border-primary text-primary px-3 py-2 rounded-lg text-sm">
                    <i class="ri-pencil-fill"></i>
                </a>

                <form action="{{ route('admin.package.destroy', $package->package_id) }}" method="POST" class="inline"
                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus paket ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="bg-red-100 hover:bg-red hover:text-white border border-red/90 text-red px-3 py-2 rounded-lg text-sm">
                        <i class="ri-delete-bin-fill"></i>
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
