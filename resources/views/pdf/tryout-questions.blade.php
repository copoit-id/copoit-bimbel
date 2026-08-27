<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12mm 12mm 16mm; }
        body { margin: 0; color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.55; }
        .document-header { width: 100%; margin: 0; border: 0; background: {{ $brandPrimaryColor }}; color: #ffffff; }
        .document-header td { vertical-align: middle; }
        .header-content { position: relative; overflow: hidden; padding: 16px 18px 13px; }
        .header-content-inner { position: relative; z-index: 1; }
        .header-ornament { position: absolute; border-radius: 50%; background: #ffffff; opacity: 0.10; }
        .header-ornament-one { top: -30px; right: -18px; width: 108px; height: 108px; }
        .header-ornament-two { top: 46px; right: 62px; width: 42px; height: 42px; opacity: 0.07; }
        .header-ring { position: absolute; border: 1px solid #ffffff; border-radius: 50%; opacity: 0.16; }
        .header-ring-one { top: 13px; right: 122px; width: 58px; height: 58px; }
        .header-ribbon { display: inline-block; margin-bottom: 11px; padding: 5px 15px; border-radius: 14px; background: #facc35; color: #1f2937; font-size: 8pt; font-weight: bold; letter-spacing: 1.6px; }
        .logo-cell { width: 64px; padding-right: 12px; }
        .brand-logo { width: 52px; max-height: 52px; padding: 5px; border-radius: 8px; background: #ffffff; object-fit: contain; }
        .document-title { margin: 0; color: #ffffff; font-size: 20pt; font-weight: bold; line-height: 1.15; }
        .tryout-name { margin: 5px 0 0; color: #d1fae5; font-size: 9.5pt; }
        .header-meta { margin-top: 10px; }
        .header-meta-item { display: inline-block; margin-right: 5px; padding: 3px 8px; border: 1px solid rgba(255, 255, 255, 0.32); border-radius: 10px; background: rgba(255, 255, 255, 0.10); color: #ffffff; font-size: 7.5pt; font-weight: bold; letter-spacing: 0.4px; }
        .header-note { padding: 8px 18px; border-top: 1px solid {{ $brandPrimaryDarkColor }}; background: {{ $brandPrimaryDarkColor }}; color: #ffffff; font-size: 8.5pt; }
        .document-body { padding: 8mm 0 0; }
        .question { margin: 0 0 16px; padding: 0 0 15px; border-bottom: 1px solid #e5e7eb; page-break-inside: avoid; }
        .question:last-child { margin-bottom: 0; border-bottom: 0; }
        .number { color: #047857; font-weight: bold; }
        .subtest { margin-top: 2px; color: #6b7280; font-size: 8.5pt; }
        .content { margin: 7px 0 3px; }
        .content > :first-child { margin-top: 0; }
        .content > :last-child { margin-bottom: 0; }
        .options { margin: 0 0 0 22px; padding: 0; }
        .options li { margin: 0 0 5px; padding-left: 3px; }
        .options li p { margin: 0; }
        .answer, .explanation { border-radius: 4px; }
        .answer { margin-top: 8px; padding: 6px 8px; border: 1px solid #a7f3d0; background: #ecfdf5; font-size: 9pt; line-height: 1.4; }
        .explanation { margin-top: 10px; padding: 8px 10px; border: 1px solid #e5e7eb; background: #f9fafb; line-height: 1.45; }
        .answer p, .explanation p { margin: 0; }
        .label { margin-bottom: 2px; font-weight: bold; }
        img { max-width: 100%; height: auto; }
    </style>
</head>
<body>
    <table class="document-header" role="presentation">
        <tr>
            <td class="header-content">
                <span class="header-ornament header-ornament-one"></span>
                <span class="header-ornament header-ornament-two"></span>
                <span class="header-ring header-ring-one"></span>
                <div class="header-content-inner">
                    <div class="header-ribbon">DOKUMEN EVALUASI</div>
                    <table role="presentation">
                        <tr>
                            @if($brandLogoDataUrl)
                                <td class="logo-cell"><img src="{{ $brandLogoDataUrl }}" class="brand-logo" alt="{{ $brandName }}"></td>
                            @endif
                            <td>
                                <div class="document-title">{{ $type === 'pembahasan' ? 'Soal & Pembahasan Tryout' : 'Soal Tryout' }}</div>
                                <div class="tryout-name">{{ $tryout->name }} · {{ $brandName }}</div>
                                <div class="header-meta">
                                    <span class="header-meta-item">{{ $questions->count() }} SOAL</span>
                                    <span class="header-meta-item">{{ $type === 'pembahasan' ? 'DENGAN PEMBAHASAN' : 'LATIHAN MANDIRI' }}</span>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
        <tr><td class="header-note">Baca setiap soal dengan teliti, lalu pilih jawaban yang paling tepat.</td></tr>
    </table>

    <main class="document-body">
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
        @empty
            <p>Belum ada soal untuk diunduh.</p>
        @endforelse
    </main>
</body>
</html>
