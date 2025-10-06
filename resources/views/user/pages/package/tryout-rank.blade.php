@extends('user.layout.user')
@section('title', 'Ranking Tryout')
@section('content')
<div class="package-bimbel bg-white p-4 rounded-lg border border-border">
    <x-page-desc title="Ranking - {{ $tryout->name }}" description="Leaderboard peserta tryout"
        name_link="Kembali ke Tryout">
    </x-page-desc>

    <!-- Statistics Cards -->
    @if($rankings->count() > 0)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 mt-4">
        <div class="bg-white p-4 rounded-lg border border-border">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Peserta</p>
                    <p class="text-2xl font-bold text-dark">{{ $rankings->count() }}</p>
                </div>
                <i class="ri-group-line text-3xl text-dark"></i>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-border">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Rata-rata Skor</p>
                    <p class="text-2xl font-bold text-dark">{{ number_format($rankings->avg('raw_score'), 1) }}</p>
                </div>
                <i class="ri-bar-chart-line text-3xl text-dark"></i>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-border">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Skor Tertinggi</p>
                    @php
                    $highestScore = $rankings->max('raw_score');
                    $highestMaxScore = $rankings->max('max_score');
                    @endphp
                    <p class="text-2xl font-bold text-dark">
                        {{ number_format($highestScore ?? 0, 0) }}
                        @if($highestMaxScore)
                        <span class="text-base font-semibold text-gray-500">/ {{ number_format($highestMaxScore, 0)
                            }}</span>
                        @endif
                    </p>
                </div>
                <i class="ri-trophy-line text-3xl text-dark"></i>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-border">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Tingkat Kelulusan</p>
                    @php
                    $passedCount = $rankings->where('is_passed', true)->count();
                    $passRate = $rankings->count() > 0 ? ($passedCount / $rankings->count()) * 100 : 0;
                    @endphp
                    <p class="text-2xl font-bold text-dark">{{ number_format($passRate, 1) }}%</p>
                </div>
                <i class="ri-check-double-line text-3xl text-dark"></i>
            </div>
        </div>
    </div>
    @endif

    <div class="relative overflow-x-auto mt-4">
        <table class="w-full text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Peringkat</th>
                    <th scope="col" class="px-6 py-3">Peserta</th>
                    <th scope="col" class="px-6 py-3 text-center">Skor</th>
                    <th scope="col" class="px-6 py-3 text-center">Waktu Selesai</th>
                    <th scope="col" class="px-6 py-3 text-center">Tanggal</th>
                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rankings as $index => $ranking)
                @php
                $rank = $index + 1;
                $rawScoreValue = $ranking['raw_score'] ?? 0;
                $maxScoreValue = $ranking['max_score'] ?? null;
                $rawScoreDisplay = abs($rawScoreValue - round($rawScoreValue)) < 0.01
                    ? number_format($rawScoreValue, 0)
                    : number_format($rawScoreValue, 2);
                $maxScoreDisplay = $maxScoreValue
                    ? (abs($maxScoreValue - round($maxScoreValue)) < 0.01
                        ? number_format($maxScoreValue, 0)
                        : number_format($maxScoreValue, 2))
                    : null;
                $bgClass = '';
                if($rank == 1) $bgClass = 'bg-yellow-50/50';
                elseif($rank == 2) $bgClass = 'bg-gray-50/50';
                elseif($rank == 3) $bgClass = 'bg-orange-50/50';
                elseif(Auth::id() == $ranking['user']->id) $bgClass = 'bg-primary/5';
                @endphp
                <tr class="bg-white border-b border-dashed border-gray-200 text-grey3 {{ $bgClass }}">
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-3">
                            @if($rank == 1)
                            <div class="relative">
                                <i class="ri-medal-fill text-3xl text-yellow-500"></i>
                                <span
                                    class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-xs font-bold text-white">1</span>
                            </div>
                            @elseif($rank == 2)
                            <div class="relative">
                                <i class="ri-medal-fill text-3xl text-gray-400"></i>
                                <span
                                    class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-xs font-bold text-white">2</span>
                            </div>
                            @elseif($rank == 3)
                            <div class="relative">
                                <i class="ri-medal-fill text-3xl text-orange-500"></i>
                                <span
                                    class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-xs font-bold text-white">3</span>
                            </div>
                            @else
                            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-gray-600">{{ $rank }}</span>
                            </div>
                            @endif
                        </div>
                    </td>

                    <td class="py-3 px-4">
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($ranking['user']->name) }}&background=444444&color=fff"
                                class="w-10 h-10 rounded-full">
                            <div>
                                <div class="flex items-center gap-2">
                                    <p
                                        class="font-semibold capitalize {{ Auth::id() == $ranking['user']->id ? 'text-dark' : '' }}">
                                        {{ $ranking['user']->name }}
                                    </p>
                                    @if(Auth::id() == $ranking['user']->id)
                                    <span class="text-xs bg-primary text-white px-2 py-0.5 rounded-md">Anda</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500">{{ $ranking['user']->email }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center items-center">
                            <span class="text-xl font-semibold">{{ $rawScoreDisplay }}</span>
                            @if($maxScoreDisplay)
                            <span class="text-sm text-gray-500 ml-1">/ {{ $maxScoreDisplay }}</span>
                            @endif
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <span class="flex flex-col items-center">
                                @if($ranking['finished_at'])
                                @php
                                $finishedTime = \Carbon\Carbon::parse($ranking['finished_at']);
                                @endphp
                                <p class="font-medium">{{ $finishedTime->format('H:i') }}</p>
                                <p class="text-sm text-gray-500">WIB</p>
                                @else
                                <p class="font-medium text-gray-400">-</p>
                                <p class="text-sm text-gray-400">Belum selesai</p>
                                @endif
                            </span>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <span class="flex flex-col items-center">
                                <p>{{ \Carbon\Carbon::parse($ranking['finished_at'])->format('d M Y') }}</p>
                                <p class="text-sm text-gray-500">{{
                                    \Carbon\Carbon::parse($ranking['finished_at'])->format('H:i') }} WIB</p>
                            </span>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex justify-center items-center">
                            @if($ranking['is_passed'])
                            <span
                                class="flex items-center gap-1 border border-green bg-green-light px-4 py-1 rounded-lg">
                                <i class="ri-checkbox-circle-fill text-green text-lg"></i>
                                <span class="text-green">
                                    Lulus
                                </span>
                            </span>
                            @else
                            <span class="flex items-center gap-1 border border-red bg-red-light px-4 py-1 rounded-lg">
                                <i class="ri-close-circle-fill text-red text-lg"></i>
                                <span class="text-red">
                                    Belum Lulus
                                </span>
                            </span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class="ri-trophy-line text-4xl text-gray-300 mb-2"></i>
                            <p>Belum ada peserta yang menyelesaikan tryout ini</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($rankings->count() > 0)
    <div class="flex justify-between items-center mt-4">
        <p class="text-gray-500 text-sm">
            Menampilkan {{ $rankings->count() }} peserta
        </p>
    </div>
    @endif
</div>
@endsection

@section('styles')
<style>
    /* Color definitions */
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
