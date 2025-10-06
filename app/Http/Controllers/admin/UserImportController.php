<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportUsersFromCsv;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\LazyCollection;

class UserImportController extends Controller
{
    public function showImportForm()
    {
        return view('admin.pages.user.import');
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="user_import_template.csv"',
        ];

        // Biarkan template seperti semula
        $columns = [
            'name',
            'email',
            'username',
            'password',
            'date_of_birth',
            'status'
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');

            // Write header
            fputcsv($file, $columns, ';');

            // Write sample data
            fputcsv($file, [
                'John Doe',
                'john.doe@example.com',
                'johndoe',
                'password123',
                '01/01/1990',
                'active'
            ], ';');

            fputcsv($file, [
                'Jane Smith',
                'jane.smith@example.com',
                'janesmith',
                'password123',
                '15/05/1985',
                'active'
            ], ';');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:20480', // 20MB
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $file = $request->file('csv_file');
            $path = $file->getRealPath();

            // STREAMING baca CSV (hemat RAM)
            $rows = LazyCollection::make(function () use ($path) {
                $handle = fopen($path, 'r');
                if ($handle === false) return;

                // Deteksi delimiter ; atau ,
                $delimiter = $this->detectDelimiter($handle);

                // Header
                $header = fgetcsv($handle, 0, $delimiter);
                if ($header === false) {
                    fclose($handle);
                    return;
                }
                yield $header;

                // Row lain
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    yield $row;
                }
                fclose($handle);
            });

            // Ambil header pertama
            $header = null;
            $processed = 0;
            $imported = 0;
            $skipped  = 0;

            $now = now();

            // Proses per CHUNK (500–1000 baris)
            $rows->chunk(1000)->each(function ($chunk) use (&$header, &$processed, &$imported, &$skipped, $now) {
                $batch = [];

                foreach ($chunk as $row) {
                    if ($header === null) {
                        // set header sekali di chunk pertama
                        $header = $row;
                        continue;
                    }

                    // Samakan panjang row dgn header
                    if (count($row) < count($header)) {
                        $row = array_pad($row, count($header), '');
                    } elseif (count($row) > count($header)) {
                        $row = array_slice($row, 0, count($header));
                    }

                    $data = @array_combine($header, $row) ?: [];

                    // Ambil field (fallback berbagai nama kolom)
                    $email    = trim($data['email'] ?? $data['Email'] ?? '');
                    $name     = trim($data['name'] ?? $data['Name'] ?? $data['nama'] ?? '');
                    $username = trim($data['username'] ?? $data['Username'] ?? '');
                    // PASSWORD SUDAH HASH → pakai apa adanya
                    $passwordHash = trim($data['password'] ?? $data['Password'] ?? '');
                    $dobStr   = trim($data['date_of_birth'] ?? $data['birth_date'] ?? $data['tanggal_lahir'] ?? '');
                    $status   = trim($data['status'] ?? $data['Status'] ?? 'active');

                    // (Baru) created_at & updated_at dari CSV jika ada
                    $createdCsv = $data['created_at'] ?? null;
                    $updatedCsv = $data['updated_at'] ?? null;

                    // Validasi minimal
                    if ($email === '' || $name === '' || $passwordHash === '') {
                        $skipped++;
                        $processed++;
                        continue;
                    }

                    // Username kandidat (tanpa cek DB per-row)
                    $username = $username !== '' ? Str::slug($username, '') : Str::slug($name, '');
                    if ($username === '') $username = 'user';

                    $batch[] = [
                        'name'              => $name,
                        'username'          => $username,
                        'email'             => strtolower($email),
                        'password'          => $passwordHash, // ← TIDAK DI-HASH ULANG

                        // PENTING: tabel pakai 'birthday', isi dari 'birth_date' (atau alias lain) CSV
                        'birthday'          => $this->parseBirthDate($dobStr),

                        'role'              => 'user',
                        'status'            => $this->mapStatus($status),
                        'email_verified_at' => $now,

                        // Pakai created_at/updated_at dari CSV jika ada, else now()
                        'created_at'        => $createdCsv ? $this->parseDateTime($createdCsv) : $now,
                        'updated_at'        => $updatedCsv ? $this->parseDateTime($updatedCsv) : $now,
                    ];
                    $processed++;
                }

                if (empty($batch)) return;

                // Unikkan username DI DALAM CHUNK (hindari tabrakan intra-chunk)
                $seen = [];
                foreach ($batch as &$row) {
                    $u = $row['username'];
                    if (!isset($seen[$u])) {
                        $seen[$u] = 0;
                    } else {
                        $seen[$u]++;
                        $row['username'] = $u . $seen[$u];
                    }
                }
                unset($row);

                // MASAL INSERT: cepat & hemat
                DB::transaction(function () use (&$batch, &$imported) {
                    // Skip kalau email sudah ada (mengandalkan unique index di email)
                    $affected = DB::table('users')->insertOrIgnore($batch);
                    $imported += (int) $affected;

                    // Jika ingin UPDATE saat email sudah ada, ganti ke upsert (optional):
                    /*
                    $affected = DB::table('users')->upsert(
                        $batch,
                        ['email'], // key unik
                        ['name','birthday','status','updated_at'] // jangan update username/password
                    );
                    $imported += (int) $affected;
                    */
                });
            });

            $msg = "Import selesai. Diproses: {$processed}, Berhasil masuk: {$imported}, Dilewati: {$skipped}.";
            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    /**
     * Deteksi delimiter ; atau ,
     */
    private function detectDelimiter($handle): string
    {
        $pos = ftell($handle);
        $line = fgets($handle);
        $semicolon = substr_count($line, ';');
        $comma = substr_count($line, ',');
        // reset pointer
        fseek($handle, 0);
        return $semicolon >= $comma ? ';' : ',';
    }

    private function parseBirthDate($birthDate)
    {
        if (empty($birthDate) || strtoupper($birthDate) === 'NULL') return null;

        $formats = ['d/m/y', 'd/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y H:i'];
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $birthDate);
            if ($date !== false) return $date->format('Y-m-d');
        }
        try {
            return \Carbon\Carbon::parse($birthDate)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseDateTime($dateTime)
    {
        if (empty($dateTime) || $dateTime === 'NULL') {
            return now();
        }

        try {
            // Format: 22/02/24 23.36
            if (preg_match('/(\d{2})\/(\d{2})\/(\d{2})\s+(\d{2})\.(\d{2})/', $dateTime, $m)) {
                $day = $m[1];
                $month = $m[2];
                $year = '20' . $m[3]; // Assuming 2000s
                $hour = $m[4];
                $minute = $m[5];
                return Carbon::createFromFormat('Y-m-d H:i:s', "$year-$month-$day $hour:$minute:00");
            }
            return Carbon::parse($dateTime);
        } catch (\Exception $e) {
            return now();
        }
    }

    private function mapStatus($status)
    {
        return strtolower($status) === 'active' ? 'aktif' : 'nonaktif';
    }
}