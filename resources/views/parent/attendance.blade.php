@extends('parent.layout')

@section('title', 'Presensi Anak')

@section('content')
    <x-layout.page-header title="Presensi Anak" description="Pantau konsistensi kehadiran {{ $child?->name ?? 'anak' }} pada setiap sesi belajar." />

    @if($child)
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-dashboard.stat-card label="Total Sesi" :value="$attendanceSummary['total']" icon="ri-calendar-line" />
            <x-dashboard.stat-card label="Hadir" :value="$attendanceSummary['present']" icon="ri-checkbox-circle-line" color="green" />
            <x-dashboard.stat-card label="Terlambat" :value="$attendanceSummary['late']" icon="ri-time-line" color="orange" />
            <x-dashboard.stat-card label="Alpa" :value="$attendanceSummary['absent']" icon="ri-user-unfollow-line" color="red" />
        </section>

        <x-ui.card variant="flat" class="mt-6 rounded-xl border border-gray-200" padding="none">
            <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-lg font-semibold text-gray-900">Riwayat Kehadiran</h2><p class="mt-1 text-sm text-gray-500">Status absensi per pertemuan.</p></div><x-ui.badge variant="light" pill>{{ $attendances->total() }} catatan</x-ui.badge></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm text-gray-600"><thead class="bg-gray-50 text-xs uppercase text-gray-600"><tr><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Sesi</th><th class="px-5 py-3">Tutor / Rombongan Belajar</th><th class="px-5 py-3 text-center">Status</th><th class="px-5 py-3">Catatan</th></tr></thead><tbody>@forelse($attendances as $attendance)<tr class="border-t border-gray-100 align-top hover:bg-gray-50/70"><td class="px-5 py-4 whitespace-nowrap">{{ $attendance->session?->start_at?->translatedFormat('d M Y · H:i') ?? '—' }}</td><td class="px-5 py-4 font-semibold text-gray-900">{{ $attendance->session?->schedule?->title ?? 'Sesi kelas' }}</td><td class="px-5 py-4"><p>{{ $attendance->session?->tentor?->name ?? '—' }}</p><p class="mt-1 text-xs text-gray-400">{{ $attendance->session?->studyGroup?->name ?? 'Personal' }}</p></td><td class="px-5 py-4 text-center"><x-ui.badge :variant="$attendance->status_variant" pill dot>{{ $attendance->status_label }}</x-ui.badge></td><td class="max-w-xs px-5 py-4">{{ $attendance->notes ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-14 text-center text-gray-500"><i class="ri-calendar-close-line mb-2 block text-3xl text-gray-300"></i>Belum ada riwayat presensi.</td></tr>@endforelse</tbody></table></div>
            @if($attendances->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $attendances->links() }}</div>@endif
        </x-ui.card>
    @else
        <x-ui.card variant="flat" class="rounded-xl border border-dashed border-gray-300 px-5 py-14 text-center"><i class="ri-user-search-line text-4xl text-gray-300"></i><p class="mt-3 font-semibold text-gray-700">Belum ada anak yang dapat dipantau</p></x-ui.card>
    @endif
@endsection
