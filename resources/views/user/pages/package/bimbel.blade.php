@extends('user.layout.user')
@section('title', 'Paket Pembelian')
@section('content')
@php
    $tesKoranEnabled = $clientBranding['tes_koran_enabled'] ?? true;
    $tutorChatEnabled = $clientBranding['tutor_chat_enabled'] ?? false;
@endphp
<div class="package-bimbel bg-white p-4 rounded-lg border border-border mt-6">
    <x-page-desc
        :title="__('Bimbel - :name', ['name' => $package->name])"
        :description="$package->description ?: __('Masuk grup untuk baca bimbel')"
        :name_link="$package->telegram_group_url ? __('Grub') : null"
        :url_link="$package->telegram_group_url">
    </x-page-desc>

    <div class="relative overflow-x-auto mt-4">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Tanggal & Waktu</th>
                    <th scope="col" class="px-6 py-3 text-center">Judul</th>
                    <th scope="col" class="px-6 py-3 text-center">Mentor</th>
                    <th scope="col" class="px-6 py-3 text-center">Link Zoom</th>
                    <th scope="col" class="px-6 py-3 text-center">Link Materi</th>
                    @if($tutorChatEnabled)
                        <th scope="col" class="px-6 py-3 text-center">Chat Tutor</th>
                    @endif
                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($classes as $class)
                <tr class="bg-white border-b border-dashed border-gray-200 text-grey3">
                    <td class="px-6 py-4">
                        <p class="font-semibold">{{ $class->schedule_time }}</p>
                        <p>Pukul 10:00 WIB</p>
                    </td>
                    <td class="px-6 py-4 text-center">{{ $class->title }}</td>
                    <td class="px-6 py-4 text-center">{{ $class->tentor?->name ?? $class->mentor ?? '-' }}</td>

                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <a href="{{ route('user.class.zoom', $class->class_id) }}" target="_blank"
                                class="flex items-center gap-2 border border-primary px-4 py-1 rounded-xl">
                                <i class="ri-video-on-line text-primary"></i>
                                <span class="text-primary">Masuk</span>
                            </a>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <a href="{{ route('user.class.material', $class->class_id) }}" target="_blank"
                                class="flex items-center gap-2 border border-red-500 px-4 py-1 rounded-xl">
                                <i class="ri-video-line text-red-500"></i>
                                <span class="text-red-500">Baca</span>
                            </a>
                        </div>
                    </td>

                    @if($tutorChatEnabled)
                        <td class="px-6 py-4">
                            <div class="flex justify-center">
                                @if($class->tentor?->user_id)
                                    <a href="{{ route('user.chat.class.show', $class) }}" class="flex items-center gap-2 border border-sky-500 px-4 py-1 rounded-xl">
                                        <i class="ri-chat-3-line text-sky-500"></i>
                                        <span class="text-sky-600">Chat</span>
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">Belum tersedia</span>
                                @endif
                            </div>
                        </td>
                    @endif

                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <span class="flex items-center gap-2 border border-primary px-4 py-1 rounded-xl">
                                <i class="ri-check-line text-primary"></i>
                                <span class="text-primary">Selesai</span>
                            </span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</div>

@if($tesKoranEnabled && isset($tesKorans) && $tesKorans->count() > 0)
<div class="package-bimbel bg-white p-4 rounded-lg border border-border mt-6">
    <x-page-desc
        :title="__('Tes Koran')"
        :description="__('Tes Pauli dan Kraepelin yang termasuk dalam paket ini')">
    </x-page-desc>

    <div class="relative overflow-x-auto mt-4">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Nama Tes</th>
                    <th scope="col" class="px-6 py-3 text-center">Tipe</th>
                    <th scope="col" class="px-6 py-3 text-center">Durasi</th>
                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tesKorans as $tesKoran)
                <tr class="bg-white border-b border-dashed border-gray-200 text-grey3">
                    <td class="px-6 py-4">
                        <p class="font-semibold">{{ $tesKoran->name }}</p>
                        <p class="text-xs text-gray-500">Kolom: {{ $tesKoran->columns_count }} | Baris: {{ $tesKoran->rows_count }}</p>
                    </td>
                    <td class="px-6 py-4 text-center">{{ ucfirst($tesKoran->test_type) }}</td>
                    <td class="px-6 py-4 text-center">{{ $tesKoran->column_duration_seconds ?? 60 }} detik/kolom</td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <a href="{{ route('user.tes-koran.show', $tesKoran) }}"
                                class="flex items-center gap-2 border border-primary px-4 py-1 rounded-xl">
                                <i class="ri-play-circle-line text-primary"></i>
                                <span class="text-primary">Mulai</span>
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
@section('scripts')
<script>
    console.log('Dashboard scripts loaded');
</script>
@endsection
@section('styles')
