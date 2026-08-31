<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan {{ $tryout->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9px; color: #111827; }
        .header { margin-bottom: 16px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .meta { color: #4b5563; margin-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #e5e7eb; padding: 5px 4px; vertical-align: middle; }
        th { background: #eff6ff; color: #1e3a8a; text-align: center; font-weight: bold; }
        .center { text-align: center; }
        .name { width: 18%; }
        .email { width: 23%; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Laporan Tryout — {{ $tryout->name }}</div>
        <div class="meta">Jumlah peserta: {{ count($participants) }}</div>
        <div class="meta">Tanggal export: {{ now()->format('d M Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%">No.</th>
                <th class="name">Nama Peserta</th>
                <th class="email">Email</th>
                @foreach($subtests as $subtest)
                    <th>Nilai {{ $subtest['alias'] }}</th>
                @endforeach
                <th>Total Nilai</th>
                <th>Durasi</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($participants as $index => $participant)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $participant['name'] }}</td>
                    <td>{{ $participant['email'] }}</td>
                    @foreach($subtests as $subtest)
                        <td class="center">
                            {{ $participant['subtests'][$subtest['id']]['display'] ?? '0' }}
                        </td>
                    @endforeach
                    <td class="center">{{ $participant['total_score_display'] }}</td>
                    <td class="center">
                        @if($participant['started_at'] && $participant['finished_at'])
                            @php($seconds = $participant['started_at']->diffInSeconds($participant['finished_at']))
                            {{ sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="center">{{ $participant['status_label'] }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 6 + count($subtests) }}" class="center">Belum ada peserta yang mengerjakan tryout ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
