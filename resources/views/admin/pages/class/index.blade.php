@extends('admin.layout.admin')
@section('title', 'Manajemen Kelas')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Manajemen Kelas" />
        </x-slot>
    </x-breadcrumb>
    <x-btn title="Tambah Kelas" route="{{ route('admin.class.create') }}" icon="ri-add-fill">
    </x-btn>
</div>

<div class="package-bimbel bg-white p-8 rounded-lg border border-border">
    <x-page-desc title="Manajemen Kelas" description="Kelola semua kelas yang tersedia"></x-page-desc>

    <div class="relative overflow-x-auto mt-4">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Tanggal & Waktu</th>
                    <th scope="col" class="px-6 py-3 text-center">Judul</th>
                    <th scope="col" class="px-6 py-3 text-center">Mentor</th>
                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                    <th scope="col" class="px-6 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $class)
                <tr class="bg-white border-b border-dashed border-gray-200 text-grey3">
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-semibold">
                                {{ \Carbon\Carbon::parse($class->schedule_time)->translatedFormat('l, d F Y') }}
                            </p>
                            <p>Pukul {{ \Carbon\Carbon::parse($class->schedule_time)->format('H:i') }} WIB</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">{{ $class->title }}</td>
                    <td class="px-6 py-4 text-center">{{ $class->mentor ?? '-' }}</td>
                    <td class="px-6 py-4 text-center">
                        @if($class->status == 'upcoming')
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">Akan Datang</span>
                        @elseif($class->status == 'completed')
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Selesai</span>
                        @else
                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Dibatalkan</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center items-center gap-2">
                            @if($class->zoom_link)
                            <a href="{{ $class->zoom_link }}" target="_blank" class="text-gray-500 hover:text-primary">
                                <i class="ri-video-on-line text-xl"></i>
                            </a>
                            @endif
                            @if($class->drive_link)
                            <a href="{{ $class->drive_link }}" target="_blank"
                                class="text-gray-500 hover:text-blue-600">
                                <i class="ri-folder-line text-xl"></i>
                            </a>
                            @endif
                            <a href="{{ route('admin.class.edit', $class->class_id) }}"
                                class="text-gray-500 hover:text-yellow-500">
                                <i class="ri-edit-line text-xl"></i>
                            </a>
                            <form action="{{ route('admin.class.destroy', $class->class_id) }}" method="POST"
                                class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-500 hover:text-red-500"
                                    onclick="return confirm('Yakin ingin menghapus kelas ini?')">
                                    <i class="ri-delete-bin-line text-xl"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class="ri-calendar-line text-4xl text-gray-300 mb-2"></i>
                            <p>Belum ada kelas tersedia</p>
                            <a href="{{ route('admin.class.create') }}" class="text-primary hover:underline mt-2">
                                Buat kelas baru
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($classes->hasPages())
    <div class="flex justify-center mt-4">
        {{ $classes->links() }}
    </div>
    @endif
</div>

@if(session('success'))
<div class="fixed bottom-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg z-50">
    <p>{{ session('success') }}</p>
</div>
@endif

@endsection
@section('scripts')
<script>
    console.log('Dashboard scripts loaded');
</script>
@endsection
@section('styles')