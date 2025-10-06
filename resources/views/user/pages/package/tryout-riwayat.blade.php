@extends('user.layout.user')
@section('title', 'Riwayat Tryout')
@section('content')
<div class="package-bimbel bg-white p-4 rounded-lg border border-border">
    <x-page-desc title="Riwayat - {{ $tryout->name }}" description="Lihat riwayat pengerjaan tryout Anda"
        name_link="Kembali ke Tryout" url_link="{{ route('user.package.tryout', $package->package_id) }}">
    </x-page-desc>

    <div class="relative overflow-x-auto mt-4">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-center">#</th>
                    <th scope="col" class="px-6 py-3">Tanggal & Waktu</th>
                    <th scope="col" class="px-6 py-3 text-center">Nilai</th>
                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                    <th scope="col" class="px-6 py-3 text-center">Durasi Pengerjaan</th>
                    @if ($tryout->type_tryout !== 'certification' || $tryout->is_toefl !== 1)
                    <th scope="col" class="px-6 py-3 text-center">Action</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($attemptHistory as $index => $attempt)
                <tr class="bg-white border-b border-dashed border-gray-200 text-grey3">
                    <td class="px-6 py-4 text-center">{{ $index + 1 }}</td>
                    <td class="px-6 py-4">
                        <p class="font-semibold">{{
                            \Carbon\Carbon::parse($attempt['created_at'])->locale('id')->translatedFormat('l, d F Y') }}
                        </p>
                        <p>Pukul {{ \Carbon\Carbon::parse($attempt['started_at'])->format('H:i') }} WIB</p>
                    </td>
                    <td class="px-6 py-4 text-center">{{ $attempt['score'] }}</td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <span
                                class="flex items-center gap-1 border {{ $attempt['is_passed'] ? 'border-green bg-green-light' : 'border-red bg-red-light' }} px-4 py-1 rounded-lg">
                                <i
                                    class="ri-checkbox-circle-fill {{ $attempt['is_passed'] ? 'text-green' : 'text-red' }} text-lg"></i>
                                <span class="{{ $attempt['is_passed'] ? 'text-green' : 'text-red' }}">
                                    {{ $attempt['is_passed'] ? 'Lulus' : 'Tidak Lulus' }}
                                </span>
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        {{ $attempt['duration'] }}
                    </td>
                    @if ($tryout->type_tryout !== 'certification' || $tryout->is_toefl !== 1)
                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <a href="{{ route('user.package.tryout.pembahasan', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id, 'token' => $attempt['attempt_token']]) }}"
                                class="flex items-center gap-2 border border-primary px-4 py-1 rounded-md">
                                <i class="ri-line-chart-line text-primary"></i>
                                <span class="text-primary">Pembahasan</span>
                            </a>
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr class="bg-white border-b border-dashed border-gray-200">
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <i class="ri-file-list-line text-4xl text-gray-400"></i>
                            <p>Belum ada riwayat tryout</p>
                            <a href="{{ route('user.tryout.lobby', [$package->package_id, $tryout->tryout_id]) }}"
                                class="mt-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                                Mulai Tryout
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Color definitions to match other views */
    .bg-green {
        background-color: #059669;
    }

    .text-green {
        color: #059669;
    }

    .border-green {
        border-color: #059669;
    }

    .bg-green-light {
        background-color: #d1fae5;
    }

    .text-red {
        color: #dc2626;
    }

    .bg-red {
        background-color: #dc2626;
    }

    .border-red {
        border-color: #dc2626;
    }

    .bg-red-light {
        background-color: #fee2e2;
    }
</style>
@endsection