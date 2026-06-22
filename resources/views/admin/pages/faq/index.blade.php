@extends('admin.layout.admin')
@php($faqLabel = $clientBranding['faq_label'] ?? 'FAQ')
@section('title', 'Manajemen ' . $faqLabel)
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="{{ $faqLabel }}" />
        </x-slot>
    </x-breadcrumb>
    <a href="{{ route('admin.faq.create') }}"
        class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
        <i class="ri-add-line"></i>
        Tambah {{ $faqLabel }}
    </a>
</div>
<x-page-desc title="Manajemen {{ $faqLabel }}" description="Kelola pertanyaan dan jawaban yang tampil di halaman bantuan." />

<div class="bg-white p-6 rounded-lg border border-border mt-6">
    <div class="relative overflow-x-auto">
        <table class="w-full text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Pertanyaan</th>
                    <th scope="col" class="px-6 py-3">Kategori</th>
                    <th scope="col" class="px-6 py-3 text-center">Urutan</th>
                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($faqs as $faq)
                <tr class="bg-white border-b border-dashed border-gray-200 text-grey3">
                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-900">{{ $faq->question }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-gray-700">{{ $faq->category ?? '-' }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">{{ $faq->sort_order }}</td>
                    <td class="px-6 py-4 text-center">
                        <span
                            class="px-3 py-1 rounded-full text-xs font-medium {{ $faq->is_active ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-gray-100 text-gray-700 border border-gray-200' }}">
                            {{ $faq->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center items-center gap-2">
                            <a href="{{ route('admin.faq.edit', $faq->id) }}"
                                class="px-3 py-1 text-xs font-semibold rounded-full border border-primary text-primary hover:bg-primary hover:text-white transition">
                                Edit
                            </a>
                            <form action="{{ route('admin.faq.destroy', $faq->id) }}" method="POST"
                                onsubmit='return confirm(@js("Hapus {$faqLabel} ini?"));'>
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-3 py-1 text-xs font-semibold rounded-full border border-red-200 text-red-600 hover:bg-red-50 transition">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                        Belum ada data {{ $faqLabel }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $faqs->links() }}
    </div>
</div>

@endsection
