<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10pt; line-height: 1.5; }
        h1 { margin: 0; font-size: 18pt; }
        h2 { margin: 4px 0 20px; font-size: 11pt; color: #4b5563; font-weight: normal; }
        .question { margin: 0 0 22px; page-break-inside: avoid; }
        .number { font-weight: bold; color: #047857; }
        .subtest { color: #6b7280; font-size: 8.5pt; }
        .content { margin: 8px 0; }
        .options { margin: 8px 0 0 18px; padding: 0; }
        .options li { margin: 4px 0; }
        img { max-width: 100%; height: auto; }
    </style>
</head>
<body>
    <h1>Soal Tryout</h1>
    <h2>{{ $tryout->name }}</h2>

    @forelse($questions as $index => $question)
        <section class="question">
            <div class="number">Soal {{ $index + 1 }}</div>
            @if($question->tryoutDetail)
                <div class="subtest">{{ strtoupper((string) $question->tryoutDetail->type_subtest) }}</div>
            @endif
            <div class="content">{!! $question->question_text !!}</div>
            @if($question->questionOptions->isNotEmpty())
                <ol class="options" type="A">
                    @foreach($question->questionOptions as $option)
                        <li>{!! $option->option_text !!}</li>
                    @endforeach
                </ol>
            @endif
        </section>
    @empty
        <p>Belum ada soal untuk diunduh.</p>
    @endforelse
</body>
</html>
