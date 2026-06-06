@php
    $isChild = ($level ?? 0) > 0;
@endphp

<div class="border border-gray-200 rounded-lg p-4 {{ $isChild ? 'ml-8 bg-gray-50' : 'bg-white' }}">
    <form action="{{ route('admin.participant-destination-categories.update', $category) }}" method="POST"
        class="grid grid-cols-1 md:grid-cols-12 gap-3 md:items-end">
        @csrf
        @method('PUT')

        <div class="md:col-span-4">
            <label class="block text-xs font-medium text-gray-500 mb-1">
                {{ $isChild ? 'Subkategori' : 'Kategori' }}
            </label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
        </div>

        <div class="md:col-span-3">
            <label class="block text-xs font-medium text-gray-500 mb-1">Parent</label>
            <select name="parent_id"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <option value="">Kategori utama</option>
                @foreach($parentOptions as $parent)
                    @if($parent->id !== $category->id)
                        <option value="{{ $parent->id }}" @selected((int) old('parent_id', $category->parent_id) === (int) $parent->id)>
                            {{ $parent->name }}
                        </option>
                    @endif
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="block text-xs font-medium text-gray-500 mb-1">Urutan</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}" min="0"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
        </div>

        <label class="md:col-span-1 flex items-center gap-2 text-sm text-gray-700 pb-2">
            <input type="checkbox" name="is_active" value="1" class="rounded text-primary focus:ring-primary"
                @checked(old('is_active', $category->is_active))>
            Aktif
        </label>

        <div class="md:col-span-2 flex items-center gap-2">
            <button type="submit"
                class="flex-1 px-3 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 text-sm">
                Simpan
            </button>
        </div>
    </form>

    <form action="{{ route('admin.participant-destination-categories.destroy', $category) }}" method="POST"
        class="mt-3 flex items-center justify-between gap-3"
        onsubmit="return confirm('Hapus kategori tujuan ini? Peserta yang memakai kategori ini akan dikosongkan.');">
        @csrf
        @method('DELETE')
        <p class="text-xs text-gray-500">
            {{ $category->users_count ?? 0 }} peserta
            @if(!$isChild)
                , {{ $category->children->count() }} subkategori
            @endif
        </p>
        <button type="submit" class="px-3 py-1.5 border border-red-200 text-red-600 rounded-lg hover:bg-red-50 text-xs">
            Hapus
        </button>
    </form>
</div>
