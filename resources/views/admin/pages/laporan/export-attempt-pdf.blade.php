<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Jawaban {{ $user->name }} - {{ $tryout->name }}</title>
    <style>
        @page { margin: 22px 24px; }
        body { color: #1f2937; font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; }
        .header { border-bottom: 2px solid #4f46e5; margin-bottom: 14px; padding-bottom: 10px; }
        .brand { color: #4f46e5; font-size: 9px; font-weight: bold; letter-spacing: .7px; margin: 0 0 4px; text-transform: uppercase; }
        h1 { font-size: 17px; margin: 0 0 5px; }
        .muted { color: #6b7280; }
        .meta { line-height: 1.65; }
        .summary { border-collapse: separate; border-spacing: 6px 0; margin: 14px -6px; width: calc(100% + 12px); }
        .summary td { background: #f8fafc; border: 1px solid #e5e7eb; padding: 8px; text-align: center; width: 25%; }
        .summary-label { color: #6b7280; font-size: 8px; margin: 0 0 3px; text-transform: uppercase; }
        .summary-value { color: #111827; font-size: 14px; font-weight: bold; margin: 0; }
        h2 { font-size: 11px; margin: 15px 0 7px; }
        table { border-collapse: collapse; width: 100%; }
        thead { display: table-header-group; }
        th { background: #eef2ff; border: 1px solid #c7d2fe; color: #312e81; font-size: 8px; padding: 7px 6px; text-align: left; text-transform: uppercase; }
        td { border: 1px solid #e5e7eb; padding: 6px; vertical-align: top; }
        .center { text-align: center; }
        .question { color: #111827; font-weight: bold; }
        .question-id { color: #9ca3af; font-size: 7px; margin-top: 3px; }
        .correct { color: #166534; font-weight: bold; }
        .wrong { color: #b91c1c; font-weight: bold; }
        .footer { color: #9ca3af; font-size: 7px; margin-top: 12px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <p class="brand">{{ config('app.name') }} · Laporan Detail Jawaban</p>
        <h1>{{ $tryout->name }}</h1>
        <div class="meta">
            <strong>{{ $user->name }}</strong> · {{ $user->email ?? '-' }}<br>
            Attempt: {{ $attemptToken }} · Selesai: {{ optional($overallStats['finished_at'])->format('d M Y H:i') ?? '-' }}<br>
            <span class="muted">Diekspor {{ now()->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</span>
        </div>
    </div>

    <table class="summary">
        <tr>
            <td><p class="summary-label">Total Soal</p><p class="summary-value">{{ $overallStats['total_questions'] }}</p></td>
            <td><p class="summary-label">Benar</p><p class="summary-value">{{ $overallStats['correct'] }}</p></td>
            <td><p class="summary-label">Salah</p><p class="summary-value">{{ $overallStats['wrong'] }}</p></td>
            <td><p class="summary-label">Nilai</p><p class="summary-value">{{ $overallStats['score'] }}</p></td>
        </tr>
    </table>

    <h2>Detail Jawaban</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 28%">Soal</th>
                <th style="width: 14%">Subtest</th>
                <th style="width: 24%">Jawaban Peserta</th>
                <th style="width: 24%">Kunci Jawaban</th>
                <th style="width: 10%" class="center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($answerDetails as $detail)
                @php
                    $question = $detail->question;
                    $questionText = $question ? \Illuminate\Support\Str::limit(trim(strip_tags($question->question_text)), 150) : 'Soal tidak tersedia';
                    $participantAnswer = $detail->questionOption?->option_text ?? $detail->answer_text;
                    if (! $participantAnswer && ! empty($detail->answer_json['matches'])) {
                        $participantAnswer = collect($detail->answer_json['matches'])
                            ->map(fn ($pair) => ($pair['left'] ?? '?') . ' → ' . ($pair['right'] ?? '?'))
                            ->implode('; ');
                    }
                    $correctAnswer = $question
                        ? $question->questionOptions->where('is_correct', true)->pluck('option_text')->implode('; ')
                        : null;
                @endphp
                <tr>
                    <td>
                        <div class="question">{{ $questionText }}</div>
                        <div class="question-id">#{{ $detail->question_id }}</div>
                    </td>
                    <td>{{ $detail->subtest_name ?? 'Subtest' }}</td>
                    <td>{{ trim(strip_tags($participantAnswer ?: '-')) }}</td>
                    <td>{{ trim(strip_tags($correctAnswer ?: $question?->answer_text ?: '-')) }}</td>
                    <td class="center {{ $detail->is_correct ? 'correct' : 'wrong' }}">{{ $detail->is_correct ? 'Benar' : 'Salah' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center muted">Belum ada detail jawaban.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">{{ config('app.name') }} · Detail jawaban peserta</p>
</body>
</html>
