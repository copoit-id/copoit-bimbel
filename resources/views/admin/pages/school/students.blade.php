@extends('admin.layout.admin')

@section('content')
<div class="p-4 sm:p-6">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4"><div><h1 class="text-2xl font-bold text-gray-900">Data Siswa</h1><p class="mt-1 text-sm text-gray-500">Siswa dari rombel yang ditautkan.</p></div><form class="flex gap-2" method="GET"><input name="search" value="{{ $search }}" class="rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary" placeholder="Cari nama atau email"><x-ui.button type="submit" size="sm" icon="ri-search-line">Cari</x-ui.button></form></div>
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white"><table class="min-w-full text-sm"><thead class="bg-gray-50 text-left text-gray-500"><tr><th class="px-4 py-3">Siswa</th><th class="px-4 py-3">Rombel</th><th class="px-4 py-3">Paket Aktif</th><th class="px-4 py-3 text-center">Aksi</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($students as $student)<tr><td class="px-4 py-3"><p class="font-medium text-gray-900">{{ $student->name }}</p><p class="text-xs text-gray-500">{{ $student->email }}</p></td><td class="px-4 py-3">{{ $student->studyGroups->pluck('name')->join(', ') ?: '-' }}</td><td class="px-4 py-3">{{ $student->userPackageAccess->pluck('package.name')->filter()->join(', ') ?: '-' }}</td><td class="px-4 py-3 text-center"><x-ui.button :href="route('admin.school.students.show', $student)" variant="outline" size="sm" icon="ri-eye-line">Detail</x-ui.button></td></tr>@empty<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Tidak ada siswa pada rombel yang ditautkan.</td></tr>@endforelse</tbody></table></div>
    <div class="mt-4">{{ $students->links() }}</div>
</div>
@endsection
