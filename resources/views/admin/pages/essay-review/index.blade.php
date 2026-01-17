@extends('admin.layout.admin')
@section('title', 'Koreksi Essay')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.dashboard') }}" title="Dashboard" />
            <x-breadcrumb-item href="" title="Koreksi Essay" />
        </x-slot>
    </x-breadcrumb>
</div>

<x-page-desc title="Koreksi Essay" description="Pilih tryout yang memiliki jawaban essay manual." />

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @forelse($pendingTryouts as $row)
        @php
            $tryout = $row['tryout'];
        @endphp
        <div class="tryout-card bg-white px-5 py-5 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between mb-3">
                <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                    {{ strtoupper($tryout->type_tryout) }} {{ $tryout->is_toefl == 1 ? '- IRT' : '' }}
                </span>
                <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs">
                    {{ $row['pending_count'] }} belum dikoreksi
                </span>
            </div>

            <p class="text-lg font-bold text-black text-center mb-4">{{ $tryout->name }}</p>

            <div class="flex flex-col gap-1 mb-4">
                <span class="flex items-center justify-between">
                    <p class="font-medium">Total Soal:</p>
                    <p class="font-light">{{ $tryout->total_questions ?? 0 }} Soal</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Durasi:</p>
                    <p class="font-light">{{ $tryout->total_duration ?? 0 }} Menit</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Subtest:</p>
                    <p class="font-light">{{ $tryout->tryoutDetails->count() }} Bagian</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Status:</p>
                    @if($tryout->start_date > now())
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">Akan Datang</span>
                    @elseif($tryout->end_date < now())
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Selesai</span>
                    @else
                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Aktif</span>
                    @endif
                </span>
            </div>

            @if($row['last_answered_at'])
            <p class="text-xs text-gray-500 mb-4">Terakhir dijawab: {{ \Carbon\Carbon::parse($row['last_answered_at'])->translatedFormat('d M Y H:i') }}</p>
            @endif

            <a href="{{ route('admin.essay-review.tryout', $tryout->tryout_id) }}"
                class="flex w-full justify-center bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/90">
                <i class="ri-pencil-line mr-2"></i>
                Pilih Tryout
            </a>
        </div>
    @empty
        <div class="text-center text-gray-500 py-8 col-span-full">
            Tidak ada jawaban essay yang perlu dikoreksi.
        </div>
    @endforelse
</div>

@endsection
