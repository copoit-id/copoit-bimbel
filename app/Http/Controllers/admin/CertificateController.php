<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Tryout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = Certificate::with('issuedBy');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                    ->orWhere('certificate_name', 'like', "%{$search}%")
                    ->orWhere('issued_to_name', 'like', "%{$search}%")
                    ->orWhere('issued_to_email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('issued_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('issued_date', '<=', $request->date_to);
        }

        $certificates = $query->orderBy('issued_date', 'desc')->paginate(15);

        return view('admin.pages.certificate.index', compact('certificates'));
    }

    public function create()
    {
        return view('admin.pages.certificate.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'certificate_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'issued_to_name' => 'required|string|max:255',
            'issued_to_email' => 'required|email|max:255',
            'institution_name' => 'required|string|max:255',
            'issued_date' => 'required|date',
            'expired_date' => 'nullable|date|after:issued_date',
            'template' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'metadata' => 'nullable|array'
        ]);

        $data = $request->except(['template']);
        $data['issued_by'] = Auth::id();

        if ($request->hasFile('template')) {
            $templatePath = $request->file('template')->store('certificates/templates', 'private');
            $data['template_path'] = $templatePath;
        }

        if ($request->has('metadata')) {
            $data['metadata'] = $request->metadata;
        }

        Certificate::create($data);

        return redirect()->route('admin.certificate.index')
            ->with('success', 'Sertifikat berhasil dibuat');
    }

    public function show(Certificate $certificate)
    {
        $certificate->load('issuedBy');
        return view('admin.pages.certificate.show', compact('certificate'));
    }

    public function edit(Certificate $certificate)
    {
        return view('admin.pages.certificate.edit', compact('certificate'));
    }

    public function update(Request $request, Certificate $certificate)
    {
        $request->validate([
            'certificate_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'issued_to_name' => 'required|string|max:255',
            'issued_to_email' => 'required|email|max:255',
            'institution_name' => 'required|string|max:255',
            'issued_date' => 'required|date',
            'expired_date' => 'nullable|date|after:issued_date',
            'status' => 'required|in:active,revoked,expired',
            'template' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'metadata' => 'nullable|array'
        ]);

        $data = $request->except(['template']);

        if ($request->hasFile('template')) {
            if ($certificate->template_path) {
                Storage::disk('private')->delete($certificate->template_path);
            }

            $templatePath = $request->file('template')->store('certificates/templates', 'private');
            $data['template_path'] = $templatePath;
        }

        if ($request->has('metadata')) {
            $data['metadata'] = $request->metadata;
        }

        $certificate->update($data);

        return redirect()->route('admin.certificate.index')
            ->with('success', 'Sertifikat berhasil diperbarui');
    }

    public function destroy(Certificate $certificate)
    {
        if ($certificate->template_path) {
            Storage::disk('private')->delete($certificate->template_path);
        }

        $certificate->delete();

        return redirect()->route('admin.certificate.index')
            ->with('success', 'Sertifikat berhasil dihapus');
    }

    public function downloadTemplate(Certificate $certificate)
    {
        // Get tryout to determine certificate type
        $tryout = Tryout::find($certificate->tryout_id);

        if ($tryout && $tryout->type_tryout === 'computer') {
            return $this->downloadComputerTemplate($certificate);
        } else {
            return $this->downloadCertificationTemplate($certificate);
        }
    }

    private function downloadCertificationTemplate(Certificate $certificate)
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
                $font->filename(asset('/fonts/Poppins-Bold.ttf'));
            } else {
                $font->filename('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(38);
            $font->color('#2B516B');
            $font->align('center');
            $font->valign('top');
        });

        // Add user name
        $image->text($userName, 1390, 965, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->filename(asset('/fonts/Poppins-Semibold.ttf'));
            } else {
                $font->filename('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Add date of birth
        $image->text($dateOfBirth->format('d F Y'), 1390, 1045, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->filename(asset('/fonts/Poppins-Semibold.ttf'));
            } else {
                $font->filename('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Add subtest scores berdasarkan data yang ada di metadata
        // Listening Score (posisi 1)
        $listeningScore = $metadata['listening_score'] ?? '-';
        $image->text($listeningScore, 1985, 1365, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->filename(asset('/fonts/Poppins-Semibold.ttf'));
            } else {
                $font->filename('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Reading Score (posisi 2)
        $readingScore = $metadata['reading_score'] ?? '-';
        $image->text($readingScore, 1985, 1465, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->filename(asset('/fonts/Poppins-Semibold.ttf'));
            } else {
                $font->filename('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Writing Score (posisi 3)
        $writingScore = $metadata['writing_score'] ?? '-';
        $image->text($writingScore, 1985, 1565, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->filename(asset('/fonts/Poppins-Semibold.ttf'));
            } else {
                $font->filename('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Add overall score
        $overallScore = $metadata['score'] ?? '-';
        $image->text(round($overallScore, 0), 1985, 1683, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->filename(asset('/fonts/Poppins-Bold.ttf'));
            } else {
                $font->filename('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Generate filename untuk download
        $certificateName = 'Certificate_' . str_replace(['/', '-'], '_', $certificate->certificate_number) . '.png';

        return response($image->toPng()->toString(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $certificateName . '"',
            'Cache-Control' => 'no-cache, must-revalidate'
        ]);
    }

    private function downloadComputerTemplate(Certificate $certificate)
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
                $font->filename(asset('/fonts/Poppins-Bold.ttf'));
            } else {
                $font->filename('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(38);
            $font->color('#2B516B');
            $font->align('center');
            $font->valign('top');
        });

        // Add user name
        $image->text($userName, 1390, 965, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->filename(asset('/fonts/Poppins-Semibold.ttf'));
            } else {
                $font->filename('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Add date of birth
        $image->text($dateOfBirth->format('d F Y'), 1390, 1045, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->filename(asset('/fonts/Poppins-Semibold.ttf'));
            } else {
                $font->filename('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Add subtest scores berdasarkan data yang ada di metadata (Word, Excel, PPT)
        // Word Score (posisi 1)
        $wordScore = $metadata['listening_score'] ?? '-'; // Using listening_score as word_score
        $image->text($wordScore, 1985, 1365, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->filename(asset('/fonts/Poppins-Semibold.ttf'));
            } else {
                $font->filename('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Excel Score (posisi 2)
        $excelScore = $metadata['reading_score'] ?? '-'; // Using reading_score as excel_score
        $image->text($excelScore, 1985, 1465, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->filename(asset('/fonts/Poppins-Semibold.ttf'));
            } else {
                $font->filename('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // PowerPoint Score (posisi 3)
        $pptScore = $metadata['writing_score'] ?? '-'; // Using writing_score as ppt_score
        $image->text($pptScore, 1985, 1565, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->filename(asset('/fonts/Poppins-Semibold.ttf'));
            } else {
                $font->filename('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Add overall score
        $overallScore = $metadata['score'] ?? '-';
        $image->text(round($overallScore, 0), 1985, 1683, function ($font) {
            if (env('APP_ENV') == 'local') {
                $font->filename(asset('/fonts/Poppins-Bold.ttf'));
            } else {
                $font->filename('/home/cpns9957/public_html/bimbel.cpnsacademy.id/public/fonts/Poppins-SemiBold.ttf');
            }
            $font->size(50);
            $font->color('#2B516B');
            $font->align('start');
            $font->valign('top');
        });

        // Generate filename untuk download
        $certificateName = 'Certificate_Computer_' . str_replace(['/', '-'], '_', $certificate->certificate_number) . '.png';

        return response($image->toPng()->toString(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $certificateName . '"',
            'Cache-Control' => 'no-cache, must-revalidate'
        ]);
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,revoke,delete',
            'certificate_ids' => 'required|array',
            'certificate_ids.*' => 'exists:certificates,certificate_id'
        ]);

        $certificates = Certificate::whereIn('certificate_id', $request->certificate_ids);

        switch ($request->action) {
            case 'activate':
                $certificates->update(['status' => 'active']);
                $message = 'Sertifikat berhasil diaktifkan';
                break;
            case 'revoke':
                $certificates->update(['status' => 'revoked']);
                $message = 'Sertifikat berhasil dicabut';
                break;
            case 'delete':
                $certificatesToDelete = $certificates->get();
                foreach ($certificatesToDelete as $cert) {
                    if ($cert->template_path) {
                        Storage::disk('private')->delete($cert->template_path);
                    }
                }
                $certificates->delete();
                $message = 'Sertifikat berhasil dihapus';
                break;
        }

        return redirect()->route('admin.certificate.index')
            ->with('success', $message);
    }
}
