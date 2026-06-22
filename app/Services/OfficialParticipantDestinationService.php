<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OfficialParticipantDestinationService
{
    public function institutions(string $source): Collection
    {
        $institutions = collect();

        foreach ($this->sources($source) as $sourceKey) {
            $endpoint = $sourceKey === 'snbt'
                ? 'https://snpmb.id/proxy-ptn-sb.php'
                : 'https://snpmb.id/proxy-ptn-sn.php';

            try {
                $response = Http::timeout(15)->retry(2, 500)->get($endpoint);
            } catch (\Throwable $e) {
                report($e);
                continue;
            }

            if (! $response->successful() || ! is_array($response->json())) {
                continue;
            }

            $institutions = $institutions->merge(
                collect($response->json())
                    ->filter(fn($ptn) => is_array($ptn) && !empty($ptn['id_ptn']) && !empty($ptn['nama']))
                    ->map(fn($ptn) => [
                        'id_ptn' => $ptn['id_ptn'],
                        'kode_ptn' => $ptn['kode_ptn'] ?? null,
                        'nama' => trim((string) $ptn['nama']),
                        'source' => $sourceKey,
                    ])
            );
        }

        return $institutions
            ->groupBy(fn($ptn) => Str::slug($ptn['nama']))
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'id_ptn' => $first['id_ptn'],
                    'kode_ptn' => $first['kode_ptn'],
                    'nama' => $first['nama'],
                    'source' => $items->pluck('source')->unique()->implode('+'),
                    'source_ids' => $items->pluck('id_ptn', 'source')->all(),
                ];
            })
            ->sortBy('nama')
            ->values();
    }

    public function programs(string $source, string $defaultPtn, ?string $snbtPtn = null, ?string $snbpPtn = null): Collection
    {
        $programs = collect();

        foreach ($this->sources($source) as $sourceKey) {
            $endpoint = $sourceKey === 'snbt'
                ? 'https://snpmb.id/proxy-prodi-sb.php'
                : 'https://snpmb.id/proxy-prodi-sn.php';
            $ptnId = $sourceKey === 'snbt'
                ? ($snbtPtn ?: $defaultPtn)
                : ($snbpPtn ?: $defaultPtn);

            try {
                $response = Http::timeout(15)->retry(2, 500)->get($endpoint, [
                    'ptn' => $ptnId,
                ]);
            } catch (\Throwable $e) {
                report($e);
                continue;
            }

            if (! $response->successful() || ! is_array($response->json())) {
                continue;
            }

            $programs = $programs->merge(
                collect($response->json())
                    ->filter(fn($prodi) => is_array($prodi) && !empty($prodi['nama']))
                    ->map(function ($prodi) use ($sourceKey) {
                        $name = trim(implode(' ', array_filter([
                            $prodi['jenjang'] ?? null,
                            $prodi['nama'] ?? null,
                        ])));

                        return [
                            'id_prodi' => $prodi['id_prodi'] ?? null,
                            'kode_prodi' => $prodi['kode_prodi'] ?? null,
                            'nama' => $name,
                            'jenjang' => $prodi['jenjang'] ?? null,
                            'source' => $sourceKey,
                        ];
                    })
                    ->filter(fn($prodi) => $prodi['nama'] !== '')
            );
        }

        return $programs
            ->unique(fn($prodi) => Str::slug($prodi['nama']))
            ->sortBy('nama')
            ->values();
    }

    private function sources(string $source): array
    {
        return $source === 'all' ? ['snbt', 'snbp'] : [$source];
    }
}
