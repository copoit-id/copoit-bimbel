<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Services\TkaCertificateRenderer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class CertificateValidationController extends Controller
{
    public function __construct(
        private readonly TkaCertificateRenderer $tkaCertificateRenderer
    ) {}

    public function index()
    {
        return view('user.pages.certificate.validation');
    }

    public function validateCertificate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'certificate_number' => 'required|string|min:3|max:50',
        ], [
            'certificate_number.required' => 'Nomor sertifikat wajib diisi',
            'certificate_number.string' => 'Nomor sertifikat harus berupa teks',
            'certificate_number.min' => 'Nomor sertifikat minimal 3 karakter',
            'certificate_number.max' => 'Nomor sertifikat maksimal 50 karakter',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $certificateNumber = trim($request->certificate_number);

        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->where('status', 'active')
            ->first();

        if (! $certificate) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Sertifikat tidak ditemukan atau tidak aktif.',
            ]);
        }

        $metadata = is_array($certificate->metadata) ? $certificate->metadata : json_decode($certificate->metadata, true);

        $certificateData = [
            'certificate_number' => $certificate->certificate_number,
            'certificate_name' => $certificate->certificate_name,
            'holder_name' => $metadata['user_name'] ?? 'Unknown',
            'institution_name' => $certificate->institution_name,
            'issued_date' => $certificate->issued_date->format('d F Y'),
            'expired_date' => $certificate->expired_date->format('d F Y'),
            'verification_code' => substr($certificate->verification_code, 0, 8).'...',
            'package_name' => $metadata['package_name'] ?? 'Unknown Package',
            'exam_date' => isset($metadata['exam_date']) ? Carbon::parse($metadata['exam_date'])->format('d F Y') : $certificate->issued_date->format('d F Y'),
            'is_expired' => $certificate->expired_date->isPast(),
            'preview_url' => URL::temporarySignedRoute(
                'user.certificate.validation.preview',
                now()->addMinutes(10),
                ['certificate_id' => $certificate->certificate_id]
            ),
            'download_url' => URL::temporarySignedRoute(
                'user.certificate.validation.download',
                now()->addMinutes(10),
                ['certificate_id' => $certificate->certificate_id]
            ),
        ];

        return response()->json([
            'success' => true,
            'valid' => true,
            'message' => 'Sertifikat valid dan ditemukan',
            'data' => $certificateData,
        ]);
    }

    public function downloadCertificate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'certificate_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor sertifikat tidak valid',
            ], 422);
        }

        $certificate = Certificate::where('certificate_number', $request->certificate_number)
            ->where('status', 'active')
            ->first();

        if (! $certificate) {
            return response()->json([
                'success' => false,
                'message' => 'Sertifikat tidak ditemukan',
            ], 404);
        }

        $downloadUrl = URL::temporarySignedRoute(
            'user.certificate.validation.download',
            now()->addMinutes(10),
            ['certificate_id' => $certificate->certificate_id]
        );

        return response()->json([
            'success' => true,
            'download_url' => $downloadUrl,
        ]);
    }

    public function downloadById($certificate_id)
    {
        $certificate = Certificate::where('certificate_id', $certificate_id)
            ->where('status', 'active')
            ->firstOrFail();

        if ($this->tkaCertificateRenderer->usesTkaLayout($certificate)) {
            return $this->tkaCertificateRenderer->response($certificate, true);
        }

        $templatePath = storage_path('app/private/certificates/certificate-template.png');

        if (! file_exists($templatePath)) {
            abort(404, 'Template sertifikat tidak ditemukan.');
        }

        $manager = new ImageManager(new Driver);
        $image = $manager->read($templatePath);

        // Get real data from certificate
        $metadata = is_array($certificate->metadata) ? $certificate->metadata : json_decode($certificate->metadata, true);
        $userName = $metadata['user_name'] ?? 'Unknown User';
        $dateOfBirth = $certificate->date_of_birth instanceof Carbon ? $certificate->date_of_birth : Carbon::parse($certificate->date_of_birth);

        // Add certificate number
        $image->text($certificate->certificate_number, 1805, 580, function ($font) {
            $font->filename(public_path('fonts/Poppins-Bold.ttf'));
            $font->size(38);
            $font->color('#2B516B');
            $font->align('center');
            $font->valign('top');
        });

        // Add user name
        $image->text($userName, 1390, 965, function ($font) {
            $font->filename(public_path('fonts/Poppins-Semibold.ttf'));
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Add date of birth
        $image->text($dateOfBirth->format('d F Y'), 1390, 1045, function ($font) {
            $font->filename(public_path('fonts/Poppins-Semibold.ttf'));
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Add subtest scores berdasarkan data yang ada di metadata
        // Listening Score (posisi 1)
        $listeningScore = $metadata['listening_score'] ?? '-';
        $image->text($listeningScore, 1985, 1365, function ($font) {
            $font->filename(public_path('fonts/Poppins-Semibold.ttf'));
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Reading Score (posisi 2)
        $readingScore = $metadata['reading_score'] ?? '-';
        $image->text($readingScore, 1985, 1465, function ($font) {
            $font->filename(public_path('fonts/Poppins-Semibold.ttf'));
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Writing Score (posisi 3)
        $writingScore = $metadata['writing_score'] ?? '-';
        $image->text($writingScore, 1985, 1565, function ($font) {
            $font->filename(public_path('fonts/Poppins-Semibold.ttf'));
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Add overall score
        $overallScore = $metadata['score'] ?? '-';
        $image->text($overallScore, 1985, 1683, function ($font) {
            $font->filename(public_path('fonts/Poppins-Bold.ttf'));
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Generate filename untuk download
        $certificateName = 'Certificate_'.str_replace(['/', '-'], '_', $certificate->certificate_number).'.png';

        return response($image->toPng()->toString(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$certificateName.'"',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
