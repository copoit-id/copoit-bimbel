@extends('admin.layout.admin')
@section('title', 'Detail Laporan Tryout')
@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;
@endphp
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.laporan.index') }}" title="Laporan Tryout" />
            <x-breadcrumb-item href="" title="Detail Tryout" />
        </x-slot>
    </x-breadcrumb>
    <div class="flex gap-2">
        @if($leaderboardPackageId ?? false)
        <a href="{{ route('admin.leaderboard.show', [$leaderboardPackageId, $tryout->tryout_id]) }}"
            class="flex items-center gap-2 px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition">
            <i class="ri-trophy-line"></i>
            Leaderboard
        </a>
        @endif
        <a href="{{ route('admin.tryout.preview', $tryout->tryout_id) }}"
            class="flex items-center gap-2 px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition">
            <i class="ri-eye-line"></i>
            Preview Tryout
        </a>
        <a href="{{ route('admin.laporan.show.export-excel', $tryout->tryout_id) }}"
            class="flex items-center gap-2 px-4 py-2 bg-green text-white rounded-lg hover:bg-green-700">
            <i class="ri-file-excel-line"></i>
            Excel
        </a>
        <a href="{{ route('admin.laporan.show.export-pdf', $tryout->tryout_id) }}"
            class="flex items-center gap-2 px-4 py-2 bg-red text-white rounded-lg hover:bg-red-700">
            <i class="ri-file-pdf-line"></i>
            PDF
        </a>
    </div>
</div>
<x-page-desc title="{{ $tryout->name }}"></x-page-desc>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
    <div class="bg-white border border-border rounded-lg p-5">
        <p class="text-sm text-gray-500">Subtest</p>
        <p class="text-3xl font-bold text-gray-900">{{ $statistics['total_subtests'] }}</p>
        <p class="text-sm text-gray-400">{{ $statistics['total_questions'] }} soal</p>
    </div>
    <div class="bg-white border border-border rounded-lg p-5">
        <p class="text-sm text-gray-500">Peserta</p>
        <p class="text-3xl font-bold text-gray-900">{{ $statistics['total_participants'] }}</p>
        <p class="text-sm text-gray-400">{{ $statistics['completion_rate'] }}% selesai</p>
    </div>
    <div class="bg-white border border-border rounded-lg p-5">
        <p class="text-sm text-gray-500">Rata-rata skor</p>
        <p class="text-3xl font-bold text-gray-900">{{ $statistics['average_score'] }}</p>
        <p class="text-sm text-gray-400">Tertinggi {{ $statistics['highest_score'] }}</p>
    </div>
</div>

