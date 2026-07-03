<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Leaderboard {{ $tryout->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
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
        <div class="title">Leaderboard - {{ $tryout->name }}</div>
        <div class="meta">Paket: {{ $package->name }}</div>
        <div class="meta">Tanggal export: {{ now()->format('d M Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">Peringkat</th>
                <th>Peserta</th>
                <th>Email</th>
                <th class="text-center">Skor</th>
                <th class="text-center">Skor Maks</th>
                <th class="text-center">Status</th>
                <th class="text-center">Waktu Selesai</th>
                <th class="text-center">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rankings as $index => $ranking)
                @php
                    $score = round($ranking->raw_score ?? 0);
                    $maxScore = round($ranking->max_score ?? 0);
                    $status = match ($ranking->status ?? null) {
                        'completed' => $ranking->is_passed ? 'Lulus' : 'Tidak Lulus',
                        'pending_release' => 'Menunggu Rilis',
                        'in_progress' => 'Sedang Dikerjakan',
                        'abandoned' => 'Ditinggalkan',
                        default => ucfirst((string) ($ranking->status ?? '-')),
                    };
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $ranking->user->name ?? 'Unknown User' }}</td>
                    <td>{{ $ranking->user->email ?? '-' }}</td>
                    <td class="text-center">{{ ($ranking->status ?? null) === 'completed' ? $score : '-' }}</td>
                    <td class="text-center">{{ ($ranking->status ?? null) === 'completed' && $maxScore > 0 ? $maxScore : '-' }}</td>
                    <td class="text-center">{{ $status }}</td>
                    <td class="text-center">
                        {{ $ranking->finished_at ? $ranking->finished_at->format('H:i') : '-' }}
                    </td>
                    <td class="text-center">
                        {{ $ranking->created_at ? $ranking->created_at->format('d M Y H:i') : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Belum ada peserta yang mengakses tryout ini</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
