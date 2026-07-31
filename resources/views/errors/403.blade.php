@extends('errors.layout')

@section('title', 'Akses Ditolak')
@section('icon', 'ri-lock-2-line')
@section('code', '403')
@section('heading', 'Akses ditolak')
@section('message', 'Anda tidak memiliki izin untuk membuka halaman ini.')

@section('actions')
    <a href="{{ route('landing') }}" class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90">
        Kembali ke beranda
    </a>
@endsection
