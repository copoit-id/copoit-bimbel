@extends('admin.layout.admin')
@section('title', $tab === \App\Models\Discount::TYPE_VOUCHER ? 'Tambah Voucher' : 'Tambah Diskon')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.discounts.index', ['tab' => $tab]) }}" title="Diskon" />
            <x-breadcrumb-item href="" title="Tambah" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc
    title="{{ $tab === \App\Models\Discount::TYPE_VOUCHER ? 'Tambah Voucher' : 'Tambah Diskon' }}"
    description="{{ $tab === \App\Models\Discount::TYPE_VOUCHER ? 'Buat kode voucher untuk pembelian paket.' : 'Buat diskon otomatis untuk paket yang berisi tryout tertentu.' }}"
/>

<form action="{{ route('admin.discounts.store') }}" method="POST" novalidate>
    @csrf
    @include('admin.pages.discounts._form')
</form>

@endsection
