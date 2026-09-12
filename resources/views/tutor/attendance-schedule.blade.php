@extends('tutor.layout')

@section('title', 'Absensi Jadwal Tutor')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('tutor.attendance.index') }}" class="text-sm font-semibold text-primary hover:underline">← Kembali ke daftar jadwal</a>
            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-primary">Jadwal mengajar</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $classSchedule->title }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $sessions->first()?->studyGroup?->name ?? 'Tanpa rombel' }} · {{ $tentor->name }}</p>
        </div>
        <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm font-semibold text-primary">{{ $sessions->count() }} sesi ditampilkan</div>
    </div>

    <section class="overflow-hidden border border-gray-200 bg-white">
        <header class="border-b border-gray-200 bg-gray-50 px-4 py-3">
            <h2 class="font-bold text-gray-900">Daftar sesi</h2>
            <p class="mt-0.5 text-xs text-gray-500">Sesi dalam 30 hari terakhir hingga 30 hari mendatang.</p>
        </header>
        <div class="divide-y divide-gray-100">
            @forelse($sessions as $session)
                @include('tutor.partials.attendance-session', ['showDate' => true])
            @empty
                @include('tutor.partials.attendance-empty')
            @endforelse
        </div>
    </section>
</div>

@include('tutor.partials.attendance-modal')
@endsection
