<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.6; }
        h1 { color: #111827; font-size: 22px; margin-bottom: 4px; }
        h2 { color: #111827; font-size: 14px; margin-top: 22px; }
        .meta { color: #6b7280; font-size: 10px; }
        .box { background: #f3f4f6; border-radius: 6px; margin-top: 12px; padding: 12px; }
        .section { border-left: 3px solid #4f46e5; margin-top: 18px; padding-left: 12px; }
        .section h2 { margin-top: 0; }
        .callout { background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; margin-top: 18px; padding: 12px; }
        li { margin-bottom: 6px; }
        .footer { border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 9px; margin-top: 28px; padding-top: 10px; }
    </style>
</head>
<body>
    <h1>{{ $artifact->title }}</h1>
    <div class="meta">{{ $artifact->tryout?->name ?? 'Pembahasan Tryout' }} · {{ $artifact->saved_at?->translatedFormat('d F Y H:i') }}</div>
    @php($summaryParagraphs = collect(preg_split('/\\R{2,}/', (string) data_get($artifact->payload, 'summary', '')))->filter())
    @if($summaryParagraphs->isNotEmpty())
        <div class="box">@foreach($summaryParagraphs as $paragraph)<p>{{ $paragraph }}</p>@endforeach</div>
    @endif
    @foreach(data_get($artifact->payload, 'sections', []) as $section)
        <div class="section">
            <h2>{{ data_get($section, 'title') }}</h2>
            @foreach(data_get($section, 'paragraphs', []) as $paragraph)<p>{{ $paragraph }}</p>@endforeach
            @if(collect(data_get($section, 'bullets', []))->isNotEmpty())
                <ul>@foreach(data_get($section, 'bullets', []) as $bullet)<li>{{ $bullet }}</li>@endforeach</ul>
            @endif
        </div>
    @endforeach
    @if(collect(data_get($artifact->payload, 'key_points', []))->isNotEmpty())
        <div class="callout"><h2>Poin penting untuk diingat</h2><ul>@foreach(data_get($artifact->payload, 'key_points', []) as $point)<li>{{ $point }}</li>@endforeach</ul></div>
    @endif
    @if(collect(data_get($artifact->payload, 'formulas', []))->isNotEmpty())
        <h2>Rumus / istilah</h2><ul>@foreach(data_get($artifact->payload, 'formulas', []) as $formula)<li>{{ $formula }}</li>@endforeach</ul>
    @endif
    <div class="footer">Dibuat melalui AI Catatan Materi. Periksa kembali catatan dengan sumber pembelajaran resmi.</div>
</body>
</html>
