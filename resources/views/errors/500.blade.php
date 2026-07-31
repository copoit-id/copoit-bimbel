@extends('errors.layout')

@section('title', 'Terjadi Kesalahan')
@section('icon', 'ri-service-line')
@section('code', '500')
@section('heading', 'Terjadi kesalahan')
@section('message', 'Terjadi kendala pada sistem. Silakan coba lagi beberapa saat lagi.')

@section('actions')
    <a href="{{ route('landing') }}" class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90">
        Kembali ke beranda
    </a>
@endsection
