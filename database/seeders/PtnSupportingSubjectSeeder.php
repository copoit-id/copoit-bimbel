<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PtnSupportingSubjectSeeder extends Seeder
{
    private const SOURCE_FILE = 'imports/DATA_MAPEL_RAPOR_PENDUKUNG.xlsx';

    public function run(): void
    {
        if (! Schema::hasTable('ptn_supporting_subjects')) {
            $this->command?->warn('Skipping PTN supporting subject import: table does not exist.');

            return;
        }

        $path = storage_path('app/'.self::SOURCE_FILE);
        if (! file_exists($path)) {
            $this->command?->warn("Skipping PTN supporting subject import: file not found at {$path}.");

            return;
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $now = now();
        $rows = [];
        $kodeProdiList = [];

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $kodeProdi = $this->cleanKodeProdi((string) $sheet->getCell("B{$rowNumber}")->getFormattedValue());

            if ($kodeProdi === '') {
                continue;
            }

            $mapelPendukung = collect([
                $this->cleanValue($sheet->getCell("E{$rowNumber}")->getFormattedValue()),
                $this->cleanValue($sheet->getCell("F{$rowNumber}")->getFormattedValue()),
            ])
                ->filter()
                ->unique()
                ->values()
                ->all();

            $rows[] = [
                'kode_prodi' => $kodeProdi,
                'perguruan_tinggi' => $this->cleanValue($sheet->getCell("A{$rowNumber}")->getFormattedValue()),
                'nama_prodi' => $this->cleanValue($sheet->getCell("C{$rowNumber}")->getFormattedValue()),
                'jenjang' => $this->cleanValue($sheet->getCell("D{$rowNumber}")->getFormattedValue()),
                'mapel_pendukung' => json_encode($mapelPendukung, JSON_UNESCAPED_UNICODE),
                'imported_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $kodeProdiList[] = $kodeProdi;
        }

        DB::transaction(function () use ($rows, $kodeProdiList): void {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('ptn_supporting_subjects')->upsert(
                    $chunk,
                    ['kode_prodi'],
                    [
                        'perguruan_tinggi',
                        'nama_prodi',
                        'jenjang',
                        'mapel_pendukung',
                        'imported_at',
                        'updated_at',
                    ]
                );
            }

            DB::table('ptn_supporting_subjects')
                ->whereNotIn('kode_prodi', $kodeProdiList)
                ->delete();
        });

        $spreadsheet->disconnectWorksheets();

        $this->command?->info('Imported '.count($rows).' PTN supporting subject rows.');
    }

    private function cleanValue(mixed $value): string
    {
        return trim((string) $value);
    }

    private function cleanKodeProdi(string $value): string
    {
        return preg_replace('/\D+/', '', $this->cleanValue($value)) ?? '';
    }
}
