@extends('super-admin.layouts.app')
@section('title', 'Super Admin - Role & Akses')
@section('content')

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">Manajemen Role</h2>
            <p class="text-gray-500">Atur role dan akses menu (GET/POST/PUT/DELETE).</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white border border-border rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Tambah Role Baru</h3>
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('super-admin.roles.store') }}" class="flex flex-col md:flex-row gap-4">
            @csrf
            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Role</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2" required>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Tambah Role</button>
            </div>
        </form>
    </div>

    <div class="bg-white border border-border rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Daftar Role & Akses</h3>
        <div class="space-y-5">
            @forelse($roles as $role)
                @php
                    $rolePermissionSlugs = $role->permissions->pluck('slug')->all();
                @endphp
                <div class="border border-gray-200 rounded-xl p-5">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <p class="text-sm text-gray-500">{{ $role->slug }}</p>
                            <h4 class="text-lg font-semibold text-gray-900">{{ $role->name }}</h4>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <form method="POST" action="{{ route('super-admin.roles.update', $role->id) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" value="{{ $role->name }}" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm" {{ $role->slug === 'super_admin' ? 'disabled' : '' }}>
                                <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-full border border-primary text-primary hover:bg-primary hover:text-white transition" {{ $role->slug === 'super_admin' ? 'disabled' : '' }}>
                                    Simpan
                                </button>
                            </form>
                            <form method="POST" action="{{ route('super-admin.roles.destroy', $role->id) }}" onsubmit="return confirm('Hapus role ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-full border border-red-400 text-red-500 hover:bg-red-500 hover:text-white transition" {{ $role->slug === 'super_admin' ? 'disabled' : '' }}>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-4">
                        <form method="POST" action="{{ route('super-admin.roles.permissions', $role->id) }}">
                            @csrf
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-gray-600">
                                    <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left">Menu</th>
                                            @foreach($actions as $actionKey => $method)
                                                <th class="px-4 py-3 text-center">{{ strtoupper($method) }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($features as $featureKey => $feature)
                                            <tr class="border-t border-gray-100">
                                                <td class="px-4 py-3 font-medium text-gray-800">{{ $feature['label'] ?? $featureKey }}</td>
                                                @foreach($actions as $actionKey => $method)
                                                    @php
                                                        $permSlug = $featureKey . '.' . $actionKey;
                                                    @endphp
                                                    <td class="px-4 py-3 text-center">
                                                        <input type="checkbox" name="permissions[]" value="{{ $permSlug }}"
                                                            class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary"
                                                            {{ in_array($permSlug, $rolePermissionSlugs, true) ? 'checked' : '' }}
                                                            {{ $role->slug === 'super_admin' ? 'disabled' : '' }}>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary/90" {{ $role->slug === 'super_admin' ? 'disabled' : '' }}>
                                    Simpan Akses
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500">Belum ada role.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
