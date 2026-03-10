@extends('admin.layout.admin')
@section('title', 'Tambah Pembayaran Manual')
@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('admin.pembayaran.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Tambah Pembayaran Manual</h1>
        <p class="text-sm text-gray-500">Masukkan pembayaran yang dibuat secara manual.</p>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <form method="POST" action="{{ route('admin.pembayaran.manual') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email User</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Paket</label>
                <select name="package_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Pilih Paket</option>
                    @foreach($packages as $package)
                        <option value="{{ $package->package_id }}" {{ old('package_id') == $package->package_id ? 'selected' : '' }}>
                            {{ $package->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah</label>
                <input type="number" name="amount" min="0" value="{{ old('amount') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Metode</label>
                <input type="text" name="payment_method" value="{{ old('payment_method', 'manual') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <input type="text" name="notes" value="{{ old('notes') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
