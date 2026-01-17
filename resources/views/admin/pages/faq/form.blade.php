@extends('admin.layout.admin')
@section('title', isset($faq) ? 'Edit FAQ' : 'Tambah FAQ')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.faq.index') }}" title="FAQ" />
            <x-breadcrumb-item href="" title="{{ isset($faq) ? 'Edit FAQ' : 'Tambah FAQ' }}" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="{{ isset($faq) ? 'Edit FAQ' : 'Tambah FAQ' }}"
    description="Isi pertanyaan dan jawaban yang akan tampil di halaman bantuan." />

<div class="bg-white p-6 rounded-lg border border-border mt-6">
    <form
        action="{{ isset($faq) ? route('admin.faq.update', $faq->id) : route('admin.faq.store') }}"
        method="POST" class="space-y-6">
        @csrf
        @if(isset($faq))
        @method('PUT')
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Pertanyaan</label>
            <input type="text" name="question" required
                value="{{ old('question', $faq->question ?? '') }}"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Jawaban</label>
            <textarea name="answer" rows="5" required
                class="summernote w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('answer', $faq->answer ?? '') }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kategori (opsional)</label>
                <input type="text" name="category"
                    value="{{ old('category', $faq->category ?? '') }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Urutan</label>
                <input type="number" name="sort_order" min="0"
                    value="{{ old('sort_order', $faq->sort_order ?? 0) }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div class="flex items-center">
                <label class="inline-flex items-center gap-2 mt-6">
                    <input type="checkbox" name="is_active" value="1"
                        {{ old('is_active', $faq->is_active ?? true) ? 'checked' : '' }}
                        class="w-5 h-5 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                    <span class="text-sm font-medium text-gray-700">Aktif</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.faq.index') }}"
                class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                Batal
            </a>
            <button type="submit"
                class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                {{ isset($faq) ? 'Simpan Perubahan' : 'Simpan FAQ' }}
            </button>
        </div>
    </form>
</div>

@endsection
