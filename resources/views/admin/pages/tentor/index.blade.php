@extends('admin.layout.admin')

@section('title', 'Manajemen Tutor')

@section('content')
<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.user.index') }}" title="Manajemen User" />
            <x-breadcrumb-item href="" title="Tutor" />
        </x-slot>
    </x-breadcrumb>
    <x-btn title="Tambah Tutor" route="{{ route('admin.tentors.create') }}" icon="ri-add-fill"></x-btn>
</div>

@include('admin.pages.user.partials.management-tabs', [
    'activeManagementTab' => 'tentor',
    'roleOptions' => $roleOptions ?? [],
])

<div class="package-bimbel bg-white p-8 rounded-lg border border-border">
    <x-page-desc title="Manajemen Tutor" description="Kelola data Tutor untuk assignment kelas dan jadwal."></x-page-desc>

    @if (session('success'))
        <div class="mt-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
            <i class="ri-checkbox-circle-line text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="mt-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center gap-2">
            <i class="ri-error-warning-line text-lg"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.tentors.index') }}" class="mt-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Tutor..."
                    class="w-full sm:w-72 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
            </div>

            <select name="status"
                class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <option value="">Semua Status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>

            <button type="submit"
                class="px-4 py-2 text-white bg-primary rounded-lg hover:bg-primary/90">
                <i class="ri-filter-3-line"></i> Filter
            </button>

            <a href="{{ route('admin.tentors.index') }}"
                class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="ri-refresh-line"></i> Reset
            </a>
        </div>

        <div class="text-sm text-gray-500">
            Halaman ini: <span class="font-medium text-gray-700">{{ $tentors->count() }} Tutor</span>
            <span class="mx-1 text-gray-300">•</span>
            Total: <span class="font-medium text-gray-700">{{ $tentors->total() }} Tutor</span>
        </div>
    </form>

    <div class="relative overflow-x-auto mt-4">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Tutor</th>
                    <th scope="col" class="px-6 py-3">Kontak</th>
                    <th scope="col" class="px-6 py-3">Bidang</th>
                    <th scope="col" class="px-6 py-3 text-center">Assignment</th>
                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                    <th scope="col" class="px-6 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tentors as $tentor)
                    <tr class="bg-white border-b border-dashed border-gray-200 text-grey3">
                        <td class="px-6 py-4">
                            <p class="font-semibold text-gray-900">{{ $tentor->name }}</p>
                            @if($tentor->bio)
                                <p class="mt-1 max-w-md truncate text-xs text-gray-500">{{ $tentor->bio }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-gray-700">{{ $tentor->email ?: '-' }}</p>
                            <p class="text-xs text-gray-500">{{ $tentor->phone ?: '-' }}</p>
                        </td>
                        <td class="px-6 py-4">{{ $tentor->expertise ?: '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">
                                {{ $tentor->classes_count }} kelas / {{ $tentor->schedules_count }} jadwal
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($tentor->is_active)
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Aktif</span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center items-center gap-2">
                                <a href="{{ route('admin.tentors.edit', array_merge(request()->query(), ['tentor' => $tentor->id])) }}"
                                    class="text-gray-500 hover:text-yellow-500" title="Edit Tutor">
                                    <i class="ri-edit-line text-xl"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.tentors.destroy', $tentor) }}" class="inline"
                                    onsubmit="return confirm('Yakin ingin menghapus Tutor ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-gray-500 hover:text-red-500" title="Hapus Tutor">
                                        <i class="ri-delete-bin-line text-xl"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="ri-user-star-line text-4xl text-gray-300 mb-2"></i>
                                <p>Belum ada Tutor tersedia</p>
                                <a href="{{ route('admin.tentors.create') }}" class="text-primary hover:underline mt-2">
                                    Tambah Tutor pertama
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tentors->hasPages())
        <div class="flex justify-center mt-4">
            {{ $tentors->links() }}
        </div>
    @endif
</div>
@endsection
