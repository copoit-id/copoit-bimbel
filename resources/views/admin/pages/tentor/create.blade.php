@extends('admin.layout.admin')

@section('title', 'Tambah Tutor')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tambah Tutor</h1>
            <p class="text-sm text-gray-500">Lengkapi profil sekaligus akun login Tutor untuk akses portal pengajaran.</p>
        </div>
        <a href="{{ route('admin.tentors.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
    </div>

    @include('admin.pages.tentor.form', [
        'tentor' => null,
        'action' => route('admin.tentors.store'),
        'method' => 'POST',
        'buttonLabel' => 'Simpan Tutor',
    ])
</div>
@endsection
