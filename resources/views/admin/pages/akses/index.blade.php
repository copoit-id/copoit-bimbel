@extends('admin.layout.admin')
@section('title', 'Akses User')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Akses User" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="Akses User - Pilih Paket"></x-page-desc>

<!-- Summary Cards -->
<div class="grid grid-cols-3 gap-4 mt-6">
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Total User Akses</p>
                <p class="text-2xl font-bold text-primary">89</p>
            </div>
            <i class="ri-user-line text-3xl text-primary"></i>
        </div>
    </div>
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Akses Aktif</p>
                <p class="text-2xl font-bold text-primary">67</p>
            </div>
            <i class="ri-key-line text-3xl text-primary"></i>
        </div>
    </div>
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Paket Tersedia</p>
                <p class="text-2xl font-bold text-primary">12</p>
            </div>
            <i class="ri-package-line text-3xl text-primary"></i>
        </div>
    </div>
</div>

<!-- Package List -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
    @foreach($packages as $package)
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm capitalize">{{ $package->type_package
                }}</span>
            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">{{ ucfirst($package->status)
                }}</span>
        </div>
        <h3 class="text-lg font-bold mb-2">{{ $package->name }}</h3>
        <p class="text-gray-600 text-sm mb-4">{{ Str::limit($package->description, 100) }}</p>
        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
            <span>Total User: {{ $package->user_accesses_count ?? 0 }}</span>
            <span>Aktif: {{ $package->active_accesses_count ?? 0 }}</span>
        </div>
        <a href="{{ route('admin.akses.show', $package->package_id) }}"
            class="block w-full text-center bg-primary text-white py-2 rounded-lg hover:bg-primary/90">
            Kelola Akses
        </a>
    </div>
    @endforeach
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    console.log('Package access management loaded');
});
</script>

@endsection

@section('scripts')
<script>
    console.log('Akses user index scripts loaded');
</script>
@endsection
