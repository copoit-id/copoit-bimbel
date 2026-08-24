@extends('admin.layout.admin')

@section('title', 'Tambah Rombel')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Tambah Rombel</h2>
            <p class="text-gray-500">Buat grup belajar baru untuk peserta.</p>
        </div>
        <a href="{{ route('admin.study-groups.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
    </div>

    @include('admin.pages.study-group.form', [
        'action' => route('admin.study-groups.store'),
        'method' => 'POST',
        'buttonLabel' => 'Simpan Rombel',
    ])
</div>
@endsection
