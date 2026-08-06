@extends('errors.layout')

@section('title', 'Layanan Sedang Tidak Tersedia')
@section('icon', 'ri-tools-line')
@section('code', '503')
@section('heading', 'Layanan sedang tidak tersedia')
@section('message', 'Kami sedang melakukan pemeliharaan atau layanan sedang sibuk. Silakan coba lagi beberapa saat lagi.')

@section('actions')
    <a href="{{ route('landing') }}" class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90">
        Kembali ke beranda
    </a>
@endsection
