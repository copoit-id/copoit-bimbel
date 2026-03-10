@extends('admin.layout.admin')
@section('title', 'Tambah Pengeluaran')
@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('admin.finance.expenses.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Tambah Pengeluaran</h1>
        <p class="text-sm text-gray-500">Catat transaksi keluar secara manual.</p>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <form method="POST" action="{{ route('admin.finance.expenses.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah</label>
                <input type="number" name="amount" min="0" value="{{ old('amount') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                <input type="date" name="spent_at" value="{{ old('spent_at') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
