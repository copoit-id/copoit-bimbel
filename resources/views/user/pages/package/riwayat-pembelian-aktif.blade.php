@extends('user.layout.user')
@section('title', __('Paket Tryout'))
@section('content')
<div class="dashboard">
    <x-page-desc title="{{ __('Paket Aktif') }}" description="{{ __('Paket aktif yang Anda beli') }}"></x-page-desc>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        @foreach ($activePackages as $access)
        @php
            $package = $access->package;
            $thumbPath = $package?->image;
            $thumbExt = $thumbPath ? strtolower(pathinfo($thumbPath, PATHINFO_EXTENSION)) : null;
            $thumbIsVideo = $thumbPath ? in_array($thumbExt, ['mp4','webm','mov','m4v'], true) : false;
            $thumbUrl = $thumbPath ? Storage::url($thumbPath) : null;
            $featureList = $package && $package->features ? json_decode($package->features, true) : [];
        @endphp
        <div class="px-5 py-5 shadow rounded-lg flex flex-col justify-between">
            <div>
                <div class="w-full h-32 bg-gray-300 rounded-xl mb-4 overflow-hidden">
                    @if($thumbPath)
                        @if($thumbIsVideo)
                            <video src="{{ $thumbUrl }}" class="w-full h-full object-cover" controls preload="metadata" playsinline></video>
                        @else
                            <img src="{{ $thumbUrl }}" alt="{{ $package->name }}"
                                class="w-full h-full object-cover">
                        @endif
                    @else
                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                        <i class="ri-image-line text-3xl text-gray-400"></i>
                    </div>
                    @endif
                </div>
                <p class="text-lg font-bold text-black">{{ $package?->name }}</p>
                <p class="font-light">{{ $package?->description }}</p>
                @if ($package?->type_price === 'paid')
                <p class="font-bold text-black">Rp {{ number_format($package->price, 0, ',', '.') }}</p>
                @endif
                <div class="flex flex-col mt-4 gap-3 font-light">
                    @foreach ($featureList as $feature)
                    <span>
                        <i class="ri-checkbox-circle-fill text-green"></i>
                        {{ $feature }}
                    </span>
                    @endforeach
                    @if(empty($featureList))
                    <span class="text-sm text-gray-500">{{ __('Belum ada fitur terdaftar.') }}</span>
                    @endif
                </div>
            </div>
            <button data-modal-target="static-modal-{{ $access->package->package_id }}"
                data-modal-toggle="static-modal-{{ $access->package->package_id }}"
                class="flex w-full justify-center bg-primary text-white px-4 py-3 font-bold rounded-lg mt-4 uppercase text-sm">{{ __('Lihat Paket') }}</button>
        </div>
        @endforeach
    </div>
</div>
@foreach ($activePackages as $access)
<x-modal.type-package id_package="{{$access->package_id}}" type_package="{{ $access->package->type_package }}">
</x-modal.type-package>
@endforeach
@endsection

@section('styles')
