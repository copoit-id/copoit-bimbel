@extends('parent.layout')
@section('title', 'Ringkasan Anak')
@section('content')
@if(! $child)
    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center"><i class="ri-user-search-line text-4xl text-gray-300"></i><h1 class="mt-4 text-xl font-bold text-gray-900">Belum ada anak yang terhubung</h1><p class="mt-2 text-sm text-gray-500">Minta Admin menautkan akun Orang Tua ini ke akun siswa.</p></div>
@else
    <div class="space-y-6">
        <section class="rounded-2xl bg-gradient-to-br from-primary to-primary/80 p-6 text-white shadow-sm"><p class="text-sm text-white/75">Ringkasan belajar</p><h1 class="mt-1 text-2xl font-bold">{{ $child->name }}</h1><p class="mt-2 text-sm text-white/80">Pantau kehadiran, paket aktif, hasil ujian, dan catatan tutor dalam satu halaman.</p></section>
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Kehadiran</p><p class="mt-2 text-3xl font-bold text-gray-900">{{ $attendanceSummary['rate'] ?? '—' }}{{ $attendanceSummary['rate'] !== null ? '%' : '' }}</p><p class="mt-2 text-xs text-gray-500">{{ $attendanceSummary['present'] }}/{{ $attendanceSummary['total'] }} sesi hadir</p></article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Paket aktif</p><p class="mt-2 text-3xl font-bold text-gray-900">{{ $activePackages->count() }}</p><p class="mt-2 text-xs text-gray-500">Paket belajar masih berjalan</p></article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Ujian selesai</p><p class="mt-2 text-3xl font-bold text-gray-900">{{ $recentAnswers->count() }}</p><p class="mt-2 text-xs text-gray-500">Riwayat terbaru ditampilkan</p></article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Feedback tutor</p><p class="mt-2 text-3xl font-bold text-gray-900">{{ $recentFeedback->count() }}</p><p class="mt-2 text-xs text-gray-500">Catatan terbaru untuk anak</p></article>
        </section>
        @if($alerts->isNotEmpty())<section class="rounded-2xl border border-amber-200 bg-amber-50 p-5"><h2 class="font-bold text-amber-900">Perlu perhatian</h2><div class="mt-3 space-y-2">@foreach($alerts as $alert)<p class="flex gap-2 text-sm text-amber-800"><i class="{{ $alert['icon'] }}"></i>{{ $alert['text'] }}</p>@endforeach</div></section>@endif
        <section class="grid gap-6 xl:grid-cols-2">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"><div class="flex justify-between"><div><h2 class="font-bold text-gray-900">Jadwal booking terdekat</h2><p class="mt-1 text-sm text-gray-500">Sesi yang sudah disetujui tutor.</p></div></div><div class="mt-4 divide-y divide-gray-100">@forelse($upcomingBookings as $booking)<div class="py-3"><p class="font-semibold text-gray-800">{{ $booking->package?->name ?? 'Sesi bimbingan' }}</p><p class="mt-1 text-sm text-gray-500">{{ $booking->scheduled_start_at->translatedFormat('D, d M Y · H:i') }} · {{ $booking->tentor?->name ?? 'Tutor' }}</p></div>@empty<p class="py-8 text-center text-sm text-gray-500">Belum ada jadwal booking mendatang.</p>@endforelse</div></article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"><h2 class="font-bold text-gray-900">Feedback tutor terbaru</h2><div class="mt-4 space-y-4">@forelse($recentFeedback as $feedback)<div class="border-b border-gray-100 pb-4 last:border-0"><p class="font-semibold text-gray-800">{{ $feedback->title }}</p><p class="mt-1 text-xs text-gray-500">{{ $feedback->tentor?->name }} · {{ $feedback->created_at->translatedFormat('d M Y') }}</p><p class="mt-2 line-clamp-3 text-sm leading-6 text-gray-600">{{ $feedback->feedback }}</p></div>@empty<p class="py-8 text-center text-sm text-gray-500">Belum ada feedback dari tutor.</p>@endforelse</div></article>
        </section>
    </div>
@endif
@endsection
