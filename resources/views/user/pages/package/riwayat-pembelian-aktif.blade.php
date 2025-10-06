@extends('user.layout.user')
@section('title', 'Paket Pembelian')
@section('content')
<div class="dashboard">
    <x-page-desc title="Paket Aktif" description="Paket aktif yang Anda beli"></x-page-desc>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        @foreach ($activePackages as $package)
        <div class=" px-5 py-5 shadow rounded-lg flex flex-col justify-between">
            <div>
                <div class="w-full h-32 bg-gray-300 rounded-xl mb-4 overflow-hidden">
                    @if($package->image)
                    <img src="{{ Storage::url($package->image) }}" alt="{{ $package->name }}"
                        class="w-full h-full object-cover">
                    @else
                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                        <i class="ri-image-line text-3xl text-gray-400"></i>
                    </div>
                    @endif
                </div>
                <p class="text-lg font-bold text-black">{{ $package->package->name }}</p>
                <p class="font-light">{{ $package->package->description }}</p>
                @if ($package->package->type_price == 'paid')
                <p class="font-bold text-black">Rp {{ $package->package->price }}</p>
                @endif
                <div class="flex flex-col mt-4 gap-3 font-light">
                    @foreach (json_decode($package->package->features) as $feature)
                    <span>
                        <i class="ri-checkbox-circle-fill text-green"></i>
                        50 PDF Materi Lengkap
                    </span>
                    @endforeach
                </div>
            </div>
            <button data-modal-target="static-modal-{{ $package->package->package_id }}"
                data-modal-toggle="static-modal-{{ $package->package->package_id }}"
                class="flex w-full justify-center bg-primary text-white px-4 py-3 font-bold rounded-lg mt-4 uppercase text-sm">Lihat
                Paket</button>
        </div>
        @endforeach
    </div>
</div>
@foreach ($activePackages as $package)
<x-modal.type-package id_package="{{$package->package_id}}" type_package="{{ $package->package->type_package }}">
</x-modal.type-package>
@endforeach
@endsection

@section('styles')