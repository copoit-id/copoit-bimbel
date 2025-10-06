@props(['title' => null, 'price' => null, 'description' => null])
<div class="bg-white px-5 py-5 shadow rounded-lg">
    <div class="w-full h-20 bg-gray-300 rounded-xl mb-4"></div>
    <p class="text-lg font-bold text-black">{{ $title }}</p>
    <p class="font-light">{{ $description }}</p>
    <p class="font-bold text-black">Rp 200.000</p>
    <div class="flex flex-col mt-4 gap-3 font-light">
        @for ($k = 0; $k < 7; $k++) <span>
            <i class="ri-checkbox-circle-fill text-green"></i>
            50 PDF Materi Lengkap
            </span>
            @endfor
    </div>
    <a href=""
        class="flex justify-center bg-primary text-white px-4 py-3 font-bold rounded-lg mt-4 uppercase text-sm">Beli
        Sekarang</a>
</div>