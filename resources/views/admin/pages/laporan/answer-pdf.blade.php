<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Jawaban - {{ $user->name }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.45;
            margin: 20px;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 4px;
        }

        h2 {
            font-size: 14px;
            margin: 18px 0 8px;
        }

        p {
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 7px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            color: #111827;
            font-weight: bold;
            text-align: left;
        }

        .meta {
            margin-top: 10px;
            color: #4b5563;
        }

        .summary {
            margin-top: 16px;
        }

        .summary td {
            width: 20%;
        }

        .label {
            color: #6b7280;
            font-size: 10px;
        }

        .value {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
        }

        .status-correct {
            color: #047857;
            font-weight: bold;
        }

        .status-wrong {
            color: #dc2626;
            font-weight: bold;
        }

        .small {
            color: #6b7280;
            font-size: 10px;
        }
    </style>
</head>

<body>
    <h1>Detail Jawaban Peserta</h1>
    <p>{{ $tryout->name }}</p>

    <table class="meta">
        <tr>
            <td>
                <p class="label">Peserta</p>
                <p><strong>{{ $user->name }}</strong></p>
                <p>{{ $user->email }}</p>
            </td>
            <td>
                <p class="label">Attempt Token</p>
                <p>{{ $attemptToken }}</p>
            </td>
            <td>
                <p class="label">Mulai</p>
                <p>{{ optional($overallStats['started_at'])->format('d M Y H:i') ?? '-' }}</p>
            </td>
            <td>
                <p class="label">Selesai</p>
                <p>{{ optional($overallStats['finished_at'])->format('d M Y H:i') ?? '-' }}</p>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td>
                <p class="label">Total Soal</p>
                <p class="value">{{ $overallStats['total_questions'] }}</p>
            </td>
            <td>
                <p class="label">Benar</p>
                <p class="value">{{ $overallStats['correct'] }}</p>
            </td>
            <td>
                <p class="label">Salah</p>
                <p class="value">{{ $overallStats['wrong'] }}</p>
            </td>
            <td>
                <p class="label">Kosong</p>
                <p class="value">{{ $overallStats['unanswered'] }}</p>
            </td>
            <td>
                <p class="label">Skor Rata-rata</p>
                <p class="value">{{ $overallStats['score'] }}</p>
            </td>
        </tr>
    </table>

    <h2>Rangkuman Per Subtest</h2>
    <table>
        <thead>
            <tr>
                <th>Subtest</th>
                <th>Durasi</th>
                <th>Benar</th>
                <th>Salah</th>
                <th>Kosong</th>
                <th>Skor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($subtests as $subtest)
            <tr>
                <td>{{ $subtest['name'] ?? 'Subtest' }}</td>
                <td>{{ $subtest['duration'] ?? '-' }} menit</td>
                <td>{{ $subtest['correct'] }}</td>
                <td>{{ $subtest['wrong'] }}</td>
                <td>{{ $subtest['unanswered'] }}</td>
                <td>{{ $subtest['score'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6">Tidak ada data subtest.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Detail Jawaban Soal</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 28%;">Soal</th>
                <th style="width: 17%;">Subtest</th>
                <th style="width: 22%;">Jawaban Peserta</th>
                <th style="width: 22%;">Kunci</th>
                <th style="width: 11%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @php
            use Illuminate\Support\Str;
            @endphp
            @forelse($answerDetails as $detail)
            @php
            $question = $detail->question;
            $questionText = $question ? Str::limit(strip_tags($question->question_text), 160) : 'Soal';
            $correctOption = $question ? $question->questionOptions->firstWhere('is_correct', true) : null;
            $questionType = $question->question_type ?? 'multiple_choice';
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
            $participantAnswer = $detail->questionOption->option_text ?? ($detail->answer_text ?? '-');

            if($question && $questionType === 'multiple_answer') {
                $selectedIds = collect($answerMeta['selected_option_ids'] ?? [])->map(fn($id) => (int) $id)->all();
                $selectedOptions = $question->questionOptions
                    ->whereIn('question_option_id', $selectedIds)
                    ->pluck('option_text')
                    ->values();
                $participantAnswer = $selectedOptions->isNotEmpty() ? $selectedOptions->implode(', ') : '-';
            }

            if($question && $questionType === 'matching') {
                $userMatches = is_array($answerMeta['matches'] ?? null) ? $answerMeta['matches'] : [];
                $pairs = collect($userMatches)->map(function($right, $left) {
                    return (string) $left . ' -> ' . (string) ($right !== '' ? $right : '-');
                })->values();
                $participantAnswer = $pairs->isNotEmpty() ? $pairs->implode('<br>') : '-';
            }

            if($question && $questionType === 'multiple_true_false') {
                $mtfMeta = is_array($question->metadata['multiple_true_false'] ?? null) ? $question->metadata['multiple_true_false'] : [];
                $mtfStatements = is_array($mtfMeta['statements'] ?? null) ? $mtfMeta['statements'] : [];
                $mtfAnswers = is_array($answerMeta['answers'] ?? null) ? $answerMeta['answers'] : [];
                $trueLabel = trim((string) ($mtfMeta['true_label'] ?? 'Benar'));
                $falseLabel = trim((string) ($mtfMeta['false_label'] ?? 'Salah'));
                $pairs = collect($mtfStatements)->map(function($stmt) use ($mtfAnswers, $trueLabel, $falseLabel) {
                    $statementId = (string) ($stmt['id'] ?? '');
                    $answer = strtolower((string) ($mtfAnswers[$statementId] ?? ''));
                    $label = $answer === 'true'
                        ? ($trueLabel !== '' ? $trueLabel : 'Benar')
                        : ($answer === 'false' ? ($falseLabel !== '' ? $falseLabel : 'Salah') : '-');
                    return (string) ($stmt['text'] ?? '-') . ' -> ' . $label;
                })->values();
                $participantAnswer = $pairs->isNotEmpty() ? $pairs->implode('<br>') : '-';
            }

            $correctAnswer = $correctOption->option_text ?? ($question->answer_text ?? '-');

            if($question && $questionType === 'multiple_answer') {
                $correctOptions = $question->questionOptions
                    ->where('is_correct', true)
                    ->pluck('option_text')
                    ->values();
                $correctAnswer = $correctOptions->isNotEmpty() ? $correctOptions->implode(', ') : '-';
            }

            if($question && $questionType === 'matching') {
                $matchingPairs = is_array($question->metadata['matching_pairs'] ?? null) ? $question->metadata['matching_pairs'] : [];
                $pairs = collect($matchingPairs)->map(function($pair) {
                    return (string) ($pair['left'] ?? '-') . ' -> ' . (string) ($pair['right'] ?? '-');
                })->values();
                $correctAnswer = $pairs->isNotEmpty() ? $pairs->implode('<br>') : '-';
            }

            if($question && $questionType === 'multiple_true_false') {
                $mtfMeta = is_array($question->metadata['multiple_true_false'] ?? null) ? $question->metadata['multiple_true_false'] : [];
                $mtfStatements = is_array($mtfMeta['statements'] ?? null) ? $mtfMeta['statements'] : [];
                $trueLabel = trim((string) ($mtfMeta['true_label'] ?? 'Benar'));
                $falseLabel = trim((string) ($mtfMeta['false_label'] ?? 'Salah'));
                $pairs = collect($mtfStatements)->map(function($stmt) use ($trueLabel, $falseLabel) {
                    $correct = strtolower((string) ($stmt['correct'] ?? 'true'));
                    $label = $correct === 'true'
                        ? ($trueLabel !== '' ? $trueLabel : 'Benar')
                        : ($falseLabel !== '' ? $falseLabel : 'Salah');
                    return (string) ($stmt['text'] ?? '-') . ' -> ' . $label;
                })->values();
                $correctAnswer = $pairs->isNotEmpty() ? $pairs->implode('<br>') : '-';
            }
            @endphp
            <tr>
                <td>
                    <p>{{ $questionText }}</p>
                    <p class="small">#{{ $detail->question_id }}</p>
                </td>
                <td>{{ $detail->subtest_name ?? 'Subtest' }}</td>
                <td>{!! $participantAnswer ?: '-' !!}</td>
                <td>{!! $correctAnswer ?: '-' !!}</td>
                <td>
                    @if($detail->is_correct)
                    <span class="status-correct">Benar</span>
                    @else
                    <span class="status-wrong">Salah</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5">Belum ada detail jawaban.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
