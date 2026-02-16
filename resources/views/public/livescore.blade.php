<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Score - {{ $tryout->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="px-6 py-6 bg-slate-900 text-white">
                <p class="text-xs uppercase tracking-[0.25em] text-slate-300">Live Score Tryout</p>
                <h1 class="text-2xl md:text-3xl font-black mt-2">{{ $tryout->name }}</h1>
                <p class="text-sm text-slate-300 mt-1">
                    Update terakhir: {{ $generatedAt->translatedFormat('d F Y, H:i') }} WIB
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-800 text-slate-100 uppercase text-xs tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left">No</th>
                            <th class="px-4 py-3 text-left">Nama</th>
                            @foreach(($liveScore['subtests'] ?? []) as $subtest)
                                <th class="px-4 py-3 text-center">{{ $subtest['label'] }}</th>
                            @endforeach
                            <th class="px-4 py-3 text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse(($liveScore['rows'] ?? []) as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold">{{ $row['rank'] }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $row['name'] }}</td>
                                @foreach(($liveScore['subtests'] ?? []) as $subtest)
                                    @php
                                        $scoreValue = $row['scores'][$subtest['tryout_detail_id']] ?? null;
                                    @endphp
                                    <td class="px-4 py-3 text-center font-semibold {{ is_null($scoreValue) ? 'text-slate-400' : 'text-slate-800' }}">
                                        {{ is_null($scoreValue) ? '-' : rtrim(rtrim(number_format((float) $scoreValue, 2, '.', ''), '0'), '.') }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-center font-black text-slate-900">
                                    {{ rtrim(rtrim(number_format((float) ($row['total'] ?? 0), 2, '.', ''), '0'), '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + count($liveScore['subtests'] ?? []) }}" class="px-4 py-10 text-center text-slate-500">
                                    Belum ada data live score yang tersedia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
