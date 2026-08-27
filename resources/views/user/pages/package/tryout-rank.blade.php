@extends('user.layout.new-user')
@section('title', 'Ranking Tryout')
@section('container_width', 'max-w-7xl')
@section('content')
    @php
        $packageRouteId = $packageRouteId ?? ($package->package_id ?? 'free');
        $usesIrtScoreScale = $tryout->requiresIrtScoring();
    @endphp
    <div class="package-bimbel space-y-6">
        <section class="rounded-2xl border border-border bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <i class="ri-trophy-line text-xl"></i>
                    </span>
                    <div class="min-w-0">
                        <h1 class="truncate text-xl font-bold text-dark sm:text-2xl">Ranking - {{ $tryout->name }}</h1>
                        <p class="mt-1 text-sm text-gray-500">Leaderboard peserta tryout</p>
                    </div>
                </div>
                <x-ui.button :href="route('user.tryout.result', [$packageRouteId, $tryout->tryout_id])" variant="outline" size="md" icon="ri-arrow-left-line">
                    Kembali ke Hasil
                </x-ui.button>
            </div>

            <div class="mt-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('user.package.tryout.ranking', [$packageRouteId, $tryout->tryout_id]) }}"
                        class="px-4 py-2 rounded-lg text-sm font-semibold border transition {{ $activeRankingTab === 'all' ? 'bg-primary text-white border-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-primary hover:text-primary' }}">
                        Semua Peserta
                        <span class="ml-1 text-xs opacity-80">({{ $allRankings->count() }})</span>
                    </a>
                    @if($profileNeedsCompletion)
                        <a href="{{ route('user.package.tryout.ranking', [$packageRouteId, $tryout->tryout_id, 'tab' => 'profile']) }}"
                            class="px-4 py-2 rounded-lg text-sm font-semibold border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 transition">
                            Ranking Sesuai Profil
                        </a>
                    @else
                        <a href="{{ route('user.package.tryout.ranking', [$packageRouteId, $tryout->tryout_id, 'tab' => 'profile']) }}"
                            class="px-4 py-2 rounded-lg text-sm font-semibold border transition {{ $activeRankingTab === 'profile' ? 'bg-primary text-white border-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-primary hover:text-primary' }}">
                            Sesuai Profil Saya
                            <span class="ml-1 text-xs opacity-80">({{ $profileRankings->count() }})</span>
                        </a>
                    @endif
                </div>

                @if($activeRankingTab === 'profile' && $profileDestinationLabel)
                    <div class="text-sm text-gray-500">
                        Filter: <span class="font-semibold text-gray-700">{{ $profileDestinationLabel }}</span>
                    </div>
                @elseif($profileNeedsCompletion)
                    <div class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        Lengkapi instansi/prodi tujuan di profil untuk membuka ranking sesuai profil.
                    </div>
                @endif
            </div>
        </section>

        <!-- Statistics Cards -->
        @if($rankings->count() > 0)
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
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
                            <p class="text-sm text-gray-600">Rata-rata {{ $usesIrtScoreScale ? 'Nilai' : 'Skor' }}</p>
                            <p class="text-2xl font-bold text-dark">{{ $usesIrtScoreScale ? number_format($rankings->avg(fn ($ranking) => $ranking['display_score']['value'] ?? 0), 1) : number_format($rankings->avg('raw_score'), 1) }}</p>
                        </div>
                        <i class="ri-bar-chart-line text-3xl text-dark"></i>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-lg border border-border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Skor Tertinggi</p>
                            @php
                                $highestScore = $usesIrtScoreScale
                                    ? $rankings->max(fn ($ranking) => $ranking['display_score']['value'] ?? 0)
                                    : $rankings->max('raw_score');
                                $highestScoreDisplay = $usesIrtScoreScale
                                    ? ($rankings->sortByDesc(fn ($ranking) => $ranking['display_score']['value'] ?? 0)->first()['display_score']['formatted'] ?? '0')
                                    : number_format($highestScore ?? 0, 0);
                                $highestMaxScore = $usesIrtScoreScale
                                    ? ($rankings->first()['display_score']['maximum'] ?? null)
                                    : $rankings->max('max_score');
                            @endphp
                            <p class="text-2xl font-bold text-dark">
                                {{ $highestScoreDisplay }}
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

        <div class="relative overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full min-w-[640px] text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-[11px] uppercase tracking-wide text-gray-600">
                    <tr>
                        <th scope="col" class="px-3 py-2.5">Rank</th>
                        <th scope="col" class="px-3 py-2.5">Peserta</th>
                        @php
                            $hasMultipleSubtests = $tryout->tryoutDetails->count() > 1;
                        @endphp
                        @if($hasMultipleSubtests)
                            @foreach($tryout->tryoutDetails->sortBy('tryout_detail_id') as $subtest)
                                @php
                                    $subtestName = $subtest->type_subtest ?? $subtest->name;
                                    $map = [
                                        'twk' => 'TWK',
                                        'tiu' => 'TIU',
                                        'tkp' => 'TKP',
                                        'penalaran_umum' => 'PU',
                                        'pengetahuan_umum' => 'PPU',
                                        'pengetahuan_kuantitatif' => 'PK',
                                        'pemahaman_bacaan_menulis' => 'PBM',
                                        'literasi_bahasa_indonesia' => 'LBI',
                                        'literasi_bahasa_inggris' => 'LBE',
                                        'penalaran_matematika' => 'PM',
                                        'writing' => 'WT',
                                        'reading' => 'RD',
                                        'listening' => 'LS'
                                    ];
                                    $alias = $map[strtolower($subtestName)] ?? strtoupper(\Illuminate\Support\Str::limit($subtestName, 3, ''));
                                @endphp
                                <th scope="col" class="px-3 py-2.5 text-center whitespace-nowrap">{{ $alias }}</th>
                            @endforeach
                        @endif
                        <th scope="col" class="px-3 py-2.5 text-center whitespace-nowrap">{{ $hasMultipleSubtests ? 'Final Score' : 'Skor' }}
                        </th>
                        <th scope="col" class="px-3 py-2.5 text-center whitespace-nowrap">Selesai</th>
                        <th scope="col" class="px-3 py-2.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rankings as $index => $ranking)
                                @php
                                    $rank = $index + 1;
                                    $rawScoreValue = $ranking['raw_score'] ?? 0;
                                    $maxScoreValue = $ranking['max_score'] ?? null;
                                    $rawScoreDisplay = $ranking['display_score']['formatted'] ?? (abs($rawScoreValue - round($rawScoreValue)) < 0.01
                                        ? number_format($rawScoreValue, 0)
                                        : number_format($rawScoreValue, 2));
                                    $maxScoreDisplay = isset($ranking['display_score'])
                                        ? $ranking['display_score']['formatted_maximum']
                                        : ($maxScoreValue
                                        ? (abs($maxScoreValue - round($maxScoreValue)) < 0.01
                                            ? number_format($maxScoreValue, 0)
                                            : number_format($maxScoreValue, 2))
                                        : null);
                                    $bgClass = '';
                                    if ($rank == 1)
                                        $bgClass = 'bg-yellow-50/50';
                                    elseif ($rank == 2)
                                        $bgClass = 'bg-gray-50/50';
                                    elseif ($rank == 3)
                                        $bgClass = 'bg-orange-50/50';
                                    elseif (Auth::id() == $ranking['user']->id)
                                        $bgClass = 'bg-primary/5';
                                @endphp
                                <tr class="border-b border-dashed border-gray-200 bg-white text-gray-700 {{ $bgClass }}">
                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center gap-2">
                                            @if($rank == 1)
                                                <div class="relative">
                                                    <i class="ri-medal-fill text-2xl text-yellow-500"></i>
                                                    <span
                                                        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-xs font-bold text-white">1</span>
                                                </div>
                                            @elseif($rank == 2)
                                                <div class="relative">
                                                    <i class="ri-medal-fill text-2xl text-gray-400"></i>
                                                    <span
                                                        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-xs font-bold text-white">2</span>
                                                </div>
                                            @elseif($rank == 3)
                                                <div class="relative">
                                                    <i class="ri-medal-fill text-2xl text-orange-500"></i>
                                                    <span
                                                        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-xs font-bold text-white">3</span>
                                                </div>
                                            @else
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100">
                                                    <span class="text-xs font-semibold text-gray-600">{{ $rank }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-3 py-2.5">
                                        <div class="flex min-w-[180px] items-center gap-2">
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($ranking['user']->name) }}&background=444444&color=fff"
                                                class="h-8 w-8 rounded-full" alt="">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <p
                                                        class="truncate text-sm font-semibold capitalize {{ Auth::id() == $ranking['user']->id ? 'text-dark' : '' }}">
                                                        {{ $ranking['user']->name }}
                                                    </p>
                                                    @if(Auth::id() == $ranking['user']->id)
                                                        <span class="rounded bg-primary px-1.5 py-0.5 text-[10px] text-white">Anda</span>
                                                    @endif
                                                </div>
                                                <p class="truncate text-xs text-gray-500">{{ $ranking['user']->email }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    @if($hasMultipleSubtests)
                                        @foreach($tryout->tryoutDetails->sortBy('tryout_detail_id') as $subtest)
                                            @php
                                                $subscoreValue = (float) ($ranking['subtest_scores'][$subtest->tryout_detail_id] ?? 0);
                                                $subscoreDisplay = $ranking['display_subtest_scores'][$subtest->tryout_detail_id]['formatted'] ?? (abs($subscoreValue - round($subscoreValue)) < 0.01
                                                    ? number_format($subscoreValue, 0)
                                                    : number_format($subscoreValue, 2));
                                            @endphp
                                            <td class="px-3 py-2.5 text-center">
                                                <span class="font-semibold">{{ $subscoreDisplay }}</span>
                                            </td>
                                        @endforeach
                                    @endif
                                    <td class="px-3 py-2.5 text-center">
                                        <div class="flex justify-center items-center">
                                            <span class="text-base font-semibold">{{ $rawScoreDisplay }}</span>
                                            @if($maxScoreDisplay)
                                                <span class="ml-1 text-xs text-gray-500">/ {{ $maxScoreDisplay }}</span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-3 py-2.5">
                                        <div class="flex justify-center">
                                            <span class="flex flex-col items-center whitespace-nowrap">
                                                @if($ranking['finished_at'])
                                                    @php
                                                        $finishedTime = \Carbon\Carbon::parse($ranking['finished_at']);
                                                    @endphp
                                                    <p class="text-xs font-medium">{{ $finishedTime->format('d M Y') }}</p>
                                                    <p class="text-[11px] text-gray-500">{{ $finishedTime->format('H:i') }} WIB</p>
                                                @else
                                                    <p class="text-xs font-medium text-gray-400">Belum selesai</p>
                                                @endif
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-3 py-2.5">
                                        <div class="flex justify-center items-center">
                                            @if($ranking['is_passed'])
                                                <span
                                                    class="flex items-center gap-1 rounded-md border border-green bg-green-light px-2 py-1 text-xs">
                                                    <i class="ri-checkbox-circle-fill text-green"></i>
                                                    <span class="text-green whitespace-nowrap">
                                                        Lulus
                                                    </span>
                                                </span>
                                            @else
                                                <span class="flex items-center gap-1 rounded-md border border-red bg-red-light px-2 py-1 text-xs">
                                                    <i class="ri-close-circle-fill text-red"></i>
                                                    <span class="text-red whitespace-nowrap">
                                                        Belum Lulus
                                                    </span>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 5 + ($hasMultipleSubtests ? $tryout->tryoutDetails->count() : 0) }}" class="px-3 py-8 text-center text-gray-500">
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
