@extends('admin.layout.admin')

@section('content')
<div class="p-4 sm:p-6">
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-bold text-gray-900">Overview</h1><p class="mt-1 text-sm text-gray-500">Pantau performa siswa pada rombel Anda</p></div>
        <span class="inline-flex w-fit items-center gap-2 rounded-lg border border-primary/20 bg-primary/5 px-3 py-2 text-sm font-medium text-primary"><i class="ri-community-line"></i>{{ $studyGroups->count() }} Rombel dipantau</span>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-dashboard.stat-card label="Total Siswa" :value="number_format($studentCount)" icon="ri-user-3-line" trend="neutral" trend-value="Rombel terhubung" trend-label="" color="primary" />
        <x-dashboard.stat-card label="Paket Aktif" :value="number_format($activePackageCount)" icon="ri-shopping-bag-3-line" trend="neutral" trend-value="Akses berjalan" trend-label="" color="orange" />
        <x-dashboard.stat-card label="Tryout Selesai" :value="number_format($completedTryoutCount)" icon="ri-draft-line" trend="neutral" trend-value="Dari siswa rombel" trend-label="" color="blue" />
        <x-dashboard.stat-card label="Aktivitas Terbaru" :value="number_format($recentAttempts->count())" icon="ri-pulse-line" trend="neutral" trend-value="Percobaan terbaru" trend-label="" color="green" />
    </div>
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-gray-200 bg-white p-5"><h2 class="font-semibold text-gray-900">Rombel Dipantau</h2><div class="mt-3 space-y-2">@forelse($studyGroups as $studyGroup)<div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2"><span>{{ $studyGroup->name }}</span><span class="text-sm text-gray-500">{{ $studyGroup->students_count }} siswa</span></div>@empty<p class="text-sm text-gray-500">Belum ada rombel yang ditautkan.</p>@endforelse</div></section>
        <section class="rounded-xl border border-gray-200 bg-white p-5"><div class="flex items-center justify-between"><h2 class="font-semibold text-gray-900">Aktivitas Tryout Terbaru</h2><a href="{{ route('admin.school.leaderboard') }}" class="text-sm font-medium text-primary">Leaderboard</a></div><div class="mt-3 space-y-2">@forelse($recentAttempts as $attempt)<div class="rounded-lg bg-gray-50 px-3 py-2"><p class="font-medium text-sm text-gray-800">{{ $attempt->user?->name }}</p><p class="text-xs text-gray-500">{{ $attempt->tryout?->name ?? 'Tryout' }} · {{ $attempt->status }}</p></div>@empty<p class="text-sm text-gray-500">Belum ada aktivitas tryout.</p>@endforelse</div></section>
    </div>
</div>
@endsection
