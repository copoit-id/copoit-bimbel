@extends('admin.layout.admin')
@section('title', 'Edit Diskon')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.discounts.index') }}" title="Diskon" />
            <x-breadcrumb-item href="" title="Edit" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="Edit Diskon" description="Ubah pengaturan kode diskon." />

<form action="{{ route('admin.discounts.update', $discount) }}" method="POST" novalidate>
    @csrf
    @method('PUT')
    @include('admin.pages.discounts._form')
</form>

@endsection
