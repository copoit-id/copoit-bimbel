@props(['title' => null, 'description' => null, 'price' => null, 'imageUrl' => null, 'features' => []])
<div class="bg-white px-5 py-5 shadow rounded-lg">
    <div class="w-full h-20 bg-gray-300 rounded-xl mb-4"></div>
    <p class="text-lg font-bold text-black">{{ $title }}</p>
    <p class="font-light">{{ $description }}</p>
    <p class="font-bold text-black">Rp 200.000</p>
    <div class="flex flex-col mt-4 gap-2 font-light">
        @foreach ($features as $feature)
        <span>
            <i class="ri-checkbox-circle-fill text-green"></i>
            {{ $feature }}
        </span>
        @endforeach
    </div>
    <div class="flex gap-2 mt-4">
        <a href="{{ route('admin.package.tryout.index', ['package_id' => 1]) }}"
            class="flex w-full justify-center bg-primary text-white px-4 py-3 font-bold rounded-lg uppercase text-sm">LIHAT
            TRYOUT</a>
        <a href="{{ route('admin.package.class.index', ['package_id' => 1]) }}"
            class="flex w-full justify-center bg-primary text-white px-4 py-3 font-bold rounded-lg uppercase text-sm">LIHAT
            KELAS</a>
    </div>
</div>