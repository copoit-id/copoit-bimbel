<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18mm 14mm 17mm; }
        * { box-sizing: border-box; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.45; margin: 0; }
        h1, h2, h3, p { margin: 0; }
        .report-header { border-bottom: 2px solid #4f46e5; margin-bottom: 16px; padding-bottom: 12px; }
        .brand { color: #4f46e5; font-size: 9px; font-weight: bold; letter-spacing: 1.1px; text-transform: uppercase; }
        h1 { color: #111827; font-size: 20px; margin-top: 3px; }
        .subtitle { color: #6b7280; font-size: 9px; margin-top: 4px; }
        .user-card { background: #f8fafc; border: 1px solid #dbe1ea; border-radius: 7px; margin-bottom: 14px; padding: 12px; }
        .user-name { font-size: 14px; font-weight: bold; }
        .user-meta { color: #6b7280; margin-top: 4px; }
        .stats { border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 16px; table-layout: fixed; width: calc(100% + 12px); }
        .stat { background: #f8fafc; border: 1px solid #dbe1ea; border-radius: 6px; padding: 9px; }
        .stat-label { color: #6b7280; font-size: 8px; text-transform: uppercase; }
        .stat-value { color: #111827; font-size: 16px; font-weight: bold; margin-top: 3px; }
        .section { margin-top: 16px; }
        .section-title { border-bottom: 1px solid #dbe1ea; color: #111827; font-size: 12px; font-weight: bold; margin-bottom: 7px; padding-bottom: 6px; }
        .section-note { color: #6b7280; float: right; font-size: 8px; font-weight: normal; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #f3f4f6; color: #4b5563; font-size: 7px; letter-spacing: .3px; padding: 7px 6px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #e5e7eb; padding: 7px 6px; vertical-align: top; }
        .muted { color: #6b7280; }
        .score { font-size: 11px; font-weight: bold; text-align: center; }
        .badge { border-radius: 10px; display: inline-block; font-size: 7px; font-weight: bold; padding: 3px 6px; }
        .passed { background: #dcfce7; color: #166534; }
        .not-passed { background: #fee2e2; color: #b91c1c; }
        .empty { color: #6b7280; padding: 13px 0; text-align: center; }
        .certificate { border-bottom: 1px solid #e5e7eb; padding: 8px 0; }
        .certificate:last-child { border-bottom: 0; }
        .certificate-name { font-weight: bold; }
        .timeline-item { border-left: 2px solid #e5e7eb; margin-left: 5px; min-height: 30px; padding: 0 0 12px 13px; position: relative; }
        .timeline-item:before { background: #4f46e5; border: 2px solid #eef2ff; border-radius: 50%; content: ''; height: 7px; left: -5px; position: absolute; top: 2px; width: 7px; }
        .timeline-item.tryout:before { background: #2563eb; }
        .timeline-item.certificate:before { background: #d97706; }
        .timeline-text { font-weight: bold; }
        .timeline-date { color: #6b7280; font-size: 8px; margin-top: 2px; }
        .footer { bottom: -10mm; color: #9ca3af; font-size: 7px; left: 0; position: fixed; right: 0; text-align: center; }
    </style>
</head>
<body>
    <div class="report-header">
        <p class="brand">{{ config('app.report_name') }} · Laporan Pengguna</p>
        <h1>Detail Aktivitas {{ $user->name }}</h1>
        <p class="subtitle">Dibuat pada {{ now()->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</p>
    </div>

    <div class="user-card">
        <p class="user-name">{{ $user->name }}</p>
        <p class="user-meta">{{ $user->email }} · Bergabung {{ $user->created_at->format('d M Y') }}</p>
    </div>

    <table class="stats"><tr>
        <td class="stat"><p class="stat-label">Total Tryout</p><p class="stat-value">{{ $statistics['total_tryouts'] }}</p></td>
        <td class="stat"><p class="stat-label">Rata-rata Nilai</p><p class="stat-value">{{ $statistics['avg_score'] }}</p></td>
        <td class="stat"><p class="stat-label">Sertifikat</p><p class="stat-value">{{ $statistics['total_certificates'] }}</p></td>
        <td class="stat"><p class="stat-label">Waktu Belajar</p><p class="stat-value">{{ $statistics['study_hours'] }} jam</p></td>
    </tr></table>

    <div class="section">
        <h2 class="section-title">Riwayat Pengerjaan Tryout <span class="section-note">{{ $tryoutHistory->count() }} riwayat · seluruh data</span></h2>
        @if($tryoutHistory->isNotEmpty())
        <table>
            <thead><tr><th style="width: 16%">Tanggal</th><th style="width: 33%">Tryout</th><th style="width: 10%; text-align:center">Nilai</th><th style="width: 14%">Jawaban benar</th><th style="width: 13%">Durasi</th><th style="width: 14%">Status</th></tr></thead>
            <tbody>
                @foreach($tryoutHistory as $tryout)
                <tr>
                    <td>{{ $tryout['date']->format('d M Y') }}<br><span class="muted">{{ $tryout['date']->format('H:i') }} WIB</span></td>
                    <td><strong>{{ $tryout['name'] }}</strong>@if($tryout['section'])<br><span class="muted">{{ $tryout['section'] }}</span>@endif</td>
                    <td class="score">{{ $tryout['score'] }}</td>
                    <td>{{ $tryout['correct_answers'] }} / {{ $tryout['total_questions'] }}</td>
                    <td>{{ $tryout['duration'] === null ? '—' : $tryout['duration'] . ' menit' }}</td>
                    <td><span class="badge {{ $tryout['is_passed'] ? 'passed' : 'not-passed' }}">{{ $tryout['is_passed'] ? 'Lulus' : 'Belum lulus' }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="empty">Belum ada riwayat tryout yang selesai.</p>
        @endif
    </div>

    <div class="section">
        <h2 class="section-title">Sertifikat <span class="section-note">{{ $certificates->count() }} sertifikat</span></h2>
        @forelse($certificates as $certificate)
        <div class="certificate"><p class="certificate-name">{{ $certificate->certificate_name }}</p><p class="muted">{{ optional($certificate->issued_date)->format('d M Y') ?? 'Tanggal tidak tersedia' }} · {{ $certificate->certificate_number }} · {{ $certificate->status_text }}</p></div>
        @empty
        <p class="empty">Belum ada sertifikat.</p>
        @endforelse
    </div>

    <div class="section">
        <h2 class="section-title">Timeline Aktivitas <span class="section-note">{{ $activities->count() }} aktivitas · seluruh data</span></h2>
        @foreach($activities as $activity)
        <div class="timeline-item {{ $activity['type'] }}"><p class="timeline-text">{{ $activity['text'] }}</p><p class="timeline-date">{{ $activity['date']->format('d M Y, H:i') }} WIB</p></div>
        @endforeach
    </div>

    <div class="footer">Laporan aktivitas pengguna · {{ $user->name }}</div>
    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_text(500, 810, "Halaman {PAGE_NUM} dari {PAGE_COUNT}", null, 7, array(0.5, 0.5, 0.5));
        }
    </script>
</body>
</html>
