@php
    $currentSort = request('sort', $sort ?? 'default');
    $currentSearch = request('search', $search ?? '');
@endphp

<form method="GET" action="{{ $action }}" class="bg-white border border-gray-100 rounded-xl p-3 mb-6 flex flex-col md:flex-row gap-3">
    @foreach(request()->except(['search', 'sort', 'page']) as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endforeach
    <div class="flex-1">
        <label for="material-search" class="sr-only">Cari materi</label>
        <div class="relative">
            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input id="material-search" type="search" name="search" value="{{ $currentSearch }}"
                   placeholder="Cari berdasarkan nama"
                   class="w-full rounded-lg border border-gray-200 pl-10 pr-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                   style="--tw-ring-color: {{ $primaryColor ?? '#10b981' }}">
        </div>
    </div>
    <div class="flex gap-2">
        <label for="material-sort" class="sr-only">Urutkan</label>
        <select id="material-sort" name="sort" class="min-w-[180px] rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                style="--tw-ring-color: {{ $primaryColor ?? '#10b981' }}">
            <option value="default" {{ $currentSort === 'default' ? 'selected' : '' }}>Urutan default</option>
            <option value="latest" {{ $currentSort === 'latest' ? 'selected' : '' }}>Terbaru</option>
            <option value="oldest" {{ $currentSort === 'oldest' ? 'selected' : '' }}>Terlama</option>
            <option value="name_asc" {{ $currentSort === 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
            <option value="name_desc" {{ $currentSort === 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
        </select>
        <button type="submit" class="px-4 py-2.5 text-white rounded-lg text-sm font-medium hover:opacity-90" style="background-color: {{ $primaryColor ?? '#10b981' }}">
            Terapkan
        </button>
    </div>
</form>
