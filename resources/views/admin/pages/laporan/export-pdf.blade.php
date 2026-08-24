<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Tryout</title>
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
        <div class="title">Laporan Tryout</div>
        <div class="meta">Tanggal export: {{ now()->format('d M Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tryout</th>
                <th class="text-center">IRT</th>
                <th class="text-center">Subtest</th>
                <th class="text-center">Total Soal</th>
                <th class="text-center">Durasi</th>
                <th class="text-center">Peserta</th>
                <th class="text-center">Selesai</th>
                <th class="text-center">Completion</th>
                <th class="text-center">Rata-rata {{ ($scoreDisplay ?? 'score') === 'percentage' ? 'Persentase' : 'Skor' }}</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tryouts as $tryout)
                <tr>
                    <td>{{ $tryout->name }}</td>
                    <td class="text-center">{{ $tryout->is_irt ? 'Ya' : '-' }}</td>
                    <td class="text-center">{{ $tryout->tryoutDetails->count() }}</td>
                    <td class="text-center">{{ $tryout->total_questions }}</td>
                    <td class="text-center">{{ $tryout->total_duration }} menit</td>
                    <td class="text-center">{{ $tryout->total_participants }}</td>
                    <td class="text-center">{{ $tryout->completed_participants }}</td>
                    <td class="text-center">{{ $tryout->completion_rate }}%</td>
                    <td class="text-center">
                        {{ $tryout->is_irt ? $tryout->avg_score . ' (skala 0–1000)' : (($scoreDisplay ?? 'score') === 'percentage' ? $tryout->report_score . '%' : $tryout->report_score) }}
                    </td>
                    <td class="text-center">{{ $tryout->is_active ? 'Aktif' : 'Tidak Aktif' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Belum ada data tryout</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
