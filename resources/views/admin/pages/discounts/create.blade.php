@extends('admin.layout.admin')
@section('title', 'Tambah Diskon')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.discounts.index') }}" title="Diskon" />
            <x-breadcrumb-item href="" title="Tambah" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="Tambah Diskon" description="Buat kode diskon untuk pembelian paket." />

<form action="{{ route('admin.discounts.store') }}" method="POST" novalidate>
    @csrf
    @include('admin.pages.discounts._form')
</form>

@endsection
