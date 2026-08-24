@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Artikel</h2>
            <p class="text-sm text-gray-500">Kelola konten blog yang tampil di halaman General.</p>
        </div>
        <a href="{{ route('admin.artikel.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">
            <i class="ri-add-line"></i>
            Tambah Artikel
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Total Artikel</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $counts['all'] }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Published</p>
            <p class="mt-1 text-2xl font-bold text-green-600">{{ $counts['published'] }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">Draft</p>
            <p class="mt-1 text-2xl font-bold text-yellow-600">{{ $counts['draft'] }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.artikel.index') }}"
        class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 lg:grid-cols-[1fr_220px_auto] lg:items-end">
        <div>
            <label for="search" class="mb-1 block text-sm font-medium text-gray-700">Cari Artikel</label>
            <input type="text" id="search" name="search" value="{{ $search }}"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                placeholder="Judul atau ringkasan">
        </div>
        <div>
            <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
            <select id="status" name="status"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                <option value="">Semua Status</option>
                <option value="published" @selected($status === 'published')>Published</option>
                <option value="draft" @selected($status === 'draft')>Draft</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800">
                <i class="ri-search-line"></i>
                Filter
            </button>
            <a href="{{ route('admin.artikel.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                Reset
            </a>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Artikel</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Publish</th>
                        <th class="px-5 py-3">Penulis</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($articles as $article)
                    <tr>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-14 w-20 overflow-hidden rounded-lg bg-gray-100">
                                    @if($article->cover_url)
                                        <img src="{{ $article->cover_url }}" alt="{{ $article->title }}"
                                            class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-gray-400">
                                            <i class="ri-article-line text-xl"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900">{{ $article->title }}</p>
                                    <p class="mt-1 max-w-lg truncate text-xs text-gray-500">{{ $article->excerpt ?: '-' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            @if($article->status === 'published')
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Published</span>
                            @else
                                <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700">Draft</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-gray-600">
                            {{ $article->published_at ? $article->published_at->format('d M Y H:i') : '-' }}
                        </td>
                        <td class="px-5 py-4 text-gray-600">{{ $article->author->name ?? '-' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                @if($article->status === 'published')
                                <a href="{{ route('articles.show', $article->slug) }}" target="_blank"
                                    class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50">
                                    Lihat
                                </a>
                                @endif
                                <a href="{{ route('admin.artikel.edit', $article) }}"
                                    class="rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white hover:bg-primary/90">
                                    Edit
                                </a>
                                <form action="{{ route('admin.artikel.destroy', $article) }}" method="POST"
                                    onsubmit="return confirm('Hapus artikel ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-100">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center">
                            <i class="ri-article-line text-4xl text-gray-300"></i>
                            <p class="mt-3 font-semibold text-gray-700">Belum ada artikel</p>
                            <p class="mt-1 text-sm text-gray-500">Tambahkan artikel pertama untuk tampil di halaman blog.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $articles->links() }}
</div>
@endsection
