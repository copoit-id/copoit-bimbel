<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10pt; line-height: 1.5; }
        h1 { margin: 0; font-size: 18pt; } h2 { margin: 4px 0 20px; font-size: 11pt; color: #4b5563; font-weight: normal; }
        .question { margin: 0 0 22px; page-break-inside: avoid; }
        .number { font-weight: bold; color: #047857; } .subtest { color: #6b7280; font-size: 8.5pt; }
        .content { margin: 8px 0; } .options { margin: 8px 0 0 18px; padding: 0; } .options li { margin: 4px 0; }
        .answer, .explanation { margin-top: 10px; padding: 9px 11px; border-radius: 4px; }
        .answer { background: #ecfdf5; border: 1px solid #a7f3d0; } .explanation { background: #f9fafb; border: 1px solid #e5e7eb; }
        .label { font-weight: bold; margin-bottom: 3px; } img { max-width: 100%; height: auto; }
    </style>
</head>
<body>
    <h1>{{ $type === 'soal' ? 'Soal Tryout' : 'Soal & Pembahasan Tryout' }}</h1>
    <h2>{{ $tryout->name }}</h2>
    @foreach($questions as $index => $question)
        <section class="question">
            <div class="number">Soal {{ $index + 1 }}</div>
            @if($question->tryoutDetail)<div class="subtest">{{ strtoupper((string) $question->tryoutDetail->type_subtest) }}</div>@endif
            <div class="content">{!! $question->question_text !!}</div>
            @if($question->questionOptions->isNotEmpty())
                <ol class="options" type="A">
                    @foreach($question->questionOptions as $option)
                        <li>{!! $option->option_text !!}</li>
                    @endforeach
                </ol>
            @endif
            @if($type === 'pembahasan')
                @php
                    $correctOptions = $question->question_type === 'tkp'
                        ? $question->questionOptions->filter(fn ($option) => (float) $option->weight === (float) $question->questionOptions->max('weight'))
                        : $question->questionOptions->where('is_correct', true);
                @endphp
                @if($correctOptions->isNotEmpty())
                    <div class="answer"><div class="label">Jawaban benar</div>{!! $correctOptions->pluck('option_text')->implode('<br>') !!}</div>
                @endif
                @if(filled($question->explanation))
                    <div class="explanation"><div class="label">Pembahasan</div>{!! $question->explanation !!}</div>
                @endif
            @endif
        </section>
    @endforeach
</body>
</html>
