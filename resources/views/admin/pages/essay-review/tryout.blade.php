@extends('admin.layout.admin')
@section('title', 'Koreksi Essay')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.dashboard') }}" title="Dashboard" />
            <x-breadcrumb-item href="{{ route('admin.essay-review.index') }}" title="Koreksi Essay" />
            <x-breadcrumb-item href="" title="Pilih Peserta" />
        </x-slot>
    </x-breadcrumb>
</div>

<x-page-desc title="Koreksi Essay - {{ $tryout->name }}" description="Pilih peserta yang memiliki jawaban essay manual." />

<div class="bg-white border border-border rounded-lg p-6">
    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Peserta</th>
                    <th scope="col" class="px-6 py-3">Email</th>
                    <th scope="col" class="px-6 py-3">Belum Dikoreksi</th>
                    <th scope="col" class="px-6 py-3">Terakhir Dijawab</th>
                    <th scope="col" class="px-6 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingUsers as $row)
                <tr class="bg-white border-b border-dashed border-gray-200">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($row['user']->name) }}&background=444444&color=fff"
                                class="w-10 h-10 rounded-full" alt="{{ $row['user']->name }}">
                            <div>
                                <p class="font-medium text-gray-800">{{ $row['user']->name }}</p>
                                <p class="text-xs text-gray-500">ID: {{ $row['user']->id }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-gray-700">{{ $row['user']->email }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800">
                            {{ $row['pending_count'] }} soal
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-gray-600">
                            {{ $row['last_answered_at'] ? \Carbon\Carbon::parse($row['last_answered_at'])->translatedFormat('d M Y H:i') : '-' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.essay-review.user', [$tryout->tryout_id, $row['user']->id]) }}"
                            class="text-primary hover:text-primary/80" title="Lihat">
                            <i class="ri-eye-line"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                        Tidak ada peserta dengan jawaban essay yang perlu dikoreksi.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
