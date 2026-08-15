@extends('admin.layout.admin')

@php
    $isEdit = $article->exists;
    $selectedStatus = old('status', $article->status ?? \App\Models\Article::STATUS_DRAFT);
    $publishedAtValue = old(
        'published_at',
        $article->published_at
            ? $article->published_at->format('Y-m-d\TH:i')
            : ($selectedStatus === \App\Models\Article::STATUS_PUBLISHED ? now('Asia/Jakarta')->format('Y-m-d\TH:i') : '')
    );
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $isEdit ? 'Edit Artikel' : 'Tambah Artikel' }}</h2>
            <p class="text-sm text-gray-500">Tulis artikel blog yang dapat dibaca tanpa login.</p>
        </div>
        <a href="{{ route('admin.artikel.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
    </div>

    <form action="{{ $isEdit ? route('admin.artikel.update', $article) : route('admin.artikel.store') }}"
        method="POST" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[1fr_340px]">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="space-y-5">
            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <label for="title" class="mb-2 block text-sm font-medium text-gray-700">Judul Artikel <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title', $article->title) }}" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                    placeholder="Contoh: Strategi Belajar Efektif untuk Tryout">
                @error('title')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror

                <p class="mt-2 text-xs text-gray-500">Slug URL dibuat otomatis dari judul artikel saat disimpan.</p>

                <label for="excerpt" class="mb-2 mt-4 block text-sm font-medium text-gray-700">Ringkasan</label>
                <textarea id="excerpt" name="excerpt" rows="3"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                    placeholder="Ringkasan singkat yang tampil di daftar artikel">{{ old('excerpt', $article->excerpt) }}</textarea>
                @error('excerpt')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <label for="content" class="mb-2 block text-sm font-medium text-gray-700">Isi Artikel <span class="text-red-500">*</span></label>
                <textarea id="content" name="content" rows="14" required
                    class="ckeditor w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">{{ old('content', $article->content) }}</textarea>
                @error('content')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="space-y-5">
            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <label for="cover_image" class="mb-2 block text-sm font-medium text-gray-700">Cover Artikel</label>
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                    <div class="aspect-[16/10]">
                        <img id="cover-preview"
                            src="{{ old('cover_image') ? '' : ($article->cover_url ?? '') }}"
                            alt="Preview Cover"
                            class="{{ $article->cover_url ? '' : 'hidden' }} h-full w-full object-cover">
                        <div id="cover-placeholder"
                            class="{{ $article->cover_url ? 'hidden' : '' }} flex h-full w-full items-center justify-center text-gray-400">
                            <i class="ri-image-line text-4xl"></i>
                        </div>
                    </div>
                </div>
                <input type="file" id="cover_image" name="cover_image" accept="image/png,image/jpeg,image/webp"
                    class="mt-3 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                <p class="mt-2 text-xs text-gray-500">Ukuran ideal: 1200 × 750 px (rasio 16:10). JPG, PNG, atau WebP maksimal 5MB.</p>
                @if($isEdit && $article->cover_image)
                    <label class="mt-3 flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remove_cover" value="1" class="rounded border-gray-300 text-primary focus:ring-primary">
                        Hapus cover saat menyimpan
                    </label>
                @endif
                @error('cover_image')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <label for="status" class="mb-2 block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                <select id="status" name="status" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <option value="draft" @selected($selectedStatus === 'draft')>Draft</option>
                    <option value="published" @selected($selectedStatus === 'published')>Published</option>
                </select>
                @error('status')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror

                <div id="published-at-field" class="{{ $selectedStatus === 'published' ? '' : 'hidden' }}">
                    <label for="published_at" class="mb-2 mt-4 block text-sm font-medium text-gray-700">Tanggal Publish</label>
                    <input type="datetime-local" id="published_at" name="published_at" value="{{ $publishedAtValue }}"
                        @disabled($selectedStatus !== 'published')
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <p class="mt-2 text-xs text-gray-500">Otomatis memakai waktu saat ini. Ubah hanya bila ingin menjadwalkan publish.</p>
                    @error('published_at')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">
                    <i class="ri-save-line"></i>
                    Simpan
                </button>
                <a href="{{ route('admin.artikel.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Batal
                </a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('cover_image');
        const preview = document.getElementById('cover-preview');
        const placeholder = document.getElementById('cover-placeholder');
        const status = document.getElementById('status');
        const publishedAtField = document.getElementById('published-at-field');
        const publishedAt = document.getElementById('published_at');

        const currentDateTimeLocal = () => {
            const now = new Date();
            const offset = now.getTimezoneOffset() * 60_000;

            return new Date(now.getTime() - offset).toISOString().slice(0, 16);
        };

        const syncPublishedAtField = () => {
            const isPublished = status?.value === 'published';
            publishedAtField?.classList.toggle('hidden', !isPublished);

            if (publishedAt) {
                publishedAt.disabled = !isPublished;
                if (isPublished && !publishedAt.value) publishedAt.value = currentDateTimeLocal();
                if (!isPublished) publishedAt.value = '';
            }
        };

        status?.addEventListener('change', syncPublishedAtField);
        syncPublishedAtField();

        input?.addEventListener('change', function () {
            const file = this.files?.[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (event) {
                preview.src = event.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        });
    });
</script>
@endpush
