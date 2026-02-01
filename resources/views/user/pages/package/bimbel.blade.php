@extends('user.layout.user')
@section('title', __('Paket Tryout'))
@section('content')
<div class="package-bimbel bg-white p-4 rounded-lg border border-border mt-6">
    <x-page-desc title="{{ __('Bimbel - Paket Bimbel A1 2024ket') }}" description="{{ __('Masuk Grup Untuk Baca Bimbel') }}"
        name_link="{{ __('Grup Telegram') }}" url_link="/"></x-page-desc>

    <div class="relative overflow-x-auto mt-4">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">{{ __('Tanggal & Waktu') }}</th>
                    <th scope="col" class="px-6 py-3 text-center">{{ __('Judul') }}</th>
                    <th scope="col" class="px-6 py-3 text-center">{{ __('Mentor') }}</th>
                    <th scope="col" class="px-6 py-3 text-center">{{ __('Link Zoom') }}</th>
                    <th scope="col" class="px-6 py-3 text-center">{{ __('Link Materi') }}</th>
                    <th scope="col" class="px-6 py-3 text-center">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($classes as $class)
                <tr class="bg-white border-b border-dashed border-gray-200 text-grey3">
                    <td class="px-6 py-4">
                        <p class="font-semibold">{{ $class->schedule_time }}</p>
                        <p>{{ __('Pukul 10:00 WIB') }}</p>
                    </td>
                    <td class="px-6 py-4 text-center">{{ $class->title }}</td>
                    <td class="px-6 py-4 text-center">{{ $class->mentor }}</td>

                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <a href="{{ $class->zoom_link }}" target="_blank"
                                class="flex items-center gap-2 border border-primary px-4 py-1 rounded-xl">
                                <i class="ri-video-on-line text-primary"></i>
                                <span class="text-primary">{{ __('Masuk') }}</span>
                            </a>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <a href="{{ $class->drive_link }}" target="_blank"
                                class="flex items-center gap-2 border border-red-500 px-4 py-1 rounded-xl">
                                <i class="ri-video-line text-red-500"></i>
                                <span class="text-red-500">{{ __('Baca') }}</span>
                            </a>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <span class="flex items-center gap-2 border border-primary px-4 py-1 rounded-xl">
                                <i class="ri-check-line text-primary"></i>
                                <span class="text-primary">{{ __('Selesai') }}</span>
                            </span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</div>
@endsection
@section('scripts')
<script>
    console.log('Dashboard scripts loaded');
</script>
@endsection
@section('styles')
