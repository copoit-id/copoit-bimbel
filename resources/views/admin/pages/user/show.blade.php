@extends('admin.layout.admin')
@section('title', 'Detail User')

@section('content')
<div class="space-y-6">
    <!-- Header + Breadcrumb -->
    <div class="flex justify-between items-center">
        <nav class="text-sm text-gray-500" aria-label="Breadcrumb">
            <ol class="list-reset flex items-center gap-2">
                <li>
                    <a href="{{ route('admin.user.index') }}" class="hover:text-gray-700">Users</a>
                </li>
                <li class="text-gray-300">/</li>
                <li class="text-gray-700 font-medium">Detail User</li>
            </ol>
        </nav>

        <div class="flex gap-2">
            <a href="{{ route('admin.user.edit', $user->id) }}"
                class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                <i class="ri-edit-line"></i>
                Edit
            </a>

            <form action="{{ route('admin.user.destroy', $user->id) }}" method="POST"
                onsubmit="return confirm('Hapus user ini?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="flex items-center gap-2 px-4 py-2 bg-red text-white rounded-lg hover:bg-red/90">
                    <i class="ri-delete-bin-line"></i>
                    Hapus
                </button>
            </form>

            <a href="{{ route('admin.user.index') }}"
                class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                <i class="ri-arrow-go-back-line"></i>
                Kembali
            </a>
        </div>
    </div>

    <!-- Page Desc -->
    <div class="bg-white border border-border rounded-lg p-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-800">Detail User - {{ $user->name }}</h1>
                <p class="text-gray-500">Informasi profil dan status akun</p>
            </div>
        </div>
    </div>

    <!-- Profile Card -->
    <div class="bg-white rounded-lg border border-border p-6">
        <div class="flex items-center gap-6">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=444444&color=fff&size=100"
                alt="{{ $user->name }}" class="w-20 h-20 rounded-full">
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-800">{{ $user->name }}</h2>
                <p class="text-gray-600">{{ $user->email }}</p>

                <div class="flex items-center gap-3 mt-3">
                    @php
                    $roleClass = match($user->role) {
                    'admin' => 'bg-red-100 text-red-800',
                    'user' => 'bg-green-100 text-green-800',
                    default => 'bg-gray-100 text-gray-800'
                    };
                    $statusClass = match($user->status) {
                    'aktif' => 'bg-green-100 text-green-800',
                    'nonaktif' => 'bg-gray-100 text-gray-800',
                    default => 'bg-gray-100 text-gray-800'
                    };
                    @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $roleClass }}">
                        {{ ucfirst($user->role) }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                        {{ $user->status === 'aktif' ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                    @if($user->email_verified_at)
                    <span
                        class="flex items-center gap-1 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full">
                        <i class="ri-shield-check-line"></i>
                        Email terverifikasi
                    </span>
                    @else
                    <span
                        class="flex items-center gap-1 text-xs text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1 rounded-full">
                        <i class="ri-shield-keyhole-line"></i>
                        Email belum verifikasi
                    </span>
                    @endif
                </div>

                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                    <span class="flex items-center gap-1">
                        <i class="ri-calendar-line"></i>
                        Bergabung: {{ optional($user->created_at)->format('d M Y') }}
                    </span>
                    <span class="flex items-center gap-1">
                        <i class="ri-time-line"></i>
                        Diubah: {{ optional($user->updated_at)->diffForHumans() }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white border border-border rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Akun</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Nama</span>
                    <span class="font-medium text-gray-800">{{ $user->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Username</span>
                    <span class="font-medium text-gray-800">{{ $user->username }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Email</span>
                    <span class="font-medium text-gray-800">{{ $user->email }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Role</span>
                    <span class="font-medium text-gray-800">{{ ucfirst($user->role) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>
                    <span class="font-medium text-gray-800">
                        {{ $user->status === 'aktif' ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-border rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Tambahan</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Tanggal Lahir</span>
                    <span class="font-medium text-gray-800">
                        {{ $user->birthday ? \Illuminate\Support\Carbon::parse($user->birthday)->format('d M Y') : '—'
                        }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Email Verified At</span>
                    <span class="font-medium text-gray-800">
                        {{ $user->email_verified_at ? $user->email_verified_at->format('d M Y H:i') : 'Belum' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Dibuat</span>
                    <span class="font-medium text-gray-800">{{ optional($user->created_at)->format('d M Y H:i')
                        }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Terakhir Diubah</span>
                    <span class="font-medium text-gray-800">{{ optional($user->updated_at)->format('d M Y H:i')
                        }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection