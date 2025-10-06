<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tryout;
use App\Models\UserAnswer;
use App\Models\UserPackageAcces;
use App\Models\Certificate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Facades\Image;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Services\ToeflScoringService; // ⬅️ ditambahkan

class CertificateController extends Controller
{
    public function preview($id_package, $id_tryout, $token)
    {
        $package = Package::findOrFail($id_package);
        $tryout  = Tryout::findOrFail($id_tryout);

        // Check if this is a certification tryout
        if (
            !$tryout->is_certification &&
            ($tryout->type_tryout !== 'certification' && $tryout->type_tryout !== 'computer')
        ) {
            return redirect()->back()->with('error', 'Sertifikat hanya tersedia untuk ujian sertifikasi');
        }

        // Check access
        if (!$this->hasAccess($id_package)) {
            return redirect()->route('user.package.index')->with('error', 'Anda tidak memiliki akses ke paket ini');
        }

        // Get user's completed answers
        $userAnswers = UserAnswer::with('tryoutDetail')->where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->where('status', 'completed')
            ->where('attempt_token', $token)
            ->get();

        if ($userAnswers->isEmpty()) {
            return redirect()->back()->with('error', 'Anda belum menyelesaikan ujian ini');
        }

        // ============================================================
        // PERBAIKAN INTI:
        // - TOEFL: gunakan perhitungan ETP via service (310–677).
        // - COMPUTER: total gabungan = rata-rata Word, Excel, PPT.
        // ============================================================

        if ($tryout->is_toefl == 1) {
            // Hitung skor TOEFL lewat service (bukan ambil dari record pertama)
            $toeflResults     = ToeflScoringService::processToeflScoring($userAnswers);
            $toeflTotalScore  = (int)($toeflResults['total_score'] ?? 0);

            if ($tryout->type_tryout == 'computer') {
                // (Kasus jarang: TOEFL + computer) → tetap tampilkan tiga skor computer dan total gabungan rata-rata
                $wordAnswer  = $userAnswers->firstWhere(fn($a) => optional($a->tryoutDetail)->type_subtest === 'word');
                $excelAnswer = $userAnswers->firstWhere(fn($a) => optional($a->tryoutDetail)->type_subtest === 'excel');
                $pptAnswer   = $userAnswers->firstWhere(fn($a) => optional($a->tryoutDetail)->type_subtest === 'ppt');

                $wordScore  = $wordAnswer  ? (float)$wordAnswer->score  : 0;
                $excelScore = $excelAnswer ? (float)$excelAnswer->score : 0;
                $pptScore   = $pptAnswer   ? (float)$pptAnswer->score   : 0;

                $overallPercentage = $this->calculateAverage([$wordScore, $excelScore, $pptScore]);

                $score = [
                    'score'       => $overallPercentage,
                    'word_score'  => (int)round($wordScore),
                    'excel_score' => (int)round($excelScore),
                    'ppt_score'   => (int)round($pptScore),
                ];
            } else {
                // TOEFL murni: gunakan total ETP + tampilkan section scaled (sudah disimpan per-subtest juga)
                $writingAnswer   = $userAnswers->firstWhere(fn($a) => optional($a->tryoutDetail)->type_subtest === 'writing');
                $listeningAnswer = $userAnswers->firstWhere(fn($a) => optional($a->tryoutDetail)->type_subtest === 'listening');
                $readingAnswer   = $userAnswers->firstWhere(fn($a) => optional($a->tryoutDetail)->type_subtest === 'reading');

                // Fallback pakai nilai yang ada di userAnswer->score (yang sebelumnya sudah diisi scaled per section)
                $listeningScore = $listeningAnswer ? (int)$listeningAnswer->score : (int)($toeflResults['section1']['scaled_score'] ?? 0);
                $writingScore   = $writingAnswer   ? (int)$writingAnswer->score   : (int)($toeflResults['section2']['scaled_score'] ?? 0);
                $readingScore   = $readingAnswer   ? (int)$readingAnswer->score   : (int)($toeflResults['section3']['scaled_score'] ?? 0);

                $score = [
                    'score'           => $toeflTotalScore,
                    'listening_score' => $listeningScore,
                    'writing_score'   => $writingScore,
                    'reading_score'   => $readingScore,
                ];

                $overallPercentage = $toeflTotalScore;
            }
        } else {
            if ($tryout->type_tryout == 'computer') {
                // Computer: total gabungan = rata-rata Word, Excel, PPT
                $wordScore  = optional($userAnswers->firstWhere(fn($a) => optional($a->tryoutDetail)->type_subtest === 'word'))->score ?? 0;
                $excelScore = optional($userAnswers->firstWhere(fn($a) => optional($a->tryoutDetail)->type_subtest === 'excel'))->score ?? 0;
                $pptScore   = optional($userAnswers->firstWhere(fn($a) => optional($a->tryoutDetail)->type_subtest === 'ppt'))->score ?? 0;

                $overallPercentage = $this->calculateAverage([(float)$wordScore, (float)$excelScore, (float)$pptScore]);

                $score = [
                    'score'       => $overallPercentage,
                    'word_score'  => (int)round($wordScore),
                    'excel_score' => (int)round($excelScore),
                    'ppt_score'   => (int)round($pptScore),
                ];
            } else {
                // Regular certification (non-computer): pertahankan perhitungan existing
                $writingScore   = optional($userAnswers->firstWhere(fn($a) => optional($a->tryoutDetail)->type_subtest === 'writing'))->score ?? 0;
                $listeningScore = optional($userAnswers->firstWhere(fn($a) => optional($a->tryoutDetail)->type_subtest === 'listening'))->score ?? 0;
                $readingScore   = optional($userAnswers->firstWhere(fn($a) => optional($a->tryoutDetail)->type_subtest === 'reading'))->score ?? 0;

                $overallPercentage = $this->calculateOverallScore($userAnswers); // dibiarkan sesuai logic lama (menjumlah)
                $score = [
                    'score'           => $overallPercentage,
                    'listening_score' => (int)$listeningScore,
                    'writing_score'   => (int)$writingScore,
                    'reading_score'   => (int)$readingScore
                ];
            }
        }

        // Get or create certificate
        $existingCertificate = $this->getOrCreateCertificate($package, $tryout, $userAnswers, $score, $token);
        $token = $token;

        return view('user.pages.certificate.preview', compact(
            'package',
            'tryout',
            'token',
            'existingCertificate',
            'overallPercentage'
        ));
    }

    public function view($certificateId, $token = null)
    {
        // Jika token adalah 'public', allow akses tanpa pengecekan user
        if ($token === 'public') {
            $certificate = Certificate::where('certificate_id', $certificateId)
                ->where('status', 'active')
                ->firstOrFail();
        } else {
            $certificate = $this->getCertificate($certificateId);
        }

        // Get tryout to determine certificate type
        $tryout = Tryout::find($certificate->tryout_id);

        if ($tryout && $tryout->type_tryout === 'computer') {
            return $this->viewComputerCertificate($certificate);
        } else {
            return $this->viewCertificationCertificate($certificate);
        }
    }

    public function download($certificateId, $token = null)
    {
        // Jika token adalah 'public', allow akses tanpa pengecekan user
        if ($token === 'public') {
            $certificate = Certificate::where('certificate_id', $certificateId)
                ->where('status', 'active')
                ->firstOrFail();
        } else {
            $certificate = $this->getCertificate($certificateId);
        }

        // Get tryout to determine certificate type
        $tryout = Tryout::find($certificate->tryout_id);

        if ($tryout && $tryout->type_tryout === 'computer') {
            return $this->downloadComputerCertificate($certificate);
        } else {
            return $this->downloadCertificationCertificate($certificate);
        }
    }

    private function viewCertificationCertificate($certificate)
    {
        $templatePath = storage_path('app/private/certificates/certificate-template.png');

        if (!file_exists($templatePath)) {
            abort(404, 'Template sertifikat tidak ditemukan.');
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->read($templatePath);

        // Get real data from certificate
        $metadata = is_array($certificate->metadata) ? $certificate->metadata : json_decode($certificate->metadata, true);
        $userName = $metadata['user_name'] ?? 'Unknown User';
        $dateOfBirth = $certificate->date_of_birth instanceof \Carbon\Carbon ? $certificate->date_of_birth : \Carbon\Carbon::parse($certificate->date_of_birth);

        // Add certificate number
        $image->text($certificate->certificate_number, 1805, 580, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-Bold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-Bold.ttf');
            }
            $font->size(38);
            $font->color('#2B516B');
            $font->align('center');
            $font->valign('top');
        });

        // Add user name
        $image->text($userName, 1390, 965, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Add date of birth
        $image->text($dateOfBirth->format('F, d Y'), 1390, 1045, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Add subtest scores
        $listeningScore = $certificate->metadata['listening_score'] ?? '-';
        $image->text($listeningScore, 1985, 1365, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        $readingScore = $certificate->metadata['reading_score'] ?? '-';
        $image->text($readingScore, 1985, 1465, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        $writingScore = $certificate->metadata['writing_score'] ?? '-';
        $image->text($writingScore, 1985, 1565, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Overall score
        $overallScore = $certificate->metadata['score'] ?? '-';
        $image->text($overallScore, 1985, 1683, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-Bold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        $fileName = 'qrcode-' . $certificate->certificate_number . '.png';
        $filePath = 'qrcodes/' . $fileName;

        Storage::disk('public')->put($filePath, QrCode::format('png')->size(300)->generate($certificate->certificate_number));
        $publicQrPath = public_path('storage/' . $filePath);
        $image->place($publicQrPath, 'bottom-right', 100, 100);

        return response($image->toPng()->toString(), 200)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'inline; filename="sertifikat.png"');
    }

    private function getPredicate($score)
    {
        if ($score >= 80) {
            return 'Baik Sekali';
        } else if ($score >= 60) {
            return 'Baik';
        } else if ($score >= 40) {
            return 'Cukup';
        } else if ($score >= 20) {
            return 'Kurang';
        } else {
            return 'Sangat Kurang';
        }
    }

    private function viewComputerCertificate($certificate)
    {
        $templatePath = storage_path('app/private/certificates/certificate-template-computer.png');

        if (!file_exists($templatePath)) {
            abort(404, 'Template sertifikat computer tidak ditemukan.');
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->read($templatePath);

        // Get real data from certificate
        $metadata = is_array($certificate->metadata) ? $certificate->metadata : json_decode($certificate->metadata, true);
        $userName = $metadata['user_name'] ?? 'Unknown User';
        $dateOfBirth = $certificate->date_of_birth instanceof \Carbon\Carbon ? $certificate->date_of_birth : \Carbon\Carbon::parse($certificate->date_of_birth);

        // Add certificate number
        $image->text($certificate->certificate_number, 1805, 580, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-Bold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(38);
            $font->color('#2B516B');
            $font->align('center');
            $font->valign('top');
        });

        // Add user name
        $image->text($userName, 1790, 1065, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('center');
            $font->valign('top');
        });

        // keterangan
        $keterangan = 'Berdasarkan Hasil Ujian Sertifikasi Komputer Perkantoran (Microsoft Office)
yang dilaksanakan pada Tanggal, 24 Juli 2025 dengan perolehan Nilai dan Predikat sebagai berikut:';

        $wrapped = wordwrap($keterangan, 100, "\n");

        $y = 1200;
        foreach (explode("\n", $wrapped) as $line) {
            $image->text($line, 1790, $y, function ($font) {
                if (env('APP_ENV') == 'local') {
                    $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
                } else {
                    $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
                }
                $font->size(46);
                $font->color('#2B516B');
                $font->align('center');
                $font->valign('top');
            });

            $y += 70; // jarak antar baris, sesuaikan dengan ukuran font
        }

        // Word Score
        $wordScore = $certificate->metadata['word_score'] ?? '-';
        $image->text($wordScore, 1985, 1550, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });
        $image->text($this->getPredicate($wordScore), 2300, 1550, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Excel Score
        $excelScore = $certificate->metadata['excel_score'] ?? '-';
        $image->text($excelScore, 1985, 1650, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });
        $image->text($this->getPredicate($excelScore), 2300, 1650, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });
        $image->text($this->getPredicate($excelScore), 2300, 1650, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });
        $image->text($this->getPredicate($excelScore), 2300, 1650, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // PowerPoint Score
        $pptScore = $certificate->metadata['ppt_score'] ?? '-';
        $image->text($pptScore, 1985, 1750, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });
        $image->text($this->getPredicate($pptScore), 2300, 1750, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // tanggal
        $width = $image->width();

        $image->text(
            $certificate->issued_date->format('F, d Y'),
            $width / 2,
            2050,
            function ($font) {
                if (env('APP_ENV') == 'local') {
                    $font->file(public_path('/fonts/Poppins-Medium.ttf'));
                } else {
                    $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-Medium.ttf');
                }
                $font->size(46);
                $font->color('#2B516B');
                $font->align('center'); // biar teks center
                $font->valign('top');
            }
        );

        $fileName = 'qrcode-' . $certificate->certificate_number . '.png';
        $filePath = 'qrcodes/' . $fileName;

        Storage::disk('public')->put($filePath, QrCode::format('png')->size(300)->generate($certificate->certificate_number));
        $publicQrPath = public_path('storage/' . $filePath);
        $image->place($publicQrPath, 'bottom-right', 100, 120);

        return response($image->toPng()->toString(), 200)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'inline; filename="sertifikat-computer.png"');
    }

    private function downloadCertificationCertificate($certificate)
    {
        $templatePath = storage_path('app/private/certificates/certificate-template.png');

        if (!file_exists($templatePath)) {
            abort(404, 'Template sertifikat tidak ditemukan.');
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->read($templatePath);

        // Get real data (sama seperti view)
        $metadata = is_array($certificate->metadata) ? $certificate->metadata : json_decode($certificate->metadata, true);
        $userName = $metadata['user_name'] ?? 'Unknown User';
        $dateOfBirth = $certificate->date_of_birth instanceof \Carbon\Carbon ? $certificate->date_of_birth : \Carbon\Carbon::parse($certificate->date_of_birth);

        // Add certificate number
        $image->text($certificate->certificate_number, 1805, 580, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-Bold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-Bold.ttf');
            }
            $font->size(38);
            $font->color('#2B516B');
            $font->align('center');
            $font->valign('top');
        });

        // Add user name
        $image->text($userName, 1390, 965, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Add date of birth
        $image->text($dateOfBirth->format('F, d Y'), 1390, 1045, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Subtest scores
        $listeningScore = $certificate->metadata['listening_score'] ?? '-';
        $image->text($listeningScore, 1985, 1365, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        $readingScore = $certificate->metadata['reading_score'] ?? '-';
        $image->text($readingScore, 1985, 1465, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        $writingScore = $certificate->metadata['writing_score'] ?? '-';
        $image->text($writingScore, 1985, 1565, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Overall score
        $overallScore = $certificate->metadata['score'] ?? '-';
        $image->text($overallScore, 1985, 1683, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-Bold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // QR Code (samakan dengan view)
        $fileName = 'qrcode-' . $certificate->certificate_number . '.png';
        $filePath = 'qrcodes/' . $fileName;

        Storage::disk('public')->put($filePath, QrCode::format('png')->size(300)->generate($certificate->certificate_number));
        $publicQrPath = public_path('storage/' . $filePath);
        $image->place($publicQrPath, 'bottom-right', 100, 100);

        // Generate filename
        $certificateName = 'Certificate_' . str_replace(['/', '-'], '_', $certificate->certificate_number) . '.png';

        return response($image->toPng()->toString(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $certificateName . '"',
            'Cache-Control' => 'no-cache, must-revalidate'
        ]);
    }

    private function downloadComputerCertificate($certificate)
    {
        $templatePath = storage_path('app/private/certificates/certificate-template-computer.png');

        if (!file_exists($templatePath)) {
            abort(404, 'Template sertifikat computer tidak ditemukan.');
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->read($templatePath);

        // Get real data (sama seperti view)
        $metadata = is_array($certificate->metadata) ? $certificate->metadata : json_decode($certificate->metadata, true);
        $userName = $metadata['user_name'] ?? 'Unknown User';
        $dateOfBirth = $certificate->date_of_birth instanceof \Carbon\Carbon ? $certificate->date_of_birth : \Carbon\Carbon::parse($certificate->date_of_birth);

        // Add certificate number
        $image->text($certificate->certificate_number, 1805, 580, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-Bold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(38);
            $font->color('#2B516B');
            $font->align('center');
            $font->valign('top');
        });

        // Add user name (centered like preview)
        $image->text($userName, 1790, 1065, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('center');
            $font->valign('top');
        });

        // Catatan: Sertifikat Computer tidak menampilkan tanggal lahir

        $keterangan = 'Berdasarkan Hasil Ujian Sertifikasi Komputer Perkantoran (Microsoft Office)
yang dilaksanakan pada Tanggal, 24 Juli 2025 dengan perolehan Nilai dan Predikat sebagai berikut:';

        $wrapped = wordwrap($keterangan, 100, "\n");

        $y = 1200;
        foreach (explode("\n", $wrapped) as $line) {
            $image->text($line, 1790, $y, function ($font) {
                if (env('APP_ENV') == 'local') {
                    $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
                } else {
                    $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
                }
                $font->size(46);
                $font->color('#2B516B');
                $font->align('center');
                $font->valign('top');
            });

            $y += 70; // jarak antar baris, sesuaikan dengan ukuran font
        }

        // Word Score (samakan dengan view)
        $wordScore = $certificate->metadata['word_score'] ?? '-';
        $image->text($wordScore, 1985, 1550, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });
        $image->text($this->getPredicate($wordScore), 2300, 1550, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Excel Score
        $excelScore = $certificate->metadata['excel_score'] ?? '-';
        $image->text($excelScore, 1985, 1650, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });
        $image->text($this->getPredicate($excelScore), 2300, 1650, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // PowerPoint Score
        $pptScore = $certificate->metadata['ppt_score'] ?? '-';
        $image->text($pptScore, 1985, 1750, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });
        $image->text($this->getPredicate($pptScore), 2300, 1750, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->file(public_path('/fonts/Poppins-SemiBold.ttf'));
            } else {
                $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Catatan: Tidak menampilkan Overall pada sertifikat Computer (harusnya hanya 3 nilai)

        $width = $image->width();
        $image->text(
            $certificate->issued_date->format('F, d Y'),
            $width / 2,
            2050,
            function ($font) {
                if (env('APP_ENV') == 'local') {
                    $font->file(public_path('/fonts/Poppins-Medium.ttf'));
                } else {
                    $font->file('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-Medium.ttf');
                }
                $font->size(46);
                $font->color('#2B516B');
                $font->align('center'); // biar teks center
                $font->valign('top');
            }
        );

        // QR Code dan filename (samakan dengan view)
        $fileName = 'qrcode-' . $certificate->certificate_number . '.png';
        $filePath = 'qrcodes/' . $fileName;

        Storage::disk('public')->put($filePath, QrCode::format('png')->size(300)->generate($certificate->certificate_number));
        $publicQrPath = public_path('storage/' . $filePath);
        $image->place($publicQrPath, 'bottom-right', 100, 120);

        // Generate filename
        $certificateName = 'Certificate_Computer_' . str_replace(['/', '-'], '_', $certificate->certificate_number) . '.png';

        return response($image->toPng()->toString(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $certificateName . '"',
            'Cache-Control' => 'no-cache, must-revalidate'
        ]);
    }

    // Private helper methods
    private function hasAccess($packageId)
    {
        return UserPackageAcces::where('user_id', Auth::id())
            ->where('package_id', $packageId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhere('end_date', '>', Carbon::now());
            })
            ->exists();
    }

    /*************  ✨ Windsurf Command ⭐  *************/
    /**
     * Get or create a new certificate for the given tryout and user.
     *
     * @param Package $package
     * @param Tryout $tryout
     * @param Collection $userAnswers
     * @param array $score
     * @return Certificate
     */
    /*******  237ca336-8bc5-41fe-a2b3-e5453a5fd923  *******/
    private function getOrCreateCertificate($package, $tryout, $userAnswers, $score, $token)
    {
        // Check existing
        $certificate = Certificate::where('tryout_id', $tryout->tryout_id)
            ->whereJsonContains('metadata->user_id', (string)Auth::id())
            ->whereJsonContains('metadata->token', $token)
            ->first();

        if ($certificate) {
            return $certificate;
        }

        // Create new
        $certificateNumber = $this->generateCertificateNumber();

        if ($tryout->type_tryout == 'computer') {
            return Certificate::create([
                'certificate_number' => $certificateNumber,
                'certificate_name' => $tryout->name,
                'date_of_birth' => Auth::user()->date_of_birth ?? Carbon::now()->subYears(25),
                'description' => 'Certificate of completion for TOEFL ITP test',
                'institution_name' => 'CPNS Academy',
                'issued_date' => Carbon::now(),
                'expired_date' => Carbon::now()->addYear(),
                'verification_code' => Str::random(16),
                'status' => 'active',
                'issued_by' => Auth::id(),
                'tryout_id' => $tryout->tryout_id,
                'metadata' => [
                    'user_id'       => (string)Auth::id(),
                    'user_name'     => Auth::user()->name,
                    'user_email'    => Auth::user()->email,
                    'package_name'  => $package->name,
                    'token'         => $token,
                    'word_score'    => $score['word_score'],
                    'excel_score'   => $score['excel_score'],
                    'ppt_score'     => $score['ppt_score'],
                    'score'         => $score['score'], // total gabungan (rata-rata)
                    'exam_date'     => $userAnswers->min('started_at'),
                    'completion_date' => $userAnswers->max('finished_at'),
                ]
            ]);
        } else {
            return Certificate::create([
                'certificate_number' => $certificateNumber,
                'certificate_name' => $tryout->name,
                'date_of_birth' => Auth::user()->date_of_birth ?? Carbon::now()->subYears(25),
                'description' => 'Certificate of completion for TOEFL ITP test',
                'institution_name' => 'CPNS Academy',
                'issued_date' => Carbon::now(),
                'expired_date' => Carbon::now()->addYear(),
                'verification_code' => Str::random(16),
                'status' => 'active',
                'issued_by' => Auth::id(),
                'tryout_id' => $tryout->tryout_id,
                'metadata' => [
                    'user_id'         => (string)Auth::id(),
                    'user_name'       => Auth::user()->name,
                    'user_email'      => Auth::user()->email,
                    'package_name'    => $package->name,
                    'token'           => $token,
                    'listening_score' => $score['listening_score'] ?? null,
                    'writing_score'   => $score['writing_score']   ?? null,
                    'reading_score'   => $score['reading_score']   ?? null,
                    'score'           => $score['score'], // TOEFL total (310–677) atau total existing
                    'exam_date'       => $userAnswers->min('started_at'),
                    'completion_date' => $userAnswers->max('finished_at'),
                ]
            ]);
        }
    }

    private function getCertificate($certificateId)
    {
        return Certificate::where('certificate_id', $certificateId)
            ->whereJsonContains('metadata->user_id', (string)Auth::id())
            ->firstOrFail();
    }

    private function generateCertificateNumber()
    {
        $maxAttempts = 100;
        $attempts = 0;

        do {
            $attempts++;
            $randomNumber = rand(1000, 9999);
            $month = date('m');
            $year = date('Y');
            $certificateNumber = "{$randomNumber}/CA/{$month}/{$year}";

            $exists = Certificate::where('certificate_number', $certificateNumber)->exists();

            if (!$exists) {
                return $certificateNumber;
            }
        } while ($exists && $attempts < $maxAttempts);

        $timestamp = time();
        $month = date('m');
        $year = date('Y');
        return "{$timestamp}/CA/{$month}/{$year}";
    }

    private function calculateOverallScore($userAnswers)
    {
        $totalScore = 0;

        foreach ($userAnswers as $userAnswer) {
            $totalScore += (float) $userAnswer->score;
        }

        return $totalScore;
    }

    // ⬇️ Helper baru (dipakai khusus total gabungan untuk "computer")
    private function calculateAverage(array $values): float
    {
        $nums = array_filter(array_map(fn($v) => is_numeric($v) ? (float)$v : null, $values), fn($v) => $v !== null);
        $count = count($nums);
        if ($count === 0) return 0.0;
        return array_sum($nums) / $count;
    }
}
