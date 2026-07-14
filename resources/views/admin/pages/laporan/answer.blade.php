@extends('admin.layout.admin')
@section('title', 'Detail Jawaban Peserta')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.laporan.index') }}" title="Laporan Tryout" />
            <x-breadcrumb-item href="{{ route('admin.laporan.show', $tryout->tryout_id) }}" title="{{ $tryout->name }}" />
            <x-breadcrumb-item href="" title="Detail Jawaban" />
        </x-slot>
    </x-breadcrumb>
    <div class="flex gap-2">
        <a href="{{ route('admin.laporan.attempt.export-pdf', [$tryout->tryout_id, $attemptToken, 'inline' => 1]) }}" target="_blank"
            class="flex items-center gap-2 px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50">
            <i class="ri-printer-line"></i>
            Cetak
        </a>
        <a href="{{ route('admin.laporan.attempt.export-pdf', [$tryout->tryout_id, $attemptToken]) }}"
            class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
            <i class="ri-download-2-line"></i>
            Unduh PDF
        </a>
    </div>
</div>
<x-page-desc title="Jawaban Peserta - {{ $user->name }}"></x-page-desc>

<div class="bg-white border border-border rounded-lg p-6 mt-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=4f46e5&color=fff"
                class="w-16 h-16 rounded-full" alt="{{ $user->name }}">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">{{ $user->name }}</h2>
                <p class="text-gray-500">{{ $user->email }}</p>
                <p class="text-sm text-gray-400">Attempt Token: <span class="font-mono text-gray-600">{{ $attemptToken }}</span></p>
            </div>
        </div>
        <div class="flex gap-4">
            <div class="text-center">
                <p class="text-sm text-gray-500">Mulai</p>
                <p class="text-lg font-semibold text-gray-800">
                    {{ optional($overallStats['started_at'])->format('d M Y H:i') ?? '-' }}
                </p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500">Selesai</p>
                <p class="text-lg font-semibold text-gray-800">
                    {{ optional($overallStats['finished_at'])->format('d M Y H:i') ?? '-' }}
                </p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
    <div class="bg-white border border-border rounded-lg p-5 text-center">
        <p class="text-sm text-gray-500">Total Soal</p>
        <p class="text-3xl font-bold text-gray-900">{{ $overallStats['total_questions'] }}</p>
    </div>
    <div class="bg-white border border-border rounded-lg p-5 text-center">
        <p class="text-sm text-gray-500">Benar</p>
        <p class="text-3xl font-bold text-green-600">{{ $overallStats['correct'] }}</p>
    </div>
    <div class="bg-white border border-border rounded-lg p-5 text-center">
        <p class="text-sm text-gray-500">Salah</p>
        <p class="text-3xl font-bold text-red-500">{{ $overallStats['wrong'] }}</p>
    </div>
    <div class="bg-white border border-border rounded-lg p-5 text-center">
        <p class="text-sm text-gray-500">Skor Rata-rata</p>
        <p class="text-3xl font-bold text-primary">{{ $overallStats['score'] }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <div class="bg-white border border-border rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Rangkuman Per Subtest</h3>
        <div class="space-y-4">
            @forelse($subtests as $subtest)
            <div class="border border-dashed border-gray-200 rounded-lg p-4">
                <div class="flex justify-between">
                    <div>
                        <p class="font-semibold text-gray-900">{{ $subtest['name'] ?? 'Subtest' }}</p>
                        <p class="text-xs text-gray-500">{{ $subtest['duration'] ?? '-' }} menit</p>
                    </div>
                    <span class="text-sm font-semibold text-primary">{{ $subtest['score'] }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2 mt-3 text-center text-sm">
                    <div class="bg-green-50 text-green-700 rounded py-2">
                        <p class="font-semibold">{{ $subtest['correct'] }}</p>
                        <p>Benar</p>
                    </div>
                    <div class="bg-red-50 text-red-600 rounded py-2">
                        <p class="font-semibold">{{ $subtest['wrong'] }}</p>
                        <p>Salah</p>
                    </div>
                    <div class="bg-gray-50 text-gray-600 rounded py-2">
                        <p class="font-semibold">{{ $subtest['unanswered'] }}</p>
                        <p>Kosong</p>
                    </div>
                </div>
            </div>
            @empty
            <p class="text-gray-500">Tidak ada data subtest.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white border border-border rounded-lg p-6 lg:col-span-2">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Detail Jawaban Soal</h3>
            <div class="flex items-center gap-2">
                <div class="relative">
                    <input type="text" id="answer-search" placeholder="Cari soal..."
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
                </div>
            </div>
        </div>

        <div class="relative overflow-x-auto">
            <table class="w-full text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th class="px-4 py-3">Soal</th>
                        <th class="px-4 py-3">Subtest</th>
                        <th class="px-4 py-3">Jawaban Peserta</th>
                        <th class="px-4 py-3">Kunci</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="answer-rows">
                    @php
                    use Illuminate\Support\Str;
                    @endphp
                    @forelse($answerDetails as $detail)
                    @php
                    $question = $detail->question;
                    $questionText = $question ? Str::limit(strip_tags($question->question_text), 110) : 'Soal';
                    $correctOption = $question ? $question->questionOptions->firstWhere('is_correct', true) : null;
                    $participantAnswer = $detail->questionOption->option_text ?? ($detail->answer_text ?? '-');
                    if(!$participantAnswer && !empty($detail->answer_json['matches'])) {
                        $pairs = collect($detail->answer_json['matches'])->map(function($pair) {
                            return ($pair['left'] ?? '?') . ' → ' . ($pair['right'] ?? '?');
                        });
                        $participantAnswer = $pairs->implode(', ');
                    }
                    $correctAnswer = $correctOption->option_text ?? ($question->answer_text ?? '-');
                    @endphp
                    <tr class="bg-white border-b border-dashed border-gray-200 answer-row">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $questionText }}</p>
                            <p class="text-xs text-gray-400 mt-1">#{{ $detail->question_id }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 text-sm text-gray-600">
                                <i class="ri-layout-line"></i>
                                {{ $detail->subtest_name ?? 'Subtest' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-800">{!! $participantAnswer ?: '-' !!}</div>
                            @if($detail->answer_file_path)
                            <a href="{{ Storage::url($detail->answer_file_path) }}" target="_blank"
                                class="text-primary text-xs inline-flex items-center gap-1 mt-1">
                                <i class="ri-download-2-line"></i> Unduh Lampiran
                            </a>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-600">{!! $correctAnswer !!}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($detail->is_correct)
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 border border-green-200">Benar</span>
                            @else
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-600 border border-red-200">Salah</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500">Belum ada detail jawaban.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('answer-search');
        const rows = document.querySelectorAll('.answer-row');

        if (!searchInput) {
            return;
        }

        searchInput.addEventListener('input', () => {
            const term = searchInput.value.toLowerCase();
            rows.forEach(row => {
                const text = row.querySelector('td').textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    });
</script>
@endsection
