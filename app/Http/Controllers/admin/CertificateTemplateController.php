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
        'conditional_text' => 'Teks Berdasarkan Nilai Subtest',
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

        $layout = $certificateTemplate->layout ?? [];
        $highestSubtestIndex = collect($layout)
            ->filter(function (mixed $config, string $field): bool {
                if (! is_array($config)) {
                    return false;
                }

                return str_starts_with($field, 'subtest_score_')
                    || in_array($config['field_type'] ?? null, ['subtest_score', 'conditional_text'], true);
            })
            ->map(function (array $config, string $field): int {
                $defaultIndex = str_starts_with($field, 'subtest_score_')
                    ? (int) substr($field, strrpos($field, '_') + 1)
                    : 1;

                return max(1, (int) ($config['subtest_index'] ?? $defaultIndex));
            })
            ->max() ?? 0;
        $previewSubtestScores = collect(range(1, max(3, $highestSubtestIndex)))
            ->map(fn (int $index): array => [
                'label' => "Subtest {$index}",
                'score' => (string) ([86, 82, 89, 84, 88][($index - 1) % 5]),
            ])
            ->all();
        $previewDate = Carbon::create(2026, 8, 15);

        $certificate = new Certificate([
            'certificate_number' => 'PREVIEW/001/2026',
            'certificate_name' => 'Tryout Contoh',
            'date_of_birth' => Carbon::create(2000, 1, 15),
            'institution_name' => 'Bimbel Contoh',
            'issued_date' => $previewDate,
            'template_path' => $certificateTemplate->background_path,
            'metadata' => [
                'user_name' => 'Kuntum Sari',
                'user_email' => 'kuntum.sari@example.com',
                'package_name' => 'Paket Contoh',
                'exam_date' => $previewDate->copy()->subDay(),
                'completion_date' => $previewDate->copy()->subDay(),
                'score' => 85.5,
                'subtest_scores' => $previewSubtestScores,
                'template_layout' => $layout,
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
            'layout.*.font_style' => ['nullable', 'in:regular,semibold,bold,italic,bold_italic'],
            'layout.*.color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'layout.*.align' => ['nullable', 'in:left,center,right'],
            'layout.*.text' => ['nullable', 'string', 'max:500'],
            'layout.*.fallback_text' => ['nullable', 'string', 'max:500'],
            'layout.*.subtest_index' => ['nullable', 'integer', 'min:1', 'max:100'],
            'layout.*.rules' => ['nullable', 'array', 'max:20'],
            'layout.*.rules.*.operator' => ['nullable', 'in:equals,gte,lte'],
            'layout.*.rules.*.value' => ['nullable', 'string', 'max:100'],
            'layout.*.rules.*.text' => ['nullable', 'string', 'max:500'],
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
                'font_style' => $this->normalizedFontStyle($submitted['font_style'] ?? null, $default['font_style'] ?? 'regular'),
                'color' => $submitted['color'] ?? $default['color'],
                'align' => $submitted['align'] ?? $default['align'],
                'text' => $key === 'custom_text' ? trim((string) ($submitted['text'] ?? '')) : null,
                'subtest_index' => str_starts_with($key, 'subtest_score_')
                    ? max(1, (int) ($submitted['subtest_index'] ?? $default['subtest_index'] ?? 1))
                    : null,
                'field_type' => null,
                'rules' => [],
                'fallback_text' => null,
            ];
        }

        foreach ($submittedLayout as $key => $submitted) {
            $isAdditionalField = str_starts_with((string) $key, 'custom_text_')
                || str_starts_with((string) $key, 'subtest_score_')
                || str_starts_with((string) $key, 'optional_');

            // Field bawaan sudah diproses di loop pertama. Memprosesnya lagi
            // dapat membuat nilai dari input duplikat menimpa posisi terbaru.
            if (array_key_exists($key, $defaults) || ! $isAdditionalField || ! is_array($submitted)) {
                continue;
            }

            $fieldType = str_starts_with((string) $key, 'optional_')
                ? ($submitted['field_type'] ?? 'custom_text')
                : null;

            $defaults[$key] = [
                'enabled' => $request->boolean("layout.{$key}.enabled"),
                'x' => round((float) ($submitted['x'] ?? 527), 2),
                'y' => round((float) ($submitted['y'] ?? 1020), 2),
                'font_size' => round((float) ($submitted['font_size'] ?? 16), 2),
                'font_style' => $this->normalizedFontStyle($submitted['font_style'] ?? null),
                'color' => $submitted['color'] ?? '#1C3259',
                'align' => $submitted['align'] ?? 'center',
                'text' => trim((string) ($submitted['text'] ?? '')),
                'subtest_index' => str_starts_with((string) $key, 'subtest_score_') || $fieldType === 'conditional_text'
                    ? max(1, (int) ($submitted['subtest_index'] ?? 1))
                    : null,
                'field_type' => $fieldType,
                'rules' => $fieldType === 'conditional_text'
                    ? $this->normalizedConditionalRules($submitted['rules'] ?? [])
                    : [],
                'fallback_text' => $fieldType === 'conditional_text'
                    ? trim((string) ($submitted['fallback_text'] ?? ''))
                    : null,
            ];
        }

        return $defaults;
    }

    private function defaultLayout(): array
    {
        return [
            'participant_name' => ['enabled' => true, 'x' => 527, 'y' => 620, 'font_size' => 34, 'font_style' => 'semibold', 'color' => '#1C3259', 'align' => 'center'],
            'issued_date' => ['enabled' => true, 'x' => 527, 'y' => 1360, 'font_size' => 15, 'font_style' => 'regular', 'color' => '#1C3259', 'align' => 'center'],
            'subtest_score_1' => ['enabled' => true, 'x' => 527, 'y' => 900, 'font_size' => 18, 'font_style' => 'regular', 'color' => '#1C3259', 'align' => 'center', 'subtest_index' => 1],
            'subtest_score_2' => ['enabled' => true, 'x' => 527, 'y' => 940, 'font_size' => 18, 'font_style' => 'regular', 'color' => '#1C3259', 'align' => 'center', 'subtest_index' => 2],
            'subtest_score_3' => ['enabled' => true, 'x' => 527, 'y' => 980, 'font_size' => 18, 'font_style' => 'regular', 'color' => '#1C3259', 'align' => 'center', 'subtest_index' => 3],
            'total_score' => ['enabled' => true, 'x' => 527, 'y' => 1030, 'font_size' => 22, 'font_style' => 'regular', 'color' => '#1C3259', 'align' => 'center'],
        ];
    }

    private function normalizedFontStyle(mixed $style, string $default = 'regular'): string
    {
        $style = (string) $style;

        return in_array($style, ['regular', 'semibold', 'bold', 'italic', 'bold_italic'], true)
            ? $style
            : $default;
    }

    private function normalizedConditionalRules(mixed $rules): array
    {
        if (! is_array($rules)) {
            return [];
        }

        return collect($rules)
            ->filter(fn ($rule): bool => is_array($rule) && filled($rule['value'] ?? null) && filled($rule['text'] ?? null))
            ->take(20)
            ->map(fn (array $rule): array => [
                'operator' => in_array($rule['operator'] ?? null, ['equals', 'gte', 'lte'], true) ? $rule['operator'] : 'equals',
                'value' => trim((string) $rule['value']),
                'text' => trim((string) $rule['text']),
            ])
            ->values()
            ->all();
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
