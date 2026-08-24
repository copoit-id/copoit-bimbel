@extends('errors.layout')

@section('title', 'Terlalu Banyak Permintaan')
@section('icon', 'ri-time-line')
@section('code', '429')
@section('heading', 'Terlalu banyak permintaan')
@section('message', 'Untuk menjaga layanan tetap lancar, tunggu beberapa saat sebelum mencoba lagi.')

@section('actions')
    <button type="button" onclick="window.location.reload()" class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90">
        Coba lagi
    </button>
    <a href="{{ route('landing') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
        Kembali ke beranda
    </a>
@endsection
