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
        li { margin-bottom: 6px; }
        .footer { border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 9px; margin-top: 28px; padding-top: 10px; }
    </style>
</head>
<body>
    <h1>{{ $artifact->title }}</h1>
    <div class="meta">{{ $artifact->tryout?->name ?? 'Pembahasan Tryout' }} · {{ $artifact->saved_at?->translatedFormat('d F Y H:i') }}</div>
    <div class="box">{{ data_get($artifact->payload, 'summary') }}</div>
    @if(collect(data_get($artifact->payload, 'key_points', []))->isNotEmpty())
        <h2>Poin penting</h2><ul>@foreach(data_get($artifact->payload, 'key_points', []) as $point)<li>{{ $point }}</li>@endforeach</ul>
    @endif
    @if(collect(data_get($artifact->payload, 'formulas', []))->isNotEmpty())
        <h2>Rumus / istilah</h2><ul>@foreach(data_get($artifact->payload, 'formulas', []) as $formula)<li>{{ $formula }}</li>@endforeach</ul>
    @endif
    <div class="footer">Dibuat melalui AI Catatan Materi. Periksa kembali catatan dengan sumber pembelajaran resmi.</div>
</body>
</html>
