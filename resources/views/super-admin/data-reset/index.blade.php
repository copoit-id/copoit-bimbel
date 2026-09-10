@extends('super-admin.layouts.app')

@section('content')
    <div class="w-full space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reset Data Operasional</h1>
            <p class="mt-1 text-sm text-gray-500">Bersihkan data demo atau operasional tanpa mengubah konfigurasi platform.</p>
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
        @endif

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <div class="flex gap-3">
                <i class="ri-error-warning-line text-xl text-amber-600"></i>
                <div><p class="font-semibold text-amber-900">Tindakan permanen</p><p class="mt-1 text-sm text-amber-800">Data yang dipilih beserta relasi operasionalnya akan dihapus. Pengaturan, role, plan, akun admin/Super Admin, serta riwayat pembayaran tetap dipertahankan.</p></div>
            </div>
        </div>

        <form action="{{ route('super-admin.data-reset.destroy') }}" method="POST" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm" onsubmit="return confirm('Reset data yang dipilih? Tindakan ini tidak dapat dibatalkan.');">
            @csrf
            @method('DELETE')
            <div class="mb-5"><h2 class="text-lg font-semibold text-gray-900">Pilih data</h2><p class="mt-1 text-sm text-gray-500">Centang hanya kategori yang memang ingin dihapus.</p></div>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach($categories as $key => $category)
                    <label class="flex min-h-32 cursor-pointer flex-col justify-between rounded-xl border border-gray-200 p-4 transition hover:border-primary/40 hover:bg-primary/5">
                        <span class="flex items-start justify-between gap-3"><span class="font-semibold text-gray-900">{{ $category['label'] }}</span><input type="checkbox" name="categories[]" value="{{ $key }}" class="mt-0.5 rounded border-gray-300 text-primary focus:ring-primary" @checked(in_array($key, old('categories', []), true))></span>
                        <span class="mt-3 block text-sm leading-6 text-gray-500">{{ $category['description'] }}</span>
                    </label>
                @endforeach
            </div>
            @error('categories')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror

            <div class="mt-6 border-t border-gray-100 pt-6">
                <label for="confirmation" class="block text-sm font-semibold text-gray-700">Ketik <code class="rounded bg-gray-100 px-1.5 py-0.5 text-primary">RESET DATA</code> untuk mengonfirmasi</label>
                <input id="confirmation" name="confirmation" value="{{ old('confirmation') }}" required autocomplete="off" class="mt-2 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10">
                @error('confirmation')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="mt-6 flex justify-end"><button type="submit" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">Reset data yang dipilih</button></div>
        </form>
    </div>
@endsection
