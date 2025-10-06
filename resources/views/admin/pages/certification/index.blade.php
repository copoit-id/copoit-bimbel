@extends('admin.layout.admin')

@section('content')
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold">Manajemen Sertifikasi</h2>
                <p class="text-gray-500">Kelola ujian sertifikasi dan penerbitan sertifikat</p>
            </div>
            <button data-modal-target="add-certification-modal" data-modal-toggle="add-certification-modal"
                class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 flex items-center gap-2">
                <i class="ri-add-line"></i>
                Tambah Sertifikasi
            </button>
        </div>

        <!-- Certification List -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <input type="text" placeholder="Cari sertifikasi..."
                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
                        </div>
                        <select
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Semua Tipe</option>
                            <option value="toefl">TOEFL</option>
                            <option value="ielts">IELTS</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="text-gray-500 hover:text-gray-700">
                            <i class="ri-download-line text-lg"></i>
                        </button>
                        <button class="text-gray-500 hover:text-gray-700">
                            <i class="ri-upload-line text-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4">Nama Sertifikasi</th>
                                <th class="text-left py-3 px-4">Tipe</th>
                                <th class="text-left py-3 px-4">Durasi</th>
                                <th class="text-left py-3 px-4">Harga</th>
                                <th class="text-left py-3 px-4">Status</th>
                                <th class="text-left py-3 px-4">Peserta</th>
                                <th class="text-left py-3 px-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-gray-200">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-orange-100 p-2 rounded">
                                            <i class="ri-award-line text-orange-500"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium">TOEFL ITP Preparation</p>
                                            <p class="text-sm text-gray-500">Reading, Listening, Structure</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm">TOEFL</span>
                                </td>
                                <td class="py-3 px-4">120 Menit</td>
                                <td class="py-3 px-4">Rp 750.000</td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">Aktif</span>
                                </td>
                                <td class="py-3 px-4">245</td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <button class="text-gray-500 hover:text-primary">
                                            <i class="ri-pencil-line"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-red-500">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="border-b border-gray-200">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-orange-100 p-2 rounded">
                                            <i class="ri-award-line text-orange-500"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium">IELTS Academic Test</p>
                                            <p class="text-sm text-gray-500">Reading, Writing, Listening, Speaking</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm">IELTS</span>
                                </td>
                                <td class="py-3 px-4">165 Menit</td>
                                <td class="py-3 px-4">Rp 950.000</td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm">Draft</span>
                                </td>
                                <td class="py-3 px-4">0</td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <button class="text-gray-500 hover:text-primary">
                                            <i class="ri-pencil-line"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-red-500">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="border-b border-gray-200">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-orange-100 p-2 rounded">
                                            <i class="ri-award-line text-orange-500"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium">English Proficiency Test</p>
                                            <p class="text-sm text-gray-500">Grammar, Vocabulary, Comprehension</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm">Other</span>
                                </td>
                                <td class="py-3 px-4">90 Menit</td>
                                <td class="py-3 px-4">Rp 500.000</td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">Aktif</span>
                                </td>
                                <td class="py-3 px-4">178</td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <button class="text-gray-500 hover:text-primary">
                                            <i class="ri-pencil-line"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-red-500">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center mt-4">
                    <p class="text-gray-500 text-sm">Menampilkan 1-3 dari 5 sertifikasi</p>
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50">
                            <i class="ri-arrow-left-s-line"></i>
                        </button>
                        <button class="px-3 py-1 bg-primary text-white rounded-lg hover:bg-primary/90">1</button>
                        <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50">2</button>
                        <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50">
                            <i class="ri-arrow-right-s-line"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Certification Modal -->
    <div id="add-certification-modal" tabindex="-1" aria-hidden="true"
        class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-2xl max-h-full">
            <div class="relative bg-white rounded-lg shadow">
                <div class="flex items-start justify-between p-4 border-b rounded-t">
                    <h3 class="text-xl font-semibold text-gray-900">
                        Tambah Sertifikasi Baru
                    </h3>
                    <button type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center"
                        data-modal-hide="add-certification-modal">
                        <i class="ri-close-line text-lg"></i>
                    </button>
                </div>
                <form action="#">
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="block mb-2 text-sm font-medium text-gray-900">Nama Sertifikasi</label>
                                <input type="text" name="name"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5"
                                    required>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900">Tipe</label>
                                <select name="type"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5"
                                    required>
                                    <option value="">Pilih tipe</option>
                                    <option value="toefl">TOEFL</option>
                                    <option value="ielts">IELTS</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900">Durasi (Menit)</label>
                                <input type="number" name="duration"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5"
                                    required>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900">Harga</label>
                                <input type="number" name="price"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5"
                                    required>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900">Status</label>
                                <select name="status"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5"
                                    required>
                                    <option value="">Pilih status</option>
                                    <option value="active">Aktif</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block mb-2 text-sm font-medium text-gray-900">Deskripsi</label>
                                <textarea name="description" rows="4"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5"
                                    required></textarea>
                            </div>
                            <div class="col-span-2">
                                <label class="block mb-2 text-sm font-medium text-gray-900">Template Sertifikat</label>
                                <div class="flex items-center justify-center w-full">
                                    <label for="dropzone-file"
                                        class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <i class="ri-upload-cloud-2-line text-4xl text-gray-500 mb-2"></i>
                                            <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Klik untuk
                                                    upload</span> atau drag and drop</p>
                                            <p class="text-xs text-gray-500">PNG, JPG atau PDF (MAX. 2MB)</p>
                                        </div>
                                        <input id="dropzone-file" type="file" class="hidden" />
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end p-6 space-x-2 border-t border-gray-200 rounded-b">
                        <button data-modal-hide="add-certification-modal" type="button"
                            class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                            Batal
                        </button>
                        <button type="submit"
                            class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
