@extends('admin.layout.admin')

@section('title', 'Manajemen Tentor')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manajemen Tentor</h1>
            <p class="text-sm text-gray-500">Kelola data tentor untuk assignment kelas dan jadwal.</p>
        </div>
        <a href="{{ route('admin.tentors.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
            <i class="ri-add-line"></i>
            Tambah Tentor
        </a>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4">
        <form method="GET" action="{{ route('admin.tentors.index') }}" class="grid gap-3 md:grid-cols-[1fr_180px_auto]">
            <div class="relative">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Cari nama, email, nomor HP, atau bidang">
            </div>
            <select name="status" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>
            <button class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Filter</button>
        </form>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr>
                    <th class="px-4 py-3">Tentor</th>
                    <th class="px-4 py-3">Kontak</th>
                    <th class="px-4 py-3">Bidang</th>
                    <th class="px-4 py-3 text-center">Assignment</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tentors as $tentor)
                    <tr class="border-t border-gray-100 hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">{{ $tentor->name }}</p>
                            @if($tentor->bio)
                                <p class="mt-1 max-w-md truncate text-xs text-gray-500">{{ $tentor->bio }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p>{{ $tentor->email ?: '-' }}</p>
                            <p class="text-xs text-gray-500">{{ $tentor->phone ?: '-' }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $tentor->expertise ?: '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                {{ $tentor->classes_count }} kelas / {{ $tentor->schedules_count }} jadwal
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="rounded-full px-2 py-1 text-xs font-medium {{ $tentor->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $tentor->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.tentors.edit', array_merge(request()->query(), ['tentor' => $tentor->id])) }}" class="text-gray-500 hover:text-primary" title="Edit tentor">
                                    <i class="ri-edit-line text-xl"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.tentors.destroy', $tentor) }}" onsubmit="return confirm('Yakin ingin menghapus tentor ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-gray-500 hover:text-red-600" title="Hapus tentor">
                                        <i class="ri-delete-bin-line text-xl"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-500">
                            Belum ada tentor.
                            <a href="{{ route('admin.tentors.create') }}" class="font-medium text-primary hover:underline">Tambah tentor pertama</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $tentors->links() }}
</div>
@endsection
