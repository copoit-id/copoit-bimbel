<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\ClientProfile;
use App\Services\CertificateTemplateRenderer;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateTemplateController extends Controller
{
    private const FIELD_DEFINITIONS = [
        'participant_name' => 'Nama Peserta',
        'issued_date' => 'Tanggal Terbit',
        'subtest_score_1' => 'Nilai Subtest 1',
        'subtest_score_2' => 'Nilai Subtest 2',
        'subtest_score_3' => 'Nilai Subtest 3',
        'total_score' => 'Skor Total',
    ];

    private const ADDABLE_FIELD_DEFINITIONS = [
        'participant_name' => 'Nama Peserta',
        'participant_email' => 'Email Peserta',
        'date_of_birth' => 'Tanggal Lahir Peserta',
        'certificate_number' => 'Nomor Sertifikat',
        'tryout_name' => 'Nama Tryout',
        'package_name' => 'Nama Paket',
        'institution_name' => 'Nama Bimbel / Lembaga',
        'issued_date' => 'Tanggal Terbit Sertifikat',
        'completion_date' => 'Tanggal Selesai Ujian',
        'exam_date' => 'Tanggal Mulai Ujian',
        'total_score' => 'Nilai Total',
        'subtest_score' => 'Nilai Subtest Tertentu',
        'subtest_scores' => 'Daftar Semua Nilai Subtest',
        'qr_code' => 'QR Validasi',
        'custom_text' => 'Teks Bebas',
    ];

    public function index(): View
    {
        $templates = CertificateTemplate::query()
            ->where('client_profile_id', $this->clientProfileId())
            ->latest()
            ->paginate(12);

        return view('admin.pages.certificate-template.index', compact('templates'));
    }

    public function create(): View
    {
        $certificateTemplate = new CertificateTemplate([
            'layout' => $this->defaultLayout(),
            'is_active' => true,
        ]);

        return view('admin.pages.certificate-template.form', [
            'certificateTemplate' => $certificateTemplate,
            'fieldDefinitions' => self::FIELD_DEFINITIONS,
            'addableFieldDefinitions' => self::ADDABLE_FIELD_DEFINITIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTemplate($request, true);
        $backgroundPath = $request->file('background')->store('certificates/templates', 'local');

        CertificateTemplate::create([
            'client_profile_id' => $this->clientProfileId(),
            'name' => $validated['name'],
            'background_path' => $backgroundPath,
            'layout' => $this->normalizedLayout($request),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.certificate.template.index')
            ->with('success', 'Template sertifikat berhasil dibuat.');
    }

    public function edit(CertificateTemplate $certificateTemplate): View
    {
        $certificateTemplate = $this->templateForCurrentClient($certificateTemplate);

        return view('admin.pages.certificate-template.form', [
            'certificateTemplate' => $certificateTemplate,
            'fieldDefinitions' => self::FIELD_DEFINITIONS,
            'addableFieldDefinitions' => self::ADDABLE_FIELD_DEFINITIONS,
        ]);
    }

    public function update(Request $request, CertificateTemplate $certificateTemplate): RedirectResponse
    {
        $certificateTemplate = $this->templateForCurrentClient($certificateTemplate);
        $validated = $this->validateTemplate($request);

        $data = [
            'name' => $validated['name'],
            'layout' => $this->normalizedLayout($request),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('background')) {
            $data['background_path'] = $request->file('background')->store('certificates/templates', 'local');
        }

        $certificateTemplate->update($data);

        return redirect()
            ->route('admin.certificate.template.index')
            ->with('success', 'Template sertifikat berhasil diperbarui. Sertifikat lama tetap memakai snapshot template sebelumnya.');
    }

    public function destroy(CertificateTemplate $certificateTemplate): RedirectResponse
    {
        $certificateTemplate = $this->templateForCurrentClient($certificateTemplate);

        if ($certificateTemplate->tryouts()->exists()) {
            return back()->with('error', 'Template tidak dapat dihapus karena masih dipakai oleh tryout.');
        }

        $certificateTemplate->delete();

        return redirect()
            ->route('admin.certificate.template.index')
            ->with('success', 'Template sertifikat berhasil dihapus.');
    }

    public function background(CertificateTemplate $certificateTemplate): StreamedResponse
    {
        $certificateTemplate = $this->templateForCurrentClient($certificateTemplate);
        abort_unless(Storage::disk('local')->exists($certificateTemplate->background_path), 404);

        return Storage::disk('local')->response($certificateTemplate->background_path);
    }

    public function preview(CertificateTemplate $certificateTemplate): Response
    {
        $certificateTemplate = $this->templateForCurrentClient($certificateTemplate);
        abort_unless(Storage::disk('local')->exists($certificateTemplate->background_path), 404);

        $certificate = new Certificate([
            'certificate_number' => 'PREVIEW/001/2026',
            'certificate_name' => 'Tryout Contoh',
            'date_of_birth' => Carbon::create(2000, 1, 15),
            'institution_name' => ClientProfile::query()->value('nama_bimbel') ?: 'Nama Bimbel Anda',
            'issued_date' => now(),
            'template_path' => $certificateTemplate->background_path,
            'metadata' => [
                'user_name' => 'Nama Peserta',
                'user_email' => 'peserta@example.com',
                'package_name' => 'Paket Contoh',
                'exam_date' => now()->subDay(),
                'completion_date' => now()->subDay(),
                'score' => 85.5,
                'subtest_scores' => [
                    ['label' => 'Subtest 1', 'score' => '86'],
                    ['label' => 'Subtest 2', 'score' => '82'],
                    ['label' => 'Subtest 3', 'score' => '89'],
                ],
                'template_layout' => $certificateTemplate->layout,
            ],
        ]);

        return response(app(CertificateTemplateRenderer::class)->render($certificate), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="preview-sertifikat.png"',
        ]);
    }

    private function validateTemplate(Request $request, bool $backgroundRequired = false): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'background' => [$backgroundRequired ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'is_active' => ['nullable', 'boolean'],
            'layout' => ['required', 'array'],
            'layout.*.x' => ['nullable', 'numeric', 'min:0', 'max:20000'],
            'layout.*.y' => ['nullable', 'numeric', 'min:0', 'max:20000'],
            'layout.*.font_size' => ['nullable', 'numeric', 'min:8', 'max:300'],
            'layout.*.color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'layout.*.align' => ['nullable', 'in:left,center,right'],
            'layout.*.text' => ['nullable', 'string', 'max:500'],
            'layout.*.subtest_index' => ['nullable', 'integer', 'min:1', 'max:100'],
            'layout.*.field_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(self::ADDABLE_FIELD_DEFINITIONS))],
        ]);
    }

    private function normalizedLayout(Request $request): array
    {
        $submittedLayout = $request->input('layout', []);
        $defaults = $this->defaultLayout();

        foreach ($defaults as $key => $default) {
            $submitted = $submittedLayout[$key] ?? [];
            $defaults[$key] = [
                'enabled' => $request->boolean("layout.{$key}.enabled"),
                'x' => round((float) ($submitted['x'] ?? $default['x']), 2),
                'y' => round((float) ($submitted['y'] ?? $default['y']), 2),
                'font_size' => round((float) ($submitted['font_size'] ?? $default['font_size']), 2),
                'color' => $submitted['color'] ?? $default['color'],
                'align' => $submitted['align'] ?? $default['align'],
                'text' => $key === 'custom_text' ? trim((string) ($submitted['text'] ?? '')) : null,
                'subtest_index' => str_starts_with($key, 'subtest_score_')
                    ? max(1, (int) ($submitted['subtest_index'] ?? $default['subtest_index'] ?? 1))
                    : null,
                'field_type' => null,
            ];
        }

        foreach ($submittedLayout as $key => $submitted) {
            if (
                ! str_starts_with((string) $key, 'custom_text_')
                && ! str_starts_with((string) $key, 'subtest_score_')
                && ! str_starts_with((string) $key, 'optional_')
                || ! is_array($submitted)
            ) {
                continue;
            }

            $defaults[$key] = [
                'enabled' => $request->boolean("layout.{$key}.enabled"),
                'x' => round((float) ($submitted['x'] ?? 527), 2),
                'y' => round((float) ($submitted['y'] ?? 1020), 2),
                'font_size' => round((float) ($submitted['font_size'] ?? 16), 2),
                'color' => $submitted['color'] ?? '#1C3259',
                'align' => $submitted['align'] ?? 'center',
                'text' => trim((string) ($submitted['text'] ?? '')),
                'subtest_index' => str_starts_with((string) $key, 'subtest_score_')
                    ? max(1, (int) ($submitted['subtest_index'] ?? 1))
                    : null,
                'field_type' => str_starts_with((string) $key, 'optional_')
                    ? ($submitted['field_type'] ?? 'custom_text')
                    : null,
            ];
        }

        return $defaults;
    }

    private function defaultLayout(): array
    {
        return [
            'participant_name' => ['enabled' => true, 'x' => 527, 'y' => 620, 'font_size' => 34, 'color' => '#1C3259', 'align' => 'center'],
            'issued_date' => ['enabled' => true, 'x' => 527, 'y' => 1360, 'font_size' => 15, 'color' => '#1C3259', 'align' => 'center'],
            'subtest_score_1' => ['enabled' => true, 'x' => 527, 'y' => 900, 'font_size' => 18, 'color' => '#1C3259', 'align' => 'center', 'subtest_index' => 1],
            'subtest_score_2' => ['enabled' => true, 'x' => 527, 'y' => 940, 'font_size' => 18, 'color' => '#1C3259', 'align' => 'center', 'subtest_index' => 2],
            'subtest_score_3' => ['enabled' => true, 'x' => 527, 'y' => 980, 'font_size' => 18, 'color' => '#1C3259', 'align' => 'center', 'subtest_index' => 3],
            'total_score' => ['enabled' => true, 'x' => 527, 'y' => 1030, 'font_size' => 22, 'color' => '#1C3259', 'align' => 'center'],
        ];
    }

    private function clientProfileId(): ?int
    {
        return ClientProfile::query()->value('id');
    }

    private function templateForCurrentClient(CertificateTemplate $certificateTemplate): CertificateTemplate
    {
        abort_unless($certificateTemplate->client_profile_id === $this->clientProfileId(), 404);

        return $certificateTemplate;
    }
}
