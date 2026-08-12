<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Lengkap {{ $tryout->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9px; color: #111827; }
        .header { margin-bottom: 16px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .meta { color: #4b5563; margin-bottom: 2px; }
        h2 { font-size: 13px; margin: 22px 0 8px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #e5e7eb; padding: 5px 4px; vertical-align: middle; }
        th { background: #eff6ff; color: #1e3a8a; text-align: center; font-weight: bold; }
        .center { text-align: center; }
        .name { width: 20%; }
        .email { width: 23%; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Laporan Lengkap Tryout — {{ $tryout->name }}</div>
        <div class="meta">Jumlah peserta: {{ count($participants) }}</div>
        <div class="meta">Tanggal export: {{ now()->format('d M Y H:i') }}</div>
    </div>

    <h2>Ringkasan Pengerjaan</h2>
    <table>
        <thead>
            <tr>
                <th style="width:4%">No.</th>
                <th class="name">Peserta</th>
                <th class="email">Email</th>
                <th style="width:11%">Status</th>
                <th style="width:8%">Skor</th>
                <th style="width:7%">B / S / K</th>
                <th style="width:13%">Mulai</th>
                <th style="width:13%">Selesai</th>
                <th style="width:10%">Durasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($participants as $index => $participant)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $participant['name'] }}</td>
                    <td>{{ $participant['email'] }}</td>
                    <td class="center">{{ $participant['status_label'] }}</td>
                    <td class="center">{{ rtrim(rtrim(number_format($participant['total_score'], 2, '.', ''), '0'), '.') }}</td>
                    <td class="center">{{ $participant['total_correct'] }} / {{ $participant['total_wrong'] }} / {{ $participant['total_unanswered'] }}</td>
                    <td class="center">{{ $participant['started_at']?->format('d M Y H:i') ?? '-' }}</td>
                    <td class="center">{{ $participant['finished_at']?->format('d M Y H:i') ?? '-' }}</td>
                    <td class="center">
                        @if($participant['started_at'] && $participant['finished_at'])
                            @php($seconds = $participant['started_at']->diffInSeconds($participant['finished_at']))
                            {{ sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60) }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="center">Belum ada peserta yang mengerjakan tryout ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    @foreach($subtests as $subtest)
        <section class="page-break">
            <h2>Rincian Subtest — {{ $subtest['alias'] }} ({{ $subtest['name'] }})</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width:6%">No.</th>
                        <th class="name">Peserta</th>
                        <th class="email">Email</th>
                        <th style="width:12%">Skor</th>
                        <th style="width:10%">Benar</th>
                        <th style="width:10%">Salah</th>
                        <th style="width:13%">Tidak Dijawab</th>
                        <th style="width:10%">Total Soal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($participants as $index => $participant)
                        @php($score = $participant['subtests'][$subtest['id']] ?? null)
                        <tr>
                            <td class="center">{{ $index + 1 }}</td>
                            <td>{{ $participant['name'] }}</td>
                            <td>{{ $participant['email'] }}</td>
                            <td class="center">{{ rtrim(rtrim(number_format($score['score'] ?? 0, 2, '.', ''), '0'), '.') }}</td>
                            <td class="center">{{ $score['correct'] ?? 0 }}</td>
                            <td class="center">{{ $score['wrong'] ?? 0 }}</td>
                            <td class="center">{{ $score['unanswered'] ?? $subtest['total_questions'] }}</td>
                            <td class="center">{{ $subtest['total_questions'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endforeach
</body>
</html>
