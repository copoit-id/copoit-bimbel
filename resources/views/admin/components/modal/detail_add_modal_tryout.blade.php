<!-- Main modal -->
<div id="static-modal" data-modal-backdrop="static" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow-sm py-6 px-4">
            <div class="flex flex-col items-center justify-center px-4">
                <h3 class="text-xl font-semibold text-gray-900 ">
                    Pilihan Subtest Tryout
                </h3>
                <p class="font-light">Pilih subtest paket yang akan ditambahkan soal</p>
            </div>
            <div class="flex items-center justify-center my-4 gap-2">
                <a href="{{ route('user.package.bimbel', 1) }}"
                    class="flex-1 flex flex-col justify-center items-center gap-2 border border-border py-3 rounded-l-2xl">
                    <div class="flex justify-center items-center bg-blue-light w-10 h-10 rounded-xl">
                        <i class="ri-book-line text-primary text-xl"></i>
                    </div>
                    <p class="font-light">TWK</p>
                </a>
                <a href="{{ route('user.package.tryout', ['id_package' => 1, 'id_tryout' => 1, 'number' => 1]) }}"
                    class="flex-1 flex flex-col justify-center items-center gap-2 border border-border py-3 rounded-r-2xl">
                    <div class="flex justify-center items-center bg-blue-light w-10 h-10 rounded-xl">
                        <i class="ri-book-line text-primary text-xl"></i>
                    </div>
                    <p class="font-light">TIU</p>
                </a>
                <a href="{{ route('user.package.tryout', ['id_package' => 1, 'id_tryout' => 1, 'number' => 1]) }}"
                    class="flex-1 flex flex-col justify-center items-center gap-2 border border-border py-3 rounded-r-2xl">
                    <div class="flex justify-center items-center bg-blue-light w-10 h-10 rounded-xl">
                        <i class="ri-book-line text-primary text-xl"></i>
                    </div>
                    <p class="font-light">TKP</p>
                </a>
            </div>
            <div class="flex justify-center items-center">
                <button data-modal-hide="static-modal" type="button"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-1.5 text-center">Kembali</button>
            </div>
        </div>
    </div>
</div>