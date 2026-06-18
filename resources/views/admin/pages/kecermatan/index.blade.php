@extends('admin.layout.admin')

@section('title', 'Manajemen Kecermatan')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Manajemen Kecermatan</h2>
            <p class="text-gray-500">Kelola Kecermatan TNI dan POLRI berbasis kolom.</p>
        </div>
        <a href="{{ route('admin.kecermatan.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-white hover:bg-primary/90">
            <i class="ri-add-line"></i>Tambah Kecermatan
        </a>
    </div>

    @if(session('success'))
    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3 gap-5">
        @forelse($kecermatans as $kecermatan)
        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <i class="ri-focus-3-line text-2xl"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-semibold text-gray-900 line-clamp-2">{{ $kecermatan->name }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $kecermatan->typeLabel() }} · {{ $kecermatan->columns_count }} kolom</p>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-2 text-xs font-medium">
                <span class="rounded-full px-2.5 py-1 {{ $kecermatan->is_active ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-600' }}">
                    {{ $kecermatan->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
                <span class="rounded-full px-2.5 py-1 {{ $kecermatan->is_displayed ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $kecermatan->is_displayed ? 'Tampil' : 'Hidden' }}
                </span>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-600">{{ $kecermatan->attempts_count }} attempt</span>
            </div>

            <div class="mt-5 flex items-center gap-3 border-t border-gray-100 pt-4">
                <a href="{{ route('admin.kecermatan.preview', $kecermatan) }}" class="flex-1 rounded-lg border border-gray-200 px-4 py-2 text-center text-sm font-medium text-gray-600 hover:bg-gray-50">Preview</a>
                <a href="{{ route('admin.kecermatan.edit', $kecermatan) }}" class="flex-1 rounded-lg bg-primary px-4 py-2 text-center text-sm font-medium text-white hover:bg-primary/90">Edit</a>
                <form action="{{ route('admin.kecermatan.destroy', $kecermatan) }}" method="POST" onsubmit="return confirm('Yakin hapus kecermatan ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-500 hover:text-white">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full rounded-xl border border-gray-100 bg-white p-12 text-center">
            <i class="ri-focus-3-line text-4xl text-gray-300"></i>
            <h3 class="mt-3 text-lg font-semibold text-gray-900">Belum ada kecermatan</h3>
            <p class="mt-1 text-gray-500">Buat paket kecermatan pertama untuk mulai generate soal.</p>
        </div>
        @endforelse
    </div>

    {{ $kecermatans->links() }}
</div>
@endsection
