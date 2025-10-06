@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">
                {{ $user ? 'Edit User' : 'Tambah User Baru' }}
            </h2>
            <p class="text-gray-500">
                {{ $user ? 'Perbarui data user' : 'Tambahkan user baru' }}
            </p>
        </div>
        <a href="{{ route('admin.user.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
    </div>

    <!-- Create / Edit Form -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <form action="{{ $user ? route('admin.user.update', $user->id) : route('admin.user.store') }}" method="POST">
            @csrf
            @if ($user)
            @method('PUT')
            @endif

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-form.input name="name" label="Nama" :value="old('name', $user->name ?? '')" required />
                        <x-form.input name="username" label="Username" :value="old('username', $user->username ?? '')"
                            required />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-form.input type="email" name="email" label="Email" :value="old('email', $user->email ?? '')"
                            required />

                        <x-form.input type="password" name="password" label="Password {{ $user
                            ? '(biarkan kosong jika tidak diubah)' : '' }}" {{ $user ? '' : 'required' }} />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-form.select name="role" label="Role" :options="['admin' => 'Admin', 'user' => 'User']"
                            :value="old('role', $user->role ?? '')" required />
                        <x-form.select name="status" label="Status"
                            :options="['aktif' => 'Aktif', 'nonaktif' => 'Tidak Aktif']"
                            :value="old('status', $user->status ?? 'aktif')" required />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end px-6 py-5 space-x-2">
                <a href="{{ route('admin.user.index') }}"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                    Batal
                </a>
                <button type="submit"
                    class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection