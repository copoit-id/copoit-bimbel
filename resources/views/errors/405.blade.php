@extends('errors.layout')

@section('title', 'Permintaan Tidak Diizinkan')
@section('icon', 'ri-error-warning-line')
@section('code', '405')
@section('heading', 'Permintaan tidak diizinkan')
@section('message', 'Cara akses halaman ini tidak sesuai. Silakan kembali dan coba lagi dari halaman sebelumnya.')

@section('actions')
    <a href="{{ route('landing') }}" class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90">
        Kembali ke beranda
    </a>
@endsection
