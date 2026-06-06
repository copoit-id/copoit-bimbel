@extends('admin.layout.admin')

@section('title', 'Kategori Tujuan Peserta')

@section('content')
<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Kategori Tujuan Peserta" />
        </x-slot>
    </x-breadcrumb>
</div>

<x-page-desc title="Kategori Tujuan Peserta" description="Atur instansi tujuan dan subkategori pilihan peserta untuk filter leaderboard."></x-page-desc>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <div class="bg-white border border-border rounded-lg p-6 h-fit">
        <h2 class="text-lg font-semibold text-gray-900">Tambah Instansi</h2>
        <p class="text-sm text-gray-500 mt-1">Tambahkan kategori utama seperti STAN, AKMIL, AKPOL.</p>

        <form action="{{ route('admin.participant-destination-categories.store') }}" method="POST" class="mt-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    placeholder="Contoh: STAN">
                @error('name')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Urutan</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" class="rounded text-primary focus:ring-primary" checked>
                Aktif
            </label>

            <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                Simpan Instansi
            </button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white border border-border rounded-lg p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Daftar Kategori</h2>
                <p class="text-sm text-gray-500">Subkategori tampil menjorok di bawah kategori utama.</p>
            </div>
        </div>

        <div class="mt-5 space-y-4">
            @forelse($categories as $category)
                @include('admin.pages.participant-destination-categories.partials.row', [
                    'category' => $category,
                    'parentOptions' => $parentOptions,
                    'level' => 0,
                ])

                @foreach($category->children as $child)
                    @include('admin.pages.participant-destination-categories.partials.row', [
                        'category' => $child,
                        'parentOptions' => $parentOptions,
                        'level' => 1,
                    ])
                @endforeach
            @empty
                <div class="text-center py-10 text-gray-500">
                    <i class="ri-map-pin-line text-4xl text-gray-300"></i>
                    <p class="mt-2">Belum ada kategori tujuan peserta.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<div id="destinationSubcategoryModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
        <div class="flex justify-between items-center p-5 border-b border-gray-100">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Tambah Sub Category</h3>
                <p class="text-sm text-gray-500">Parent: <span id="destinationSubcategoryParentName" class="font-medium text-gray-700"></span></p>
            </div>
            <button type="button" onclick="closeDestinationSubcategoryModal()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-lg transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <form action="{{ route('admin.participant-destination-categories.store') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="parent_id" id="destinationSubcategoryParentId">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Sub Category <span class="text-red-500">*</span></label>
                <input type="text" name="name" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    placeholder="Contoh: Manajemen Keuangan Negara">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Urutan</label>
                <input type="number" name="sort_order" value="0" min="0"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" class="rounded text-primary focus:ring-primary" checked>
                Aktif
            </label>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeDestinationSubcategoryModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Simpan Sub Category</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openDestinationSubcategoryModal(parentId, parentName) {
        document.getElementById('destinationSubcategoryParentId').value = parentId;
        document.getElementById('destinationSubcategoryParentName').textContent = parentName;
        document.getElementById('destinationSubcategoryModal').classList.remove('hidden');
    }

    function closeDestinationSubcategoryModal() {
        document.getElementById('destinationSubcategoryModal').classList.add('hidden');
    }
</script>
@endsection
