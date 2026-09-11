@extends('parent.layout')

@section('title', 'Presensi Anak')

@section('content')
<div class="space-y-5">
    <header><p class="text-sm font-semibold text-primary">Kehadiran</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Presensi {{ $child?->name ?? 'anak' }}</h1><p class="mt-1 text-sm text-gray-500">Pantau konsistensi kehadiran pada setiap sesi belajar.</p></header>
    @if($child)
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach(['total' => ['Total sesi', 'ri-calendar-line', 'text-primary'], 'present' => ['Hadir', 'ri-checkbox-circle-line', 'text-emerald-600'], 'late' => ['Terlambat', 'ri-time-line', 'text-amber-600'], 'absent' => ['Alpa', 'ri-user-unfollow-line', 'text-red-600']] as $key => [$label, $icon, $tone])
                <article class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-center justify-between"><p class="text-sm font-medium text-gray-500">{{ $label }}</p><i class="{{ $icon }} {{ $tone }} text-lg"></i></div><p class="mt-4 text-3xl font-bold text-gray-900">{{ $attendanceSummary[$key] }}</p>@if($key === 'total')<p class="mt-2 text-xs text-gray-500">{{ $attendanceSummary['rate'] ?? '—' }}{{ $attendanceSummary['rate'] !== null ? '%' : '' }} tingkat kehadiran</p>@else<p class="mt-2 text-xs text-gray-500">dari {{ $attendanceSummary['total'] }} sesi</p>@endif</article>
            @endforeach
        </section>
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="font-bold text-gray-900">Riwayat kehadiran</h2><p class="mt-1 text-sm text-gray-500">Status absensi {{ $child->name }} per pertemuan.</p></div><span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ $attendances instanceof \Illuminate\Pagination\AbstractPaginator ? $attendances->total() : $attendances->count() }} catatan</span></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[700px] text-left text-sm"><thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Sesi</th><th class="px-5 py-3">Tutor / Rombel</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Catatan</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($attendances as $attendance)<tr class="align-top"><td class="px-5 py-4 text-gray-600">{{ $attendance->session?->start_at?->translatedFormat('d M Y · H:i') ?? '—' }}</td><td class="px-5 py-4 font-semibold text-gray-900">{{ $attendance->session?->schedule?->title ?? 'Sesi kelas' }}</td><td class="px-5 py-4 text-gray-600"><p>{{ $attendance->session?->tentor?->name ?? '—' }}</p><p class="mt-1 text-xs text-gray-400">{{ $attendance->session?->studyGroup?->name ?? 'Personal' }}</p></td><td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ in_array($attendance->status, ['present','late']) ? 'bg-emerald-50 text-emerald-700' : ($attendance->status === 'absent' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">{{ ['present'=>'Hadir','late'=>'Terlambat','absent'=>'Alpa','excused'=>'Izin'][$attendance->status] ?? $attendance->status }}</span></td><td class="max-w-xs px-5 py-4 text-gray-600">{{ $attendance->notes ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-14 text-center text-gray-500"><i class="ri-calendar-close-line mb-2 block text-3xl text-slate-300"></i>Belum ada riwayat presensi.</td></tr>@endforelse</tbody></table></div>
            @if($attendances instanceof \Illuminate\Pagination\AbstractPaginator)<div class="border-t border-slate-100 px-5 py-4">{{ $attendances->links() }}</div>@endif
        </section>
    @endif
</div>
@endsection
