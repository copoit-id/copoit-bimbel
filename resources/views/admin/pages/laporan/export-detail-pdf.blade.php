<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan {{ $tryout->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #111827;
        }
        .header {
            margin-bottom: 16px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .meta {
            font-size: 12px;
            color: #4b5563;
        }
        .summary {
            margin: 12px 0 16px;
        }
        .summary span {
            display: inline-block;
            margin-right: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
        }
        th {
            background: #f9fafb;
            text-align: left;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Laporan Tryout - {{ $tryout->name }}</div>
        <div class="meta">Tanggal export: {{ now()->format('d M Y H:i') }}</div>
        <div class="summary">
            <span>Peserta: {{ $statistics['total_participants'] }}</span>
            <span>Selesai: {{ $statistics['completed_participants'] }}</span>
            <span>Rata-rata: {{ $statistics['average_score'] }}</span>
            <span>Tertinggi: {{ $statistics['highest_score'] }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Peserta</th>
                <th>Email</th>
                <th>Attempt Token</th>
                <th class="text-center">Status</th>
                <th class="text-center">Skor</th>
                <th class="text-center">Benar</th>
                <th class="text-center">Salah</th>
                <th class="text-center">Kosong</th>
                <th class="text-center">Total Soal</th>
                <th class="text-center">Durasi</th>
                <th class="text-center">Mulai</th>
                <th class="text-center">Selesai</th>
            </tr>
        </thead>
        <tbody>
            @forelse($participants as $participant)
                @foreach($participant['attempts'] as $attempt)
                    <tr>
                        <td>{{ $participant['user']->name ?? 'User' }}</td>
                        <td>{{ $participant['user']->email ?? '-' }}</td>
                        <td>{{ $attempt->attempt_token }}</td>
                        <td class="text-center">{{ $attempt->attempt_status_label }}</td>
                        <td class="text-center">{{ round($attempt->raw_score ?? 0, 1) }}</td>
                        <td class="text-center">{{ $attempt->total_correct ?? 0 }}</td>
                        <td class="text-center">{{ $attempt->total_wrong ?? 0 }}</td>
                        <td class="text-center">{{ $attempt->total_unanswered ?? 0 }}</td>
                        <td class="text-center">{{ $attempt->question_count ?? 0 }}</td>
                        <td class="text-center">{{ $attempt->duration_label ?? '-' }}</td>
                        <td class="text-center">{{ $attempt->started_label ?? '-' }}</td>
                        <td class="text-center">{{ $attempt->finished_label ?? '-' }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="12" class="text-center">Belum ada peserta untuk tryout ini</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
