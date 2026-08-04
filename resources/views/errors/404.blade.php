@extends('errors.layout')

@section('title', 'Halaman Tidak Ditemukan')
@section('icon', 'ri-file-search-line')
@section('code', '404')
@section('heading', 'Halaman tidak ditemukan')
@section('message', 'Halaman yang Anda cari mungkin sudah dipindahkan, dihapus, atau alamatnya tidak tepat.')

@section('actions')
    <a href="{{ route('landing') }}" class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90">
        Kembali ke beranda
    </a>
@endsection