<div class="bg-white border border-border rounded-xl p-6 mt-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-gray-400">Peserta Tryout</p>
            <h3 class="text-lg font-semibold text-gray-900">Ringkasan Pengerjaan</h3>
        </div>
        <div class="relative w-full lg:w-80">
            <input type="text" id="participant-search" placeholder="Cari peserta berdasarkan nama..."
                class="pl-11 pr-4 py-2.5 w-full border border-gray-200 rounded-full bg-gray-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/10 text-sm">
            <i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>
    </div>

    <div class="mt-6 space-y-4" id="participant-list">
        @forelse($participants as $index => $participant)
        @php
            $user = $participant['user'];
            $lastFinished = $participant['last_finished'] ? Carbon::parse($participant['last_finished']) : null;
        @endphp
        <div class="rounded-2xl border border-border bg-white transition data-[hidden=true]:hidden"
            data-participant-card data-name="{{ Str::lower($user->name ?? '') }}">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between px-5 py-5">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'User') }}&background=444444&color=fff"
                            class="w-14 h-14 rounded-full ring-2 ring-primary/10" alt="{{ $user->name ?? 'User' }}">
                        <span class="absolute -bottom-1 -right-1 bg-primary text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">
                            {{ strtoupper(Str::substr($user->name ?? 'U', 0, 2)) }}
                        </span>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $user->name ?? 'User' }}</p>
                        <p class="text-sm text-gray-500">{{ $user->email ?? '-' }}</p>
                        <p class="text-xs text-gray-400 mt-1">ID: {{ $user->id }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 text-sm">
                    <div class="px-4 py-2 rounded-xl bg-gray-50 border border-gray-100">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Total Attempt</p>
                        <p class="text-xl font-semibold text-gray-900">{{ $participant['total_attempts'] }}</p>
                    </div>
                    <div class="px-4 py-2 rounded-xl bg-gray-50 border border-gray-100">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Skor Terakhir</p>
                        <p class="text-xl font-semibold text-gray-900">{{ $participant['latest_score'] }}</p>
                    </div>
                    <div class="px-4 py-2 rounded-xl bg-gray-50 border border-gray-100">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Terakhir Selesai</p>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ $lastFinished ? $lastFinished->translatedFormat('d M Y') : '-' }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.user.report', $user->id) }}"
                        class="px-4 py-2 text-sm border border-gray-200 rounded-full text-gray-600 hover:bg-gray-50 flex items-center gap-2 font-semibold">
                        <i class="ri-line-chart-line text-base"></i>
                        Profil User
                    </a>
                    <button type="button" data-participant-toggle data-target="participant-attempts-{{ $index }}"
                        class="px-4 py-2 text-sm rounded-full border border-primary/50 text-primary font-semibold flex items-center gap-2 hover:bg-primary hover:text-white transition">
                        <i class="ri-list-check-2 text-base"></i>
                        <span>Lihat Attempt</span>
                    </button>
                </div>
            </div>
            <div id="participant-attempts-{{ $index }}" class="hidden border-t border-border/50 bg-gradient-to-b from-gray-50 to-white rounded-b-2xl">
                <div class="overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-gray-600">
                            <thead class="text-xs text-gray-500 uppercase bg-white border-b border-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left">Attempt Token</th>
                                    <th class="px-4 py-2 text-center">Skor</th>
                                    <th class="px-4 py-2 text-center">Benar/Salah</th>
                                    <th class="px-4 py-2 text-center">Durasi</th>
                                    <th class="px-4 py-2 text-center">Selesai</th>
                                    <th class="px-4 py-2 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($participant['attempts'] as $attempt)
                                @php
                                    $questions = ($attempt->total_correct ?? 0) + ($attempt->total_wrong ?? 0) + ($attempt->total_unanswered ?? 0);
                                    $attemptFinished = $attempt->finished_at ? Carbon::parse($attempt->finished_at) : null;
                                    $attemptStarted = $attempt->started_at ? Carbon::parse($attempt->started_at) : null;
                                    $durationLabel = ($attemptStarted && $attemptFinished) ? $attemptStarted->diffForHumans($attemptFinished, true) : '-';
                                @endphp
                                <tr class="border-t border-gray-100 hover:bg-gray-50/80 transition">
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-2 text-xs font-mono bg-gray-100 px-3 py-1 rounded-full text-gray-600">
                                            <i class="ri-hashtag"></i>{{ $attempt->attempt_token }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <p class="text-lg font-semibold text-gray-900">{{ round($attempt->raw_score ?? 0, 1) }}</p>
                                        <p class="text-[11px] text-gray-500">{{ $questions }} soal</p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="inline-flex items-center justify-center gap-3 text-xs font-semibold">
                                            <span class="flex items-center gap-1 text-green-900" title="Benar">
                                                <i class="ri-check-line text-base w-[25px] h-[25px] bg-green-900 flex justify-center items-center rounded-full text-white"></i>
                                                {{ $attempt->total_correct ?? 0 }}
                                            </span>
                                            <span class="flex items-center gap-1 text-red-900" title="Salah">
                                                <i class="ri-close-line text-base w-[25px] h-[25px] bg-red-900 flex justify-center items-center rounded-full text-white"></i>
                                                {{ $attempt->total_wrong ?? 0 }}
                                            </span>
                                            <span class="flex items-center gap-1 text-gray-500" title="Kosong">
                                                <i class="ri-subtract-line text-base w-[25px] h-[25px] bg-gray-500 flex justify-center items-center rounded-full text-white"></i>
                                                {{ $attempt->total_unanswered ?? 0 }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ $durationLabel }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <p class="font-medium text-gray-800">{{ $attemptFinished ? $attemptFinished->translatedFormat('d M Y') : '-' }}</p>
                                        <p class="text-xs text-gray-500">{{ $attemptFinished ? $attemptFinished->format('H:i') : '' }}</p>
                                        @if(($attempt->attempt_status ?? null) !== 'completed')
                                        <p class="text-[11px] text-amber-600 mt-1">{{ $attempt->attempt_status_label }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('admin.laporan.attempt', [$tryout->tryout_id, $attempt->attempt_token]) }}"
                                            class="inline-flex items-center gap-2 px-4 py-1.5 text-xs font-semibold rounded-full border border-primary text-primary hover:bg-primary hover:text-white transition">
                                            <i class="ri-file-list-3-line text-base"></i>
                                            Detail Jawaban
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="border border-dashed border-gray-200 rounded-lg px-6 py-10 text-center text-gray-500">
            Belum ada peserta untuk tryout ini.
        </div>
        @endforelse
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('participant-search');
        const cards = document.querySelectorAll('[data-participant-card]');

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                const term = searchInput.value.toLowerCase();
                cards.forEach(card => {
                    const name = card.dataset.name || '';
                    card.style.display = name.includes(term) ? '' : 'none';
                });
            });
        }

        document.querySelectorAll('[data-participant-toggle]').forEach(button => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-target');
                const target = document.getElementById(targetId);
                if (!target) return;

                target.classList.toggle('hidden');
                const label = button.querySelector('span');
                if (label) {
                    label.textContent = target.classList.contains('hidden') ? 'Lihat Attempt' : 'Sembunyikan';
                }
            });
        });
    });
</script>
@endsection
