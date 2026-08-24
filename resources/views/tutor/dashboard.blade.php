@extends('admin.layout.admin')

@section('title', 'Dashboard Tutor')

@section('content')
@php
    $attendedCount = (int) ($attendanceSummary?->attended_count ?? 0);
    $pendingAttendanceCount = (int) ($attendanceSummary?->pending_count ?? 0);
    $formatMoney = fn (int $amount): string => 'Rp '.number_format($amount, 0, ',', '.');
@endphp

<div class="space-y-6">
    <section class="rounded-2xl bg-gradient-to-br from-primary to-primary/80 p-6 text-white shadow-sm">
        <p class="text-sm font-medium text-white/75">Dashboard Tutor</p>
        <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Halo, {{ $tentor->name }}</h1>
                <p class="mt-1 text-sm text-white/80">Ringkasan aktivitas mengajar dan penghasilan Anda bulan {{ now()->translatedFormat('F Y') }}.</p>
            </div>
            <a href="{{ route('tutor.schedule.index') }}" class="inline-flex w-fit items-center gap-2 rounded-lg bg-white/15 px-4 py-2 text-sm font-semibold text-white hover:bg-white/25"><i class="ri-calendar-line"></i>Lihat jadwal</a>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><div class="flex items-start justify-between"><div><p class="text-sm text-gray-500">Sesi bulan ini</p><p class="mt-2 text-3xl font-bold text-gray-900">{{ $monthSessions }}</p></div><span class="rounded-lg bg-blue-50 p-2 text-blue-600"><i class="ri-calendar-event-line text-xl"></i></span></div><p class="mt-3 text-xs text-gray-500">Jadwal mengajar pada bulan berjalan</p></article>
        <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><div class="flex items-start justify-between"><div><p class="text-sm text-gray-500">Kehadiran</p><p class="mt-2 text-3xl font-bold text-gray-900">{{ $attendedCount }}</p></div><span class="rounded-lg bg-emerald-50 p-2 text-emerald-600"><i class="ri-checkbox-circle-line text-xl"></i></span></div><p class="mt-3 text-xs text-gray-500">Hadir/terlambat bulan ini @if($pendingAttendanceCount) · {{ $pendingAttendanceCount }} menunggu persetujuan @endif</p></article>
        <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><div class="flex items-start justify-between"><div><p class="text-sm text-gray-500">Booking menunggu</p><p class="mt-2 text-3xl font-bold text-gray-900">{{ $bookingEnabled ? $bookingSummary['waiting_count'] : '—' }}</p></div><span class="rounded-lg bg-amber-50 p-2 text-amber-600"><i class="ri-calendar-schedule-line text-xl"></i></span></div><p class="mt-3 text-xs text-gray-500">{{ $bookingEnabled ? 'Perlu respons Anda' : 'Fitur booking belum diaktifkan' }}</p></article>
        <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><div class="flex items-start justify-between"><div><p class="text-sm text-gray-500">Penghasilan bulan ini</p><p class="mt-2 text-2xl font-bold text-gray-900">{{ $payrollEnabled ? $formatMoney($payrollSummary['current_amount']) : '—' }}</p></div><span class="rounded-lg bg-violet-50 p-2 text-violet-600"><i class="ri-money-dollar-circle-line text-xl"></i></span></div><p class="mt-3 text-xs text-gray-500">{{ $payrollEnabled ? ($payrollSummary['current_count'].' rekap · '.$formatMoney($payrollSummary['paid_amount']).' sudah dibayar') : 'Fitur penggajian belum tersedia pada paket Anda' }}</p></article>
    </section>

    <section class="grid gap-6 xl:grid-cols-3">
        <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
            <div class="flex items-center justify-between gap-3"><div><h2 class="font-bold text-gray-900">Jadwal terdekat</h2><p class="mt-1 text-sm text-gray-500">Sesi berikutnya yang perlu Anda siapkan.</p></div><a href="{{ route('tutor.schedule.index') }}" class="text-sm font-semibold text-primary hover:underline">Halaman jadwal</a></div>
            <div class="mt-4 divide-y divide-gray-100">@forelse($upcomingSessions as $session)<div class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-semibold text-gray-900">{{ $session->schedule?->title ?? $session->class?->title ?? 'Sesi kelas' }}</p><p class="mt-1 text-sm text-gray-500">{{ $session->start_at->translatedFormat('l, d M Y · H:i') }} WIB · {{ $session->studyGroup?->name ?? 'Tanpa rombel' }}</p></div>@if($session->meeting_url)<a href="{{ $session->meeting_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex w-fit items-center gap-1 text-sm font-semibold text-primary hover:underline"><i class="ri-video-chat-line"></i>Meeting</a>@endif</div>@empty<div class="py-10 text-center text-sm text-gray-500"><i class="ri-calendar-close-line mb-2 block text-3xl text-gray-300"></i>Belum ada jadwal mendatang.</div>@endforelse</div>
        </article>

        <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><h2 class="font-bold text-gray-900">Aktivitas Tutor</h2><div class="mt-4 space-y-4"><div class="flex items-center gap-3"><span class="rounded-lg bg-primary/10 p-2 text-primary"><i class="ri-group-line"></i></span><div><p class="text-sm font-semibold text-gray-900">Siswa booking</p><p class="text-sm text-gray-500">{{ $bookingEnabled ? $bookingSummary['student_count'].' siswa pernah ditangani' : 'Aktifkan booking untuk melihat data' }}</p></div></div><div class="flex items-center gap-3"><span class="rounded-lg bg-sky-50 p-2 text-sky-600"><i class="ri-timer-line"></i></span><div><p class="text-sm font-semibold text-gray-900">Booking mendatang</p><p class="text-sm text-gray-500">{{ $bookingEnabled ? $bookingSummary['upcoming_count'].' permintaan/sesi aktif' : '—' }}</p></div></div><div class="flex items-center gap-3"><span class="rounded-lg bg-emerald-50 p-2 text-emerald-600"><i class="ri-checkbox-circle-line"></i></span><div><p class="text-sm font-semibold text-gray-900">Absensi menunggu</p><p class="text-sm text-gray-500">{{ $pendingAttendanceCount ? $pendingAttendanceCount.' absensi menunggu Admin' : 'Tidak ada yang menunggu' }}</p></div></div></div></article>
    </section>

    @if($payrollEnabled)
    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between gap-3"><div><h2 class="font-bold text-gray-900">Riwayat Penggajian</h2><p class="mt-1 text-sm text-gray-500">Status rekap dan pembayaran yang dibuat Admin.</p></div></div><div class="mt-4 overflow-x-auto"><table class="w-full min-w-[560px] text-left text-sm"><thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-3 py-3">Periode</th><th class="px-3 py-3">Nominal</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Dibayar</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($recentPayrolls as $payroll)<tr><td class="px-3 py-3 text-gray-700">{{ $payroll->period_start->translatedFormat('d M') }} – {{ $payroll->period_end->translatedFormat('d M Y') }}</td><td class="px-3 py-3 font-semibold text-gray-900">{{ $formatMoney((int) $payroll->net_amount) }}</td><td class="px-3 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $payroll->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $payroll->status === 'paid' ? 'Lunas' : 'Menunggu pembayaran' }}</span></td><td class="px-3 py-3 text-gray-600">{{ $payroll->paid_at?->translatedFormat('d M Y') ?? '—' }}</td></tr>@empty<tr><td colspan="4" class="px-3 py-8 text-center text-gray-500">Belum ada rekap penggajian untuk Anda.</td></tr>@endforelse</tbody></table></div></section>
    @endif

</div>
@endsection
