@extends('admin.layout.admin')
@section('title', $tab === \App\Models\Discount::TYPE_VOUCHER ? 'Edit Voucher' : 'Edit Diskon')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.discounts.index', ['tab' => $tab]) }}" title="Diskon" />
            <x-breadcrumb-item href="" title="Edit" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc
    title="{{ $tab === \App\Models\Discount::TYPE_VOUCHER ? 'Edit Voucher' : 'Edit Diskon' }}"
    description="{{ $tab === \App\Models\Discount::TYPE_VOUCHER ? 'Ubah pengaturan kode voucher.' : 'Ubah pengaturan diskon otomatis.' }}"
/>

<form action="{{ route('admin.discounts.update', $discount) }}" method="POST" novalidate>
    @csrf
    @method('PUT')
    @include('admin.pages.discounts._form')
</form>

@endsection
