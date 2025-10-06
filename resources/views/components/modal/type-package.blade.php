@props(['id_package' => null, 'type_package' => null])
<!-- Main modal -->
<div id="static-modal-{{ $id_package }}" data-modal-backdrop="static" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow-sm py-6 px-4">
            <div class="flex flex-col items-center justify-center px-4">
                <h3 class="text-xl font-semibold text-gray-900 ">
                    Pilihan Tipe Paket
                </h3>
                <p class="font-light">Pilih paket yang akan Anda ingin buka</p>
            </div>
            <div class="flex items-center justify-center my-4 gap-2">
                @if ($type_package == 'bimbel')
                <a href="{{ route('user.package.bimbel', $id_package) }}"
                    class="flex-1 flex flex-col justify-center items-center gap-2 border border-border py-3 rounded-l-2xl">
                    <div class="flex justify-center items-center bg-blue-light w-10 h-10 rounded-xl">
                        <i class="ri-book-line text-primary text-xl"></i>
                    </div>
                    <p class="font-light">Bimbel</p>
                </a>
                <a href="{{ route('user.package.tryout', $id_package) }}"
                    class="flex-1 flex flex-col justify-center items-center gap-2 border border-border py-3 rounded-r-2xl">
                    <div class="flex justify-center items-center bg-blue-light w-10 h-10 rounded-xl">
                        <i class="ri-book-line text-primary text-xl"></i>
                    </div>
                    <p class="font-light">Tryout</p>
                </a>
                @elseif($type_package == 'tryout')
                <a href="{{ route('user.package.tryout', $id_package) }}"
                    class="flex-1 flex flex-col justify-center items-center gap-2 border border-border py-3 rounded-r-2xl">
                    <div class="flex justify-center items-center bg-blue-light w-10 h-10 rounded-xl">
                        <i class="ri-book-line text-primary text-xl"></i>
                    </div>
                    <p class="font-light">Tryout</p>
                </a>
                @elseif($type_package == 'sertifikasi')
                <a href="{{ route('user.package.tryout', $id_package) }}"
                    class="flex-1 flex flex-col justify-center items-center gap-2 border border-border py-3 rounded-r-2xl">
                    <div class="flex justify-center items-center bg-blue-light w-10 h-10 rounded-xl">
                        <i class="ri-book-line text-primary text-xl"></i>
                    </div>
                    <p class="font-light">Sertifikasi</p>
                </a>
                @endif

            </div>
            <div class="flex justify-center items-center">
                <button data-modal-hide="static-modal-{{$id_package}}" type="button"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-1.5 text-center">Kembali</button>
            </div>
        </div>
    </div>
</div>