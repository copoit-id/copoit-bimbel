@extends('admin.layout.admin')

@section('title', 'Edit Tentor')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Tentor</h1>
            <p class="text-sm text-gray-500">Perbarui data tentor {{ $tentor->name }}.</p>
        </div>
        <a href="{{ route('admin.tentors.index', request()->query()) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
    </div>

    @include('admin.pages.tentor.form', [
        'tentor' => $tentor,
        'action' => route('admin.tentors.update', array_merge(request()->query(), ['tentor' => $tentor->id])),
        'method' => 'PUT',
        'buttonLabel' => 'Simpan Perubahan',
    ])
</div>
@endsection
