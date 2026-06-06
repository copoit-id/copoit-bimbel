@extends('user.layout.new-user')

@section('title', 'Profil')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$user = auth()->user();
@endphp

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.dashboard.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Profil</h1>
        <p class="text-gray-500 text-sm">Kelola informasi profilmu</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Profile Card -->
    <div class="bg-white rounded-2xl p-6 border border-gray-100">
        <div class="text-center mb-6">
            <div class="w-24 h-24 rounded-full mx-auto mb-4 flex items-center justify-center text-white text-3xl font-bold" style="background-color: {{ $primaryColor }}">
                {{ substr($user->name, 0, 1) }}
            </div>
            <h3 class="font-bold text-gray-800 text-lg">{{ $user->name }}</h3>
            <p class="text-gray-400 text-sm">{{ $user->email }}</p>
        </div>
        
        <div class="space-y-3">
            <a href="{{ route('user.package.my') }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <i class="ri-road-map-line text-lg" style="color: {{ $primaryColor }}"></i>
                    <span class="text-gray-700">Paket Saya</span>
                </div>
                <i class="ri-arrow-right-s-line text-gray-400"></i>
            </a>
            <a href="{{ route('user.package.riwayatPembelian') }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <i class="ri-history-line text-lg" style="color: {{ $primaryColor }}"></i>
                    <span class="text-gray-700">Riwayat</span>
                </div>
                <i class="ri-arrow-right-s-line text-gray-400"></i>
            </a>
            <a href="{{ route('user.package.riwayatPembelian') }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <i class="ri-shopping-bag-line text-lg" style="color: {{ $primaryColor }}"></i>
                    <span class="text-gray-700">Pembelian</span>
                </div>
                <i class="ri-arrow-right-s-line text-gray-400"></i>
            </a>
        </div>
    </div>
    
    <!-- Edit Profile -->
    <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-6">Edit Profil</h3>
        
        <form action="{{ route('user.profile.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:border-transparent"
                           style="--tw-ring-color: {{ $primaryColor }}40"
                           required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" value="{{ $user->email }}" 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-500"
                           disabled>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                    <input type="tel" name="phone" value="{{ old('phone', $user->phone ?? '') }}" 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:border-transparent"
                           style="--tw-ring-color: {{ $primaryColor }}40">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Instansi/Prodi Tujuan</label>
                    <select name="participant_destination_category_id"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:border-transparent"
                        style="--tw-ring-color: {{ $primaryColor }}40">
                        <option value="">Belum memilih</option>
                        @foreach($destinationCategories as $category)
                            @if($category->activeChildren->isEmpty())
                                <option value="{{ $category->id }}" @selected(old('participant_destination_category_id', $user->participant_destination_category_id) == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @else
                                <optgroup label="{{ $category->name }}">
                                    <option value="{{ $category->id }}" @selected(old('participant_destination_category_id', $user->participant_destination_category_id) == $category->id)>
                                        Semua {{ $category->name }}
                                    </option>
                                    @foreach($category->activeChildren as $child)
                                        <option value="{{ $child->id }}" @selected(old('participant_destination_category_id', $user->participant_destination_category_id) == $child->id)>
                                            {{ $child->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="mt-6">
                <button type="submit" class="px-6 py-2.5 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
                    <i class="ri-save-line mr-2"></i>Simpan Perubahan
                </button>
            </div>
        </form>
        
        <hr class="my-8 border-gray-100">
        
        <h3 class="font-bold text-gray-800 mb-6">Ubah Password</h3>
        
        <form action="{{ route('user.profile.password.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Saat Ini</label>
                    <input type="password" name="current_password" 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:border-transparent"
                           style="--tw-ring-color: {{ $primaryColor }}40"
                           required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                    <input type="password" name="password" 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:border-transparent"
                           style="--tw-ring-color: {{ $primaryColor }}40"
                           required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:border-transparent"
                           style="--tw-ring-color: {{ $primaryColor }}40"
                           required>
                </div>
            </div>
            
            <div class="mt-6">
                <button type="submit" class="px-6 py-2.5 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
                    <i class="ri-lock-password-line mr-2"></i>Ubah Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
