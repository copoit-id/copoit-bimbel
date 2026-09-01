@extends('user.layout.new-user')
@section('title', 'Ranking Tryout')
@section('container_width', 'max-w-7xl')
@section('content')
    @php
        $packageRouteId = $packageRouteId ?? ($package->package_id ?? 'free');
        $showPassingGrade = $tryout->shouldShowPassingGrade();
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
                        <a href="{{ route('user.package.tryout.ranking', [$packageRouteId, $tryout->tryout_id, 'tab' => 'profile', 'profile_choice' => $selectedProfileChoice]) }}"
                            class="px-4 py-2 rounded-lg text-sm font-semibold border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 transition">
                            Ranking Sesuai Profil
                        </a>
                    @else
                        <a href="{{ route('user.package.tryout.ranking', [$packageRouteId, $tryout->tryout_id, 'tab' => 'profile', 'profile_choice' => $selectedProfileChoice]) }}"
                            class="px-4 py-2 rounded-lg text-sm font-semibold border transition {{ $activeRankingTab === 'profile' ? 'bg-primary text-white border-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-primary hover:text-primary' }}">
                            Sesuai Profil Saya
                            <span class="ml-1 text-xs opacity-80">({{ $profileRankings->count() }})</span>
                        </a>
                    @endif
                </div>

                @if($activeRankingTab === 'profile')
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach($profileChoices as $choice => $profileChoice)
                            @php
                                $isSelectedChoice = $selectedProfileChoice === $choice;
                            @endphp
                            @if($profileChoice['needs_completion'])
                                <a href="{{ route('user.profile.index') }}" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100">
                                    Pilihan {{ $choice }} · Lengkapi Profil
                                </a>
                            @else
                                <a href="{{ route('user.package.tryout.ranking', [$packageRouteId, $tryout->tryout_id, 'tab' => 'profile', 'profile_choice' => $choice]) }}"
                                    class="rounded-lg border px-3 py-2 text-xs font-semibold transition {{ $isSelectedChoice ? 'border-primary bg-primary text-white' : 'border-gray-200 bg-white text-gray-600 hover:border-primary hover:text-primary' }}">
                                    Pilihan {{ $choice }}
                                    <span class="ml-1 font-normal opacity-80">({{ $profileRankingsByChoice->get($choice, collect())->count() }})</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @elseif($profileNeedsCompletion)
                    <div class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        Lengkapi instansi/prodi tujuan di profil untuk membuka ranking berdasarkan profil.
                    </div>
                @endif
            </div>

            @if($activeRankingTab === 'profile' && $profileDestinationLabel)
                <div class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-500">
                    Filter Pilihan {{ $selectedProfileChoice }}: <span class="font-semibold text-gray-700">{{ $profileDestinationLabel }}</span>
                </div>
            @endif
        </section>

        <!-- Statistics Cards -->
        @if($rankingSummary->isNotEmpty())
            <div class="grid grid-cols-2 gap-4 {{ $showPassingGrade ? 'md:grid-cols-6' : 'md:grid-cols-5' }}">
                <div class="min-h-[116px] rounded-lg border border-border bg-white p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Peserta</p>
                            <p class="text-2xl font-bold text-dark">{{ $rankingStatistics['total_participants'] }}</p>
                        </div>
                        <i class="ri-group-line text-3xl text-dark"></i>
                    </div>
                </div>
                <div class="min-h-[116px] rounded-lg border border-border bg-white p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Rata-rata {{ $rankingStatistics['score_label'] }}</p>
                            <p class="text-2xl font-bold text-dark">{{ $rankingStatistics['average_score_display'] }}</p>
                        </div>
                        <i class="ri-bar-chart-line text-3xl text-dark"></i>
                    </div>
                </div>
                <div class="min-h-[116px] rounded-lg border border-border bg-white p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Skor Tertinggi</p>
                            <p class="text-2xl font-bold text-dark">
                                {{ $rankingStatistics['highest_score_display'] }}
                                @if($rankingStatistics['highest_maximum_display'])
                                    <span class="text-base font-semibold text-gray-500">/ {{ $rankingStatistics['highest_maximum_display'] }}</span>
                                @endif
                            </p>
                        </div>
                        <i class="ri-trophy-line text-3xl text-dark"></i>
                    </div>
                </div>
                @if($showPassingGrade)
                    <div class="min-h-[116px] rounded-lg border border-border bg-white p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Tingkat Kelulusan</p>
                                <p class="text-2xl font-bold text-dark">{{ number_format($rankingStatistics['pass_rate'], 1) }}%</p>
                            </div>
                            <i class="ri-check-double-line text-3xl text-dark"></i>
                        </div>
                    </div>
                @endif
                <div class="min-h-[116px] rounded-lg border border-border bg-white p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Ranking Saya</p>
                            @if($myRanking)
                                <p class="text-2xl font-bold text-dark">#{{ $myRanking['rank'] }}</p>
                                <p class="text-xs text-gray-500">dari {{ $myRanking['total'] }} peserta</p>
                            @else
                                <p class="mt-1 text-sm font-semibold text-gray-500">Belum masuk ranking</p>
                            @endif
                        </div>
                        <i class="ri-user-star-line text-3xl text-dark"></i>
                    </div>
                </div>
                <div class="min-h-[116px] rounded-lg border border-border bg-white p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Nilai Saya</p>
                            @if($rankingStatistics['my_score'])
                                <p class="text-2xl font-bold text-dark">{{ $rankingStatistics['my_score']['display_score']['formatted'] }}</p>
                                @if($rankingStatistics['show_score_maximum'])
                                    <p class="text-xs text-gray-500">/ {{ $rankingStatistics['my_score']['display_score']['formatted_maximum'] }}</p>
                                @endif
                            @else
                                <p class="mt-1 text-sm font-semibold text-gray-500">Belum ada nilai</p>
                            @endif
                        </div>
                        <i class="ri-award-line text-3xl text-dark"></i>
                    </div>
                </div>
            </div>
        @endif

        @if($podiumRankings->isNotEmpty())
            <section class="leaderboard-podium" x-data="{ isPodiumVisible: true }" :class="{ 'leaderboard-podium--collapsed': !isPodiumVisible }">
                <button type="button" class="leaderboard-podium__toggle" @click="isPodiumVisible = !isPodiumVisible" :aria-expanded="isPodiumVisible.toString()">
                    <span x-text="isPodiumVisible ? 'Sembunyikan' : 'Tampilkan'"></span>
                    <i :class="isPodiumVisible ? 'ri-eye-off-line' : 'ri-eye-line'" aria-hidden="true"></i>
                </button>
                <div class="leaderboard-podium__content" :aria-hidden="(!isPodiumVisible).toString()">
                    <div class="leaderboard-podium__heading">
                        <span class="leaderboard-podium__trophy"><i class="ri-trophy-fill"></i></span>
                        <div><h2>Podium Peringkat</h2><p>Tiga nilai final tertinggi</p></div>
                    </div>
                    <div class="leaderboard-podium__stage">
                    @foreach([2, 1, 3] as $podiumRank)
                        @php $podium = $podiumRankings->get($podiumRank); @endphp
                        @if($podium)
                            <article class="leaderboard-podium__entry leaderboard-podium__entry--{{ $podiumRank }}">
                                <span class="leaderboard-podium__medal"><i class="ri-medal-fill"></i></span>
                                <p class="leaderboard-podium__name" title="{{ $podium['name'] }}">{{ $podium['name'] }}</p>
                                <p class="leaderboard-podium__score">{{ $podium['score'] }}@if($podium['maximum'])<span>/ {{ $podium['maximum'] }}</span>@endif</p>
                                <div class="leaderboard-podium__block" aria-label="Peringkat {{ $podium['rank'] }}">
                                    <span>{{ $podium['rank'] }}</span>
                                </div>
                            </article>
                        @endif
                    @endforeach
                    </div>
                </div>
            </section>
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
                                <th scope="col" class="px-3 py-2.5 text-center whitespace-nowrap">{{ $subtest->display_name }}</th>
                            @endforeach
                        @endif
                        <th scope="col" class="px-3 py-2.5 text-center whitespace-nowrap">{{ $hasMultipleSubtests ? 'Final Score' : 'Skor' }}
                        </th>
                        <th scope="col" class="px-3 py-2.5 text-center whitespace-nowrap">Selesai</th>
                        @if($showPassingGrade)
                            <th scope="col" class="px-3 py-2.5 text-center">Status</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($rankings as $index => $ranking)
                                @php
                                    $rank = ($rankings->firstItem() ?? 1) + $index;
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
                                    $rowClass = match ($rank) {
                                        1 => '!bg-amber-50/70 hover:!bg-amber-50',
                                        2 => '!bg-slate-50 hover:!bg-slate-100/70',
                                        3 => '!bg-orange-50/60 hover:!bg-orange-50',
                                        default => Auth::id() == $ranking['user']->id
                                            ? '!bg-primary/5 hover:!bg-primary/10'
                                            : 'bg-white hover:bg-gray-50',
                                    };
                                    $rankBadgeClass = match ($rank) {
                                        1 => 'border-amber-300 bg-amber-100 text-amber-700',
                                        2 => 'border-slate-300 bg-slate-100 text-slate-600',
                                        3 => 'border-orange-300 bg-orange-100 text-orange-700',
                                        default => 'border-gray-200 bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <tr class="border-b border-dashed border-gray-200 text-gray-700 transition-colors {{ $rowClass }}">
                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center gap-2">
                                            @if($rank <= 3)
                                                <div class="relative flex h-9 w-9 items-center justify-center rounded-full border {{ $rankBadgeClass }}" title="Peringkat {{ $rank }}">
                                                    <i class="ri-medal-fill text-xl" aria-hidden="true"></i>
                                                    <span class="absolute text-[10px] font-bold text-white">{{ $rank }}</span>
                                                </div>
                                            @else
                                                <div class="flex h-9 w-9 items-center justify-center rounded-full border {{ $rankBadgeClass }}">
                                                    <span class="text-xs font-semibold">{{ $rank }}</span>
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
                                                <span class="text-sm font-semibold">{{ $subscoreDisplay }}</span>
                                            </td>
                                        @endforeach
                                    @endif
                                    <td class="px-3 py-2.5 text-center">
                                        <div class="flex justify-center items-center">
                                            <span class="text-sm font-semibold">{{ $rawScoreDisplay }}</span>
                                            @if($rankingStatistics['show_score_maximum'] && $maxScoreDisplay)
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

                                    @if($showPassingGrade)
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
                                    @endif
                                </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($showPassingGrade ? 5 : 4) + ($hasMultipleSubtests ? $tryout->tryoutDetails->count() : 0) }}" class="px-3 py-8 text-center text-gray-500">
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

        @if($rankingSummary->isNotEmpty())
            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500">
                    Menampilkan {{ $rankings->firstItem() }}–{{ $rankings->lastItem() }} dari {{ $rankings->total() }} peserta
                </p>
                <div>
                    {{ $rankings->onEachSide(1)->links() }}
                </div>
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

        .leaderboard-podium {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            border-radius: 1rem;
            padding: 1.1rem clamp(1.5rem, 7vw, 8rem) 0;
            color: #fff;
            background: radial-gradient(circle at 8% -15%, rgba(255,255,255,.25), transparent 32%), radial-gradient(circle at 100% 100%, rgba(77,190,231,.22), transparent 42%), linear-gradient(135deg, color-mix(in srgb, var(--color-primary, #1c3259) 84%, #fff), color-mix(in srgb, var(--color-primary, #1c3259) 94%, #3d96c2));
            transition: padding-bottom .3s ease;
        }
        .leaderboard-podium::before { position:absolute; inset:0; z-index:-1; content:""; opacity:.5; background:linear-gradient(125deg, transparent 0 48%, rgba(255,255,255,.08) 48% 49%, transparent 49% 100%), linear-gradient(125deg, transparent 0 61%, rgba(255,255,255,.08) 61% 62%, transparent 62% 100%); }
        .leaderboard-podium__heading { position:absolute; top:1.1rem; left:1.25rem; z-index:2; display:flex; align-items:center; gap:.65rem; margin:0; }
        .leaderboard-podium__heading h2 { font-size:.875rem; font-weight:700; line-height:1.1; }
        .leaderboard-podium__heading p { margin-top:.15rem; font-size:.6875rem; color:rgba(255,255,255,.76); }
        .leaderboard-podium__trophy { display:flex; height:2rem; width:2rem; align-items:center; justify-content:center; border-radius:.6rem; background:rgba(255,255,255,.14); color:#ffe08a; }
        .leaderboard-podium__toggle { position:absolute; top:1.1rem; right:1.25rem; z-index:2; display:inline-flex; align-items:center; gap:.4rem; border:0; border-radius:.5rem; padding:.45rem .6rem; color:#fff; background:rgba(255,255,255,.14); font-size:.6875rem; font-weight:700; line-height:1; transition:background-color .2s ease; }
        .leaderboard-podium__toggle:hover { background:rgba(255,255,255,.22); }
        .leaderboard-podium__toggle:focus-visible { outline:2px solid rgba(255,255,255,.8); outline-offset:2px; }
        .leaderboard-podium--collapsed { min-height:4.25rem; padding-bottom:1.1rem; }
        .leaderboard-podium__content { display:grid; grid-template-rows:1fr; opacity:1; transition:grid-template-rows .32s cubic-bezier(.4,0,.2,1), opacity .2s ease; }
        .leaderboard-podium__content > div { min-height:0; overflow:hidden; }
        .leaderboard-podium--collapsed .leaderboard-podium__content { grid-template-rows:0fr; opacity:0; pointer-events:none; }
        .leaderboard-podium__stage { position:relative; display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); align-items:end; gap:.6rem; padding-top:2.35rem; }
        .leaderboard-podium__entry { min-width:0; text-align:center; }
        .leaderboard-podium__entry:only-child { grid-column:2; }
        .leaderboard-podium__medal { display:flex; width:2.45rem; height:2.45rem; margin:0 auto .35rem; align-items:center; justify-content:center; border-radius:999px; background:rgba(255,255,255,.15); font-size:1.3rem; }
        .leaderboard-podium__entry--1 .leaderboard-podium__medal { color:#ffda62; transform:scale(1.14); }
        .leaderboard-podium__entry--2 .leaderboard-podium__medal { color:#e7edf4; }
        .leaderboard-podium__entry--3 .leaderboard-podium__medal { color:#f3b17d; }
        .leaderboard-podium__name { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:1rem; font-weight:700; letter-spacing:-.015em; }
        .leaderboard-podium__score { display:inline-block; margin:.38rem 0 .9rem; border-radius:.55rem; padding:.32rem .68rem; background:rgba(255,255,255,.78); color:#26374f; font-size:.8125rem; font-weight:700; }
        .leaderboard-podium__score span { color:rgba(38,55,79,.68); font-weight:500; }
        .leaderboard-podium__block { display:flex; height:6.5rem; align-items:center; justify-content:center; border-radius:.65rem .65rem 0 0; background:linear-gradient(135deg, #dce3e9, #91a1b0); }
        .leaderboard-podium__block span { font-size:2.5rem; font-weight:800; line-height:1; }
        .leaderboard-podium__entry--1 .leaderboard-podium__block { height:8.5rem; background:linear-gradient(135deg, #f4d67c, #bd881e); }
        .leaderboard-podium__entry--3 .leaderboard-podium__block { height:4.6rem; background:linear-gradient(135deg, #dfae89, #9b613d); }
        @media (max-width: 420px) { .leaderboard-podium { padding:1.1rem 1rem 0; } .leaderboard-podium__heading { left:1rem; } .leaderboard-podium__toggle { right:1rem; } .leaderboard-podium__stage { gap:.3rem; padding-top:2.35rem; } .leaderboard-podium__name { font-size:.8125rem; } .leaderboard-podium__score { padding:.28rem .5rem; font-size:.75rem; } .leaderboard-podium__block span { font-size:2.05rem; } }
    </style>
@endsection
