@extends('admin.layout.admin')

@section('title', 'Rombel / Grup Belajar')

@section('content')
<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Rombel / Grup Belajar" />
        </x-slot>
    </x-breadcrumb>
    @if($tab === 'rombel')
        <x-btn title="Tambah Rombel" route="{{ route('admin.study-groups.create') }}" icon="ri-add-fill"></x-btn>
    @endif
</div>

<div class="package-bimbel bg-white p-8 rounded-lg border border-border">
    <x-page-desc
        title="Rombel / Grup Belajar"
        description="{{ $tab === 'pengajuan' ? 'Tinjau pengajuan rombel paket, persetujuan, dan pembayaran anggota.' : 'Kelola kumpulan peserta. Rombel dipakai di Jadwal & Absensi untuk menentukan peserta sesi.' }}"
    ></x-page-desc>

    <div class="mt-6 flex w-fit rounded-lg bg-gray-100 p-1 text-sm font-semibold">
        <a href="{{ route('admin.study-groups.index') }}" class="rounded-md px-4 py-2 {{ $tab === 'rombel' ? 'bg-white text-primary shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">Rombel Aktif</a>
        @if($bookingScheduleEnabled)
            <a href="{{ route('admin.study-groups.index', ['tab' => 'pengajuan']) }}" class="rounded-md px-4 py-2 {{ $tab === 'pengajuan' ? 'bg-white text-primary shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">Pengajuan Paket</a>
        @endif
    </div>

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

    @if($tab === 'pengajuan')
        @include('admin.pages.study-group.partials.applications')
    @else
    <div class="relative overflow-x-auto mt-4">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Rombel</th>
                    <th scope="col" class="px-6 py-3 text-center">Tutor Default</th>
                    <th scope="col" class="px-6 py-3 text-center">Peserta</th>
                    <th scope="col" class="px-6 py-3 text-center">Jadwal</th>
                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                    <th scope="col" class="px-6 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($studyGroups as $studyGroup)
                    <tr class="bg-white border-b border-dashed border-gray-200 text-grey3">
                        <td class="px-6 py-4">
                            <p class="font-semibold text-gray-900">{{ $studyGroup->name }}</p>
                            @if($studyGroup->package)
                                <p class="mt-1 text-xs font-semibold text-primary">Dari paket: {{ $studyGroup->package->name }}</p>
                            @endif
                            @if($studyGroup->description)
                                <p class="mt-1 max-w-md truncate text-xs text-gray-500">{{ $studyGroup->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">{{ $studyGroup->tentor?->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700">
                                {{ $studyGroup->users_count }} peserta
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">
                                {{ $studyGroup->schedules_count }} jadwal
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($studyGroup->is_active)
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Aktif</span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center items-center gap-2">
                                @if($studyGroup->package_id)
                                    <span class="text-xs font-medium text-gray-500">Dari pengajuan paket</span>
                                @else
                                    <a href="{{ route('admin.study-groups.edit', $studyGroup) }}"
                                        class="text-gray-500 hover:text-yellow-500" title="Edit rombel">
                                        <i class="ri-edit-line text-xl"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.study-groups.destroy', $studyGroup) }}" class="inline"
                                        onsubmit="return confirm('Yakin ingin menghapus rombel ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-gray-500 hover:text-red-500" title="Hapus rombel">
                                            <i class="ri-delete-bin-line text-xl"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="ri-group-line text-4xl text-gray-300 mb-2"></i>
                                <p>Belum ada rombel tersedia</p>
                                <a href="{{ route('admin.study-groups.create') }}" class="text-primary hover:underline mt-2">
                                    Buat rombel baru
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($studyGroups->hasPages())
        <div class="flex justify-center mt-4">
            {{ $studyGroups->links() }}
        </div>
    @endif
    @endif
</div>
@endsection
