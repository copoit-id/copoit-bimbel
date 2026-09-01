@extends('admin.layout.admin')
@section('title', 'Leaderboard Detail')
@section('content')

    <div class="flex justify-between items-center">
        <x-breadcrumb>
            <x-slot name="items">
                <x-breadcrumb-item href="{{ route('admin.leaderboard.index') }}" title="Leaderboard" />
                <x-breadcrumb-item href="" title="Peringkat Tryout" />
            </x-slot>
        </x-breadcrumb>
        <div class="flex gap-2">
            <a href="{{ route('admin.leaderboard.export-excel', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id] + request()->only(['destination_category_id', 'destination_subcategory_id'])) }}"
                class="flex items-center gap-2 px-4 py-2 bg-green text-white rounded-lg hover:bg-green-700">
                <i class="ri-file-excel-line"></i>
                Export Excel
            </a>
            <a href="{{ route('admin.leaderboard.export-pdf', ['package_id' => $package->package_id, 'tryout_id' => $tryout->tryout_id] + request()->only(['destination_category_id', 'destination_subcategory_id'])) }}"
                class="flex items-center gap-2 px-4 py-2 bg-red text-white rounded-lg hover:bg-red-700">
                <i class="ri-file-pdf-line"></i>
                Export PDF
            </a>
        </div>
    </div>
    <x-page-desc title="Peringkat - {{ $tryout->name }}"></x-page-desc>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg border border-border">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Peserta</p>
                    <p class="text-2xl font-bold text-dark">{{ $statistics['total_participants'] }}</p>
                </div>
                <i class="ri-group-line text-3xl text-dark"></i>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-border">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Rata-rata {{ $statistics['score_label'] }}</p>
                    <p class="text-2xl font-bold text-dark">{{ $statistics['average_score_display'] }}</p>
                </div>
                <i class="ri-bar-chart-line text-3xl text-dark"></i>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-border">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Nilai Tertinggi</p>
                    <p class="text-2xl font-bold text-dark">{{ $statistics['highest_score_display'] }}</p>
                </div>
                <i class="ri-trophy-line text-3xl text-dark"></i>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-border">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Tingkat Kelulusan</p>
                    <p class="text-2xl font-bold text-dark">{{ $statistics['pass_rate'] }}%</p>
                </div>
                <i class="ri-check-double-line text-3xl text-dark"></i>
            </div>
        </div>
    </div>

    @if($podiumRankings->isNotEmpty())
        <section class="leaderboard-podium mb-6" x-data="{ isPodiumVisible: true }" :class="{ 'leaderboard-podium--collapsed': !isPodiumVisible }">
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

    <div class="package-bimbel bg-white p-8 rounded-lg border border-border mt-6">
        <div class="flex flex-col gap-4 mb-4">
            @if($destinationCategories->isEmpty())
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
                    <div>
                        <p class="font-semibold text-amber-800">Tujuan / instansi peserta belum dibuat.</p>
                        <p class="text-sm text-amber-700">Filter leaderboard memakai master Tujuan / Instansi, bukan kategori materi.</p>
                    </div>
                    @if(\Illuminate\Support\Facades\Route::has('admin.participant-destination-categories.index'))
                        <a href="{{ route('admin.participant-destination-categories.index') }}"
                            class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 text-sm font-semibold">
                            Kelola Tujuan / Instansi
                        </a>
                    @endif
                </div>
            @else
                <form method="GET" action="{{ route('admin.leaderboard.show', [$package->package_id, $tryout->tryout_id]) }}"
                    class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tujuan / Instansi</label>
                        <select name="destination_category_id" id="destination-category-filter"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Semua Instansi</option>
                            @foreach($destinationCategories as $category)
                                <option value="{{ $category->id }}" @selected((int) ($destinationFilter['category_id'] ?? 0) === (int) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Sub Tujuan</label>
                        <select name="destination_subcategory_id" id="destination-subcategory-filter"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Semua Sub Tujuan</option>
                            @foreach($destinationCategories as $category)
                                @foreach($category->activeChildren as $child)
                                    <option value="{{ $child->id }}" data-parent="{{ $category->id }}"
                                        @selected((int) ($destinationFilter['subcategory_id'] ?? 0) === (int) $child->id)>
                                        {{ $child->name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2 flex items-end gap-2">
                        <button type="submit"
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                            Terapkan Filter
                        </button>
                        <a href="{{ route('admin.leaderboard.show', [$package->package_id, $tryout->tryout_id]) }}"
                            class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50">
                            Reset
                        </a>
                    </div>
                </form>
            @endif

            <div class="flex items-center gap-2">
                <div class="relative">
                    <input type="text" placeholder="Cari peserta..."
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
                </div>
                <select
                    class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">Semua Skor</option>
                    <option value="90-100">90-100</option>
                    <option value="80-89">80-89</option>
                    <option value="70-79">70-79</option>
                    <option value="<70">
                        < 70</option>
                </select>
            </div>
        </div>

        <div class="relative overflow-x-auto mt-4">
            <table class="w-full text-left rtl:text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Peringkat</th>
                        <th scope="col" class="px-6 py-3">Peserta</th>
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
                                <th scope="col" class="px-6 py-3 text-center">Score {{ $alias }}</th>
                            @endforeach
                        @endif
                        <th scope="col" class="px-6 py-3 text-center">{{ $hasMultipleSubtests ? 'Final Score' : 'Skor' }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-center">Waktu Selesai</th>
                        <th scope="col" class="px-6 py-3 text-center">Tanggal</th>
                        <th scope="col" class="px-6 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rankings as $index => $ranking)
                        @php
                            $rank = ($rankings->currentPage() - 1) * $rankings->perPage() + $index + 1;
                            $rawScoreValue = (float) ($ranking->raw_score ?? 0);
                            $maxScoreValue = (float) ($ranking->max_score ?? 0);
                            $rawScore = $ranking->display_score['formatted'] ?? (abs($rawScoreValue - round($rawScoreValue)) < 0.01
                                ? number_format($rawScoreValue, 0)
                                : number_format($rawScoreValue, 2));
                            $maxScore = $ranking->display_score['formatted_maximum'] ?? (abs($maxScoreValue - round($maxScoreValue)) < 0.01
                                ? number_format($maxScoreValue, 0)
                                : number_format($maxScoreValue, 2));
                            $bgClass = '';
                            if ($rank == 1)
                                $bgClass = 'bg-yellow-50/50';
                            elseif ($rank == 2)
                                $bgClass = 'bg-gray-50/50';
                            elseif ($rank == 3)
                                $bgClass = 'bg-orange-50/50';
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
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($ranking->user->name ?? 'Unknown User') }}&background=444444&color=fff"
                                        class="w-10 h-10 rounded-full">
                                    <div>
                                        <p class="font-medium">{{ $ranking->user->name ?? 'Unknown User' }}</p>
                                        <p class="text-md text-gray-500">{{ $ranking->user->email ?? 'No Email' }}</p>
                                        <p class="text-xs text-gray-400">
                                            {{ $ranking->user?->participant_destination_display_name ?? 'Tujuan belum dipilih' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            @if($hasMultipleSubtests)
                                @foreach($tryout->tryoutDetails->sortBy('tryout_detail_id') as $subtest)
                                    @php
                                        $subscoreValue = (float) ($ranking->subtest_scores[$subtest->tryout_detail_id] ?? 0);
                                        $subscore = $ranking->display_subtest_scores[$subtest->tryout_detail_id]['formatted'] ?? (abs($subscoreValue - round($subscoreValue)) < 0.01
                                            ? number_format($subscoreValue, 0)
                                            : number_format($subscoreValue, 2));
                                    @endphp
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm font-semibold text-gray-800">{{ $subscore }}</span>
                                    </td>
                                @endforeach
                            @endif
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center items-center">
                                    <span class="text-sm font-semibold text-gray-800">{{ $rawScore }}</span>
                                    @if($maxScore > 0)
                                        <span class="ml-1 text-sm text-gray-500">/ {{ $maxScore }}</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex justify-center">
                                    <span class="flex flex-col items-center">
                                        @if($ranking->finished_at)
                                            @php
                                                $finishedTime = \Carbon\Carbon::parse($ranking->finished_at);
                                                $startTime = \Carbon\Carbon::parse($ranking->started_at);
                                                $duration = $startTime->diffForHumans($finishedTime, true);
                                            @endphp
                                            <p class="font-medium">{{ $finishedTime->format('H:i') }}</p>
                                            <p class="text-sm text-gray-500">{{ $duration }}</p>
                                        @else
                                            <p class="font-medium text-gray-400">-</p>
                                            <p class="text-sm text-gray-400">Belum selesai</p>
                                        @endif
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex justify-center">
                                    <span class="flex flex-col items-start">
                                        <p>{{ $ranking->created_at->format('d M Y') }}</p>
                                        <p class="text-sm text-gray-500">{{ $ranking->created_at->format('H:i') }} WIB</p>
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex justify-center items-center">
                                    @if($ranking->is_passed)
                                        <span
                                            class="px-4 py-1 border border-green-700 bg-green-100 text-green-700 rounded-full text-sm">Lulus</span>
                                    @else
                                        <span
                                            class="px-4 py-1 border border-red-700 bg-red-100 text-red-700 rounded-full text-sm">Gagal</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $hasMultipleSubtests ? 6 + $tryout->tryoutDetails->count() : 6 }}"
                                class="px-6 py-8 text-center text-gray-500">
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

        @if($rankings->hasPages())
            <div class="flex justify-between items-center mt-4">
                <p class="text-gray-500 text-sm">
                    Menampilkan {{ $rankings->firstItem() ?? 0 }}-{{ $rankings->lastItem() ?? 0 }} dari {{ $rankings->total() }}
                    peserta
                </p>
                <div class="flex items-center gap-2">
                    {{ $rankings->links() }}
                </div>
            </div>
        @else
            <div class="flex justify-between items-center mt-4">
                <p class="text-gray-500 text-sm">
                    Menampilkan {{ $rankings->count() }} dari {{ $statistics['total_participants'] }} peserta
                </p>
            </div>
        @endif
    </div>

@endsection

@section('styles')
<style>
    .leaderboard-podium { position:relative; isolation:isolate; overflow:hidden; border-radius:1rem; padding:1.1rem clamp(1.5rem,7vw,8rem) 0; color:#fff; background:radial-gradient(circle at 8% -15%,rgba(255,255,255,.25),transparent 32%),radial-gradient(circle at 100% 100%,rgba(77,190,231,.22),transparent 42%),linear-gradient(135deg,color-mix(in srgb,var(--color-primary,#1c3259) 84%,#fff),color-mix(in srgb,var(--color-primary,#1c3259) 94%,#3d96c2)); transition:padding-bottom .3s ease; }
    .leaderboard-podium::before { position:absolute; inset:0; z-index:-1; content:""; opacity:.5; background:linear-gradient(125deg,transparent 0 48%,rgba(255,255,255,.08) 48% 49%,transparent 49% 100%),linear-gradient(125deg,transparent 0 61%,rgba(255,255,255,.08) 61% 62%,transparent 62% 100%); }
    .leaderboard-podium__heading { position:absolute; top:1.1rem; left:1.25rem; z-index:2; display:flex; align-items:center; gap:.65rem; margin:0; }
    .leaderboard-podium__heading h2 { font-size:.875rem; font-weight:700; line-height:1.1; }
    .leaderboard-podium__heading p { margin-top:.15rem; font-size:.6875rem; color:rgba(255,255,255,.76); }
    .leaderboard-podium__trophy { display:flex; height:2rem; width:2rem; align-items:center; justify-content:center; border-radius:.6rem; background:rgba(255,255,255,.14); color:#ffe08a; }
    .leaderboard-podium__toggle { position:absolute; top:1.1rem; right:1.25rem; z-index:2; display:inline-flex; align-items:center; gap:.4rem; border:0; border-radius:.5rem; padding:.45rem .6rem; color:#fff; background:rgba(255,255,255,.14); font-size:.6875rem; font-weight:700; line-height:1; transition:background-color .2s ease; }
    .leaderboard-podium__toggle:hover { background:rgba(255,255,255,.22); }
    .leaderboard-podium__toggle:focus-visible { outline:2px solid rgba(255,255,255,.8); outline-offset:2px; }
    .leaderboard-podium--collapsed { min-height:4.25rem; padding-bottom:1.1rem; }
    .leaderboard-podium__content { display:grid; grid-template-rows:1fr; opacity:1; transition:grid-template-rows .32s cubic-bezier(.4,0,.2,1),opacity .2s ease; }
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
    .leaderboard-podium__block { display:flex; height:6.5rem; align-items:center; justify-content:center; border-radius:.65rem .65rem 0 0; background:linear-gradient(135deg,#dce3e9,#91a1b0); }
    .leaderboard-podium__block span { font-size:2.5rem; font-weight:800; line-height:1; }
    .leaderboard-podium__entry--1 .leaderboard-podium__block { height:8.5rem; background:linear-gradient(135deg,#f4d67c,#bd881e); }
    .leaderboard-podium__entry--3 .leaderboard-podium__block { height:4.6rem; background:linear-gradient(135deg,#dfae89,#9b613d); }
    @media (max-width:420px) { .leaderboard-podium { padding:1.1rem 1rem 0; } .leaderboard-podium__heading { left:1rem; } .leaderboard-podium__toggle { right:1rem; } .leaderboard-podium__stage { gap:.3rem; padding-top:2.35rem; } .leaderboard-podium__name { font-size:.8125rem; } .leaderboard-podium__score { padding:.28rem .5rem; font-size:.75rem; } .leaderboard-podium__block span { font-size:2.05rem; } }
</style>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const categorySelect = document.getElementById('destination-category-filter');
            const subcategorySelect = document.getElementById('destination-subcategory-filter');
            const subcategoryOptions = Array.from(subcategorySelect?.querySelectorAll('option[data-parent]') || []);

            const syncSubcategories = () => {
                if (!categorySelect || !subcategorySelect) return;

                const selectedCategory = categorySelect.value;
                subcategoryOptions.forEach((option) => {
                    const isVisible = selectedCategory && option.dataset.parent === selectedCategory;
                    option.hidden = !isVisible;
                    option.disabled = !isVisible;
                });

                const selectedOption = subcategorySelect.selectedOptions[0];
                if (selectedOption?.dataset.parent && selectedOption.dataset.parent !== selectedCategory) {
                    subcategorySelect.value = '';
                }
            };

            categorySelect?.addEventListener('change', syncSubcategories);
            syncSubcategories();
        });
    </script>
@endsection
