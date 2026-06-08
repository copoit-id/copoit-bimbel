@extends('admin.layout.admin')
@section('title', 'Manajemen Diskon')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Diskon" />
        </x-slot>
    </x-breadcrumb>
    <a href="{{ route('admin.discounts.create') }}"
        class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
        <i class="ri-add-line"></i>
        Tambah Diskon
    </a>
</div>
<x-page-desc title="Manajemen Diskon" description="Kelola kode diskon untuk pembelian paket." />

@if(session('success'))
<div class="mt-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
    {{ session('success') }}
</div>
@endif

<div class="bg-white p-6 rounded-lg border border-border mt-6">
    <div class="relative overflow-x-auto">
        <table class="w-full text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Kode</th>
                    <th scope="col" class="px-6 py-3">Diskon</th>
                    <th scope="col" class="px-6 py-3">Limit</th>
                    <th scope="col" class="px-6 py-3">Periode</th>
                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($discounts as $discount)
                <tr class="bg-white border-b border-dashed border-gray-200 text-grey3">
                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-900">{{ $discount->code }}</p>
                        <p class="text-sm text-gray-500">{{ $discount->name ?: '-' }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-900">{{ $discount->formatted_value }}</p>
                        <p class="text-sm text-gray-500">Min. Rp {{ number_format((float) $discount->min_purchase_amount, 0, ',', '.') }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm text-gray-700">{{ $discount->usage_limit ? $discount->used_count . '/' . $discount->usage_limit : 'Tanpa batas' }}</p>
                        <p class="text-xs text-gray-500">Per akun: {{ $discount->per_user_limit ?: 'Tanpa batas' }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm text-gray-700">{{ $discount->starts_at ? $discount->starts_at->format('d M Y H:i') : 'Sekarang' }}</p>
                        <p class="text-xs text-gray-500">s/d {{ $discount->ends_at ? $discount->ends_at->format('d M Y H:i') : 'Tanpa batas' }}</p>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex flex-col items-center gap-1">
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $discount->is_active ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-gray-100 text-gray-700 border border-gray-200' }}">
                                {{ $discount->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $discount->is_public ? 'bg-blue-100 text-blue-700 border border-blue-200' : 'bg-gray-100 text-gray-700 border border-gray-200' }}">
                                {{ $discount->is_public ? 'Tampil' : 'Hidden' }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center items-center gap-2">
                            <a href="{{ route('admin.discounts.edit', $discount) }}"
                                class="px-3 py-1 text-xs font-semibold rounded-full border border-primary text-primary hover:bg-primary hover:text-white transition">
                                Edit
                            </a>
                            <form action="{{ route('admin.discounts.destroy', $discount) }}" method="POST"
                                onsubmit="return confirm('Hapus kode diskon ini?');">
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
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                        Belum ada kode diskon.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $discounts->links() }}
    </div>
</div>

@endsection
