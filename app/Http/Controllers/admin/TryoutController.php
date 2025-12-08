<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Services\UtbkResultReleaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TryoutController extends Controller
{
    private const UTBK_SUBTESTS = [
        'penalaran_umum' => [
            'label' => 'Penalaran Umum',
            'default_duration' => 35,
            'default_passing' => 65,
        ],
        'pengetahuan_umum' => [
            'label' => 'Pengetahuan & Pemahaman Umum',
            'default_duration' => 30,
            'default_passing' => 65,
        ],
        'pengetahuan_kuantitatif' => [
            'label' => 'Pengetahuan Kuantitatif',
            'default_duration' => 35,
            'default_passing' => 65,
        ],
        'pemahaman_bacaan_menulis' => [
            'label' => 'Pemahaman Bacaan & Menulis',
            'default_duration' => 45,
            'default_passing' => 65,
        ],
        'literasi_bahasa_indonesia' => [
            'label' => 'Literasi Bahasa Indonesia',
            'default_duration' => 30,
            'default_passing' => 65,
        ],
        'literasi_bahasa_inggris' => [
            'label' => 'Literasi Bahasa Inggris',
            'default_duration' => 30,
            'default_passing' => 65,
        ],
        'penalaran_matematika' => [
            'label' => 'Penalaran Matematika',
            'default_duration' => 30,
            'default_passing' => 65,
        ],
    ];

    public function index()
    {
        $tryouts = Tryout::with(['tryoutDetails.questions'])
            ->withCount([
                'userAnswers as utbk_pending_count' => function ($query) {
                    $query->where('status', 'pending_release');
                },
                'userAnswers as utbk_released_count' => function ($query) {
                    $query->where('status', 'completed')
                        ->whereNotNull('utbk_total_score');
                },
            ])
            ->latest()
            ->paginate(10);

        $tryouts->getCollection()->each(function ($tryout) {
            $tryout->tryoutDetails->each(function ($detail) {
                $detail->setAttribute('subtest_name', $this->subtestLabel($detail->type_subtest));
            });
        });

        $packages = Package::all();

        return view('admin.pages.tryout.index', compact('tryouts', 'packages'));
    }

    private const UTBK_SINGLE_TYPES = [
        'utbk_penalaran_umum' => 'penalaran_umum',
        'utbk_pengetahuan_umum' => 'pengetahuan_umum',
        'utbk_pengetahuan_kuantitatif' => 'pengetahuan_kuantitatif',
        'utbk_pemahaman_bacaan_menulis' => 'pemahaman_bacaan_menulis',
        'utbk_literasi_bahasa_indonesia' => 'literasi_bahasa_indonesia',
        'utbk_literasi_bahasa_inggris' => 'literasi_bahasa_inggris',
        'utbk_penalaran_matematika' => 'penalaran_matematika',
    ];

    public function create()
    {
        $packages = Package::all();
        $utbkSubtests = $this->getUtbkSubtests();
        $utbkSingleTypes = $this->getUtbkSingleTypeOptions();

        return view('admin.pages.tryout.create', compact('packages', 'utbkSubtests', 'utbkSingleTypes'));
    }

    public function store(Request $request)
    {
        $request->validate($this->tryoutValidationRules());
        $isIrtEnabled = $this->shouldEnableIrt($request);

        try {
            $tryout = Tryout::create([
                'name' => $request->name,
                'description' => $request->description,
                'type_tryout' => $request->type_tryout,
                'assessment_type' => $request->assessment_type,
                'is_certification' => $request->has('is_certification'),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->has('is_active'),
                'is_toefl' => $request->has('is_toefl'),
                'is_irt' => $isIrtEnabled,
                'results_release_at' => $isIrtEnabled ? ($request->end_date ?? null) : null,
                'results_released_at' => null,
            ]);

            $this->createTryoutDetails($tryout, $request);

            return redirect()->route('admin.tryout.index')
                ->with('success', 'Tryout "' . $tryout->name . '" berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan tryout: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        try {
            $tryout = Tryout::with(['tryoutDetails'])->findOrFail($id);
            $utbkSubtests = $this->getUtbkSubtests();
            $utbkSingleTypes = $this->getUtbkSingleTypeOptions();

            return view('admin.pages.tryout.create', compact('tryout', 'utbkSubtests', 'utbkSingleTypes'));
        } catch (\Exception $e) {
            return redirect()->route('admin.tryout.index')
                ->with('error', 'Tryout tidak ditemukan');
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->tryoutValidationRules());
        $isIrtEnabled = $this->shouldEnableIrt($request);

        try {
            $tryout = Tryout::with('tryoutDetails')->findOrFail($id);

            $originalType = $tryout->type_tryout;

            // Update master tryout fields
            $tryout->update([
                'name' => $request->name,
                'description' => $request->description,
                'type_tryout' => $request->type_tryout,
                'assessment_type' => $request->assessment_type,
                'is_certification' => $request->has('is_certification'),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->has('is_active'),
                'is_toefl' => $request->has('is_toefl'),
                'is_irt' => $isIrtEnabled,
                'results_release_at' => $isIrtEnabled ? ($request->end_date ?? $tryout->end_date) : null,
                'results_released_at' => $isIrtEnabled ? $tryout->results_released_at : null,
            ]);

            // If type changed, rebuild subtests based on new type; else update existing ones
            if ($originalType !== $request->type_tryout) {
                // Remove all existing details to avoid stale subtests
                $tryout->tryoutDetails()->delete();
                $this->createTryoutDetails($tryout, $request);
            } else {
                $this->updateTryoutDetails($tryout, $request);
            }

            return redirect()->route('admin.tryout.index')
                ->with('success', 'Tryout berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui tryout: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $tryout = Tryout::findOrFail($id);
            $tryout->delete();
            return redirect()->route('admin.tryout.index')
                ->with('success', 'Tryout berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus tryout: ' . $e->getMessage());
        }
    }

    public function preview($id)
    {
        try {
            $tryout = Tryout::with([
                'tryoutDetails' => function ($query) {
                    $query->orderBy('tryout_detail_id');
                },
                'tryoutDetails.questions' => function ($query) {
                    $query->with('questionOptions')
                        ->orderBy('question_id');
                }
            ])->findOrFail($id);

            // Tambahkan properti 'subtest_name' ke setiap detail
            $tryout->tryoutDetails->each(function ($detail) {
                $detail->setAttribute('subtest_name', $this->subtestLabel($detail->type_subtest));
            });

            return view('admin.pages.tryout.preview', compact('tryout'));
        } catch (\Throwable $e) {
            // optional: report($e);
            return redirect()
                ->route('admin.tryout.index')
                ->with('error', 'Tryout tidak ditemukan');
        }
    }

    private function createTryoutDetails($tryout, $request)
    {
        switch ($tryout->type_tryout) {
            case 'utbk_full':
                $this->syncUtbkFullSubtests($tryout, $request);
                break;
            case 'skd_full':
                $this->createSubtest($tryout->tryout_id, 'twk', $request->duration_twk ?? 35, $request->passing_score_twk ?? 65);
                $this->createSubtest($tryout->tryout_id, 'tiu', $request->duration_tiu ?? 90, $request->passing_score_tiu ?? 80);
                $this->createSubtest($tryout->tryout_id, 'tkp', $request->duration_tkp ?? 45, $request->passing_score_tkp ?? 166);
                break;

            case 'certification':
                $this->createSubtest($tryout->tryout_id, 'listening', $request->duration_listening ?? 60, $request->passing_score_listening ?? 60);
                $this->createSubtest($tryout->tryout_id, 'writing', $request->duration_writing ?? 60, $request->passing_score_writing ?? 60);
                $this->createSubtest($tryout->tryout_id, 'reading', $request->duration_reading ?? 60, $request->passing_score_reading ?? 60);
                break;

            case 'pppk_full':
                $this->createSubtest($tryout->tryout_id, 'teknis', $request->duration_teknis ?? 90, $request->passing_score_teknis ?? 65);
                $this->createSubtest($tryout->tryout_id, 'social culture', $request->duration_social_culture ?? 60, $request->passing_score_social_culture ?? 65);
                $this->createSubtest($tryout->tryout_id, 'interview', $request->duration_interview ?? 30, $request->passing_score_interview ?? 70);
                break;

            case 'twk':
                $this->createSubtest($tryout->tryout_id, 'twk', $request->duration_twk ?? 35, $request->passing_score_twk ?? 65);
                break;

            case 'tiu':
                $this->createSubtest($tryout->tryout_id, 'tiu', $request->duration_tiu ?? 90, $request->passing_score_tiu ?? 80);
                break;

            case 'tkp':
                $this->createSubtest($tryout->tryout_id, 'tkp', $request->duration_tkp ?? 45, $request->passing_score_tkp ?? 166);
                break;

            case 'general':
                $this->createSubtest($tryout->tryout_id, 'general', $request->duration_general ?? 60, $request->passing_score_general ?? 60);
                break;

            case 'listening':
                $this->createSubtest($tryout->tryout_id, 'listening', $request->duration_listening ?? 45, $request->passing_score_listening ?? 60);
                break;

            case 'reading':
                $this->createSubtest($tryout->tryout_id, 'reading', $request->duration_reading ?? 60, $request->passing_score_reading ?? 60);
                break;

            case 'writing':
                $this->createSubtest($tryout->tryout_id, 'writing', $request->duration_writing ?? 60, $request->passing_score_writing ?? 60);
                break;

            case 'teknis':
                $this->createSubtest($tryout->tryout_id, 'teknis', $request->duration_teknis ?? 90, $request->passing_score_teknis ?? 65);
                break;

            case 'social culture':
                $this->createSubtest($tryout->tryout_id, 'social culture', $request->duration_social_culture ?? 60, $request->passing_score_social_culture ?? 65);
                break;

            case 'interview':
                $this->createSubtest($tryout->tryout_id, 'interview', $request->duration_interview ?? 30, $request->passing_score_interview ?? 70);
                break;
            case 'word':
                $this->createSubtest($tryout->tryout_id, 'word', $request->duration_word ?? 30, $request->passing_score_word ?? 70);
                break;
            case 'excel':
                $this->createSubtest($tryout->tryout_id, 'excel', $request->duration_excel ?? 30, $request->passing_score_excel ?? 70);
                break;
            case 'ppt':
                $this->createSubtest($tryout->tryout_id, 'ppt', $request->duration_ppt ?? 30, $request->passing_score_ppt ?? 70);
                break;

            case 'computer':
                $this->createSubtest($tryout->tryout_id, 'word', $request->duration_word ?? 30, $request->passing_score_word ?? 70);
                $this->createSubtest($tryout->tryout_id, 'excel', $request->duration_excel ?? 30, $request->passing_score_excel ?? 70);
                $this->createSubtest($tryout->tryout_id, 'ppt', $request->duration_ppt ?? 30, $request->passing_score_ppt ?? 70);
                break;

            case 'word':
                $this->createSubtest($tryout->tryout_id, 'word', $request->duration_word ?? 30, $request->passing_score_word ?? 70);
                break;

            case 'excel':
                $this->createSubtest($tryout->tryout_id, 'excel', $request->duration_excel ?? 30, $request->passing_score_excel ?? 70);
                break;

            case 'ppt':
                $this->createSubtest($tryout->tryout_id, 'ppt', $request->duration_ppt ?? 30, $request->passing_score_ppt ?? 70);
                break;
            default:
                $this->createUtbkSingleSubtest($tryout, $request);
                break;
        }
    }

    private function createSubtest($tryoutId, $type, $duration, $passingScore)
    {
        TryoutDetail::create([
            'tryout_id' => $tryoutId,
            'type_subtest' => $type,
            'duration' => $duration,
            'passing_score' => $passingScore,
        ]);
    }

    private function updateOrCreateSubtest(Tryout $tryout, string $type, $duration, $passingScore): void
    {
        $detail = $tryout->tryoutDetails()->where('type_subtest', $type)->first();
        if ($detail) {
            $detail->update([
                'duration' => $duration,
                'passing_score' => $passingScore,
            ]);
        } else {
            $this->createSubtest($tryout->tryout_id, $type, $duration, $passingScore);
        }
    }

    private function updateTryoutDetails(Tryout $tryout, Request $request): void
    {
        switch ($tryout->type_tryout) {
            case 'utbk_full':
                $this->syncUtbkFullSubtests($tryout, $request);
                break;
            case 'skd_full':
                $this->updateOrCreateSubtest($tryout, 'twk', $request->duration_twk ?? 35, $request->passing_score_twk ?? 65);
                $this->updateOrCreateSubtest($tryout, 'tiu', $request->duration_tiu ?? 90, $request->passing_score_tiu ?? 80);
                $this->updateOrCreateSubtest($tryout, 'tkp', $request->duration_tkp ?? 45, $request->passing_score_tkp ?? 166);
                break;
            case 'certification':
                $this->updateOrCreateSubtest($tryout, 'listening', $request->duration_listening ?? 60, $request->passing_score_listening ?? 60);
                $this->updateOrCreateSubtest($tryout, 'writing', $request->duration_writing ?? 60, $request->passing_score_writing ?? 60);
                $this->updateOrCreateSubtest($tryout, 'reading', $request->duration_reading ?? 60, $request->passing_score_reading ?? 60);
                break;
            case 'pppk_full':
                $this->updateOrCreateSubtest($tryout, 'teknis', $request->duration_teknis ?? 90, $request->passing_score_teknis ?? 65);
                $this->updateOrCreateSubtest($tryout, 'social culture', $request->duration_social_culture ?? 60, $request->passing_score_social_culture ?? 65);
                $this->updateOrCreateSubtest($tryout, 'interview', $request->duration_interview ?? 30, $request->passing_score_interview ?? 70);
                break;
            case 'computer':
                $this->updateOrCreateSubtest($tryout, 'word', $request->duration_word ?? 30, $request->passing_score_word ?? 70);
                $this->updateOrCreateSubtest($tryout, 'excel', $request->duration_excel ?? 30, $request->passing_score_excel ?? 70);
                $this->updateOrCreateSubtest($tryout, 'ppt', $request->duration_ppt ?? 30, $request->passing_score_ppt ?? 70);
                break;
            case 'twk':
                $this->updateOrCreateSubtest($tryout, 'twk', $request->duration_general ?? 35, $request->passing_score_general ?? 65);
                break;
            case 'tiu':
                $this->updateOrCreateSubtest($tryout, 'tiu', $request->duration_general ?? 90, $request->passing_score_general ?? 80);
                break;
            case 'tkp':
                $this->updateOrCreateSubtest($tryout, 'tkp', $request->duration_general ?? 45, $request->passing_score_general ?? 166);
                break;
            case 'listening':
                $this->updateOrCreateSubtest($tryout, 'listening', $request->duration_general ?? 45, $request->passing_score_general ?? 60);
                break;
            case 'reading':
                $this->updateOrCreateSubtest($tryout, 'reading', $request->duration_general ?? 60, $request->passing_score_general ?? 60);
                break;
            case 'writing':
                $this->updateOrCreateSubtest($tryout, 'writing', $request->duration_general ?? 60, $request->passing_score_general ?? 60);
                break;
            case 'teknis':
                $this->updateOrCreateSubtest($tryout, 'teknis', $request->duration_general ?? 90, $request->passing_score_general ?? 65);
                break;
            case 'social culture':
                $this->updateOrCreateSubtest($tryout, 'social culture', $request->duration_general ?? 60, $request->passing_score_general ?? 65);
                break;
            case 'interview':
                $this->updateOrCreateSubtest($tryout, 'interview', $request->duration_general ?? 30, $request->passing_score_general ?? 70);
                break;
            case 'general':
                $this->updateOrCreateSubtest($tryout, 'general', $request->duration_general ?? 60, $request->passing_score_general ?? 60);
                break;
            case 'word':
                $this->updateOrCreateSubtest($tryout, 'word', $request->duration_word ?? $request->duration_general ?? 30, $request->passing_score_word ?? $request->passing_score_general ?? 70);
                break;
            case 'excel':
                $this->updateOrCreateSubtest($tryout, 'excel', $request->duration_excel ?? $request->duration_general ?? 30, $request->passing_score_excel ?? $request->passing_score_general ?? 70);
                break;
            case 'ppt':
                $this->updateOrCreateSubtest($tryout, 'ppt', $request->duration_ppt ?? $request->duration_general ?? 30, $request->passing_score_ppt ?? $request->passing_score_general ?? 70);
                break;
            default:
                $this->createUtbkSingleSubtest($tryout, $request, true);
                break;
        }
    }

    private function syncUtbkFullSubtests(Tryout $tryout, Request $request): void
    {
        $allowedTypes = array_keys(self::UTBK_SUBTESTS);

        foreach (self::UTBK_SUBTESTS as $type => $config) {
            $durationField = 'duration_' . $type;
            $passingField = 'passing_score_' . $type;

            $duration = $request->input($durationField, $config['default_duration']);
            $passing = $request->input($passingField, $config['default_passing']);

            $this->updateOrCreateSubtest($tryout, $type, $duration, $passing);
        }

        $tryout->tryoutDetails()->whereNotIn('type_subtest', $allowedTypes)->delete();
    }

    private function createUtbkSingleSubtest(Tryout $tryout, Request $request, bool $isUpdate = false): void
    {
        $slug = $this->getUtbkSlugForType($tryout->type_tryout);

        if (! $slug) {
            return;
        }

        $defaults = self::UTBK_SUBTESTS[$slug] ?? ['default_duration' => 60, 'default_passing' => 65];
        $durationField = 'duration_' . $slug;
        $passingField = 'passing_score_' . $slug;

        $duration = $request->input($durationField, $defaults['default_duration']);
        $passing = $request->input($passingField, $defaults['default_passing']);

        if ($isUpdate) {
            $this->updateOrCreateSubtest($tryout, $slug, $duration, $passing);
        } else {
            $this->createSubtest($tryout->tryout_id, $slug, $duration, $passing);
        }

        $tryout->tryoutDetails()->where('type_subtest', '!=', $slug)->delete();
    }

    private function subtestLabel(?string $type): string
    {
        // Normalisasi: lowercase dan rapikan spasi
        $key = (string) Str::of((string) $type)->lower()->replaceMatches('/\s+/', ' ');

        $map = [
            'twk'               => 'Tes Wawasan Kebangsaan',
            'tiu'               => 'Tes Intelegensi Umum',
            'tkp'               => 'Tes Karakteristik Pribadi',
            'writing'           => 'Writing Test',
            'reading'           => 'Reading Comprehension',
            'listening'         => 'Listening Test',
            'teknis'            => 'Tes Teknis',
            'social culture'    => 'Sosial-Kultural & Manajerial',
            'interview'         => 'Wawancara',
            'word'              => 'Microsoft Word',
            'excel'             => 'Microsoft Excel',
            'ppt'               => 'Microsoft PowerPoint',
            'penalaran_umum'    => 'Penalaran Umum',
            'pengetahuan_umum'  => 'Pengetahuan & Pemahaman Umum',
            'pengetahuan_kuantitatif'  => 'Pengetahuan Kuantitatif',
            'pemahaman_bacaan_menulis' => 'Pemahaman Bacaan & Menulis',
            'literasi_bahasa_indonesia' => 'Literasi Bahasa Indonesia',
            'literasi_bahasa_inggris' => 'Literasi Bahasa Inggris',
            'penalaran_matematika' => 'Penalaran Matematika',
        ];

        // Fallback: bikin judul yang oke kalau kodenya belum dipetakan
        return $map[$key] ?? Str::headline((string) $type);
    }

    private function shouldEnableIrt(Request $request): bool
    {
        return $request->type_tryout === 'utbk_full' && $request->boolean('is_irt');
    }

    private function tryoutValidationRules(): array
    {
        $typeOptions = array_merge([
            'tiu',
            'twk',
            'tkp',
            'skd_full',
            'general',
            'certification',
            'listening',
            'reading',
            'writing',
            'pppk_full',
            'teknis',
            'social culture',
            'management',
            'interview',
            'word',
            'excel',
            'ppt',
            'computer',
            'utbk_full',
            'utbk_section',
        ], array_keys(self::UTBK_SINGLE_TYPES));

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type_tryout' => ['required', Rule::in($typeOptions)],
            'assessment_type' => ['required', Rule::in(['standard', 'pre_test', 'post_test'])],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_certification' => 'boolean',
            'is_active' => 'boolean',
            'is_toefl' => 'boolean',
            'is_irt' => 'boolean',
        ];

        foreach (array_keys(self::UTBK_SUBTESTS) as $slug) {
            $rules['duration_' . $slug] = 'nullable|integer|min:1';
            $rules['passing_score_' . $slug] = 'nullable|numeric|min:0|max:100';
        }

        return $rules;
    }

    private function getUtbkSubtests(): array
    {
        return self::UTBK_SUBTESTS;
    }

    private function getUtbkSlugForType(?string $type): ?string
    {
        return self::UTBK_SINGLE_TYPES[$type] ?? null;
    }

    private function getUtbkSingleTypeOptions(): array
    {
        $options = [];
        foreach (self::UTBK_SINGLE_TYPES as $type => $slug) {
            $options[$type] = [
                'slug' => $slug,
                'label' => self::UTBK_SUBTESTS[$slug]['label'] ?? Str::headline(str_replace('utbk_', '', $type)),
            ];
        }

        return $options;
    }

    public function releaseUtbk(Tryout $tryout, UtbkResultReleaseService $service)
    {
        if (! $tryout->requiresIrtScoring()) {
            return redirect()->back()->with('error', 'Tryout ini tidak menggunakan IRT UTBK.');
        }

        $released = $service->releaseForTryout($tryout);

        return redirect()->back()->with(
            $released ? 'success' : 'info',
            $released ? 'Hasil UTBK berhasil dirilis.' : 'Tidak ada jawaban pending untuk dirilis.'
        );
    }

    public function resetUtbk(Tryout $tryout, UtbkResultReleaseService $service)
    {
        if (! $tryout->requiresIrtScoring()) {
            return redirect()->back()->with('error', 'Tryout ini tidak menggunakan IRT UTBK.');
        }

        $reset = $service->resetResults($tryout);

        return redirect()->back()->with(
            $reset ? 'success' : 'info',
            $reset ? 'Skor UTBK berhasil di-reset. Silakan rilis ulang.' : 'Tidak ada skor UTBK yang bisa di-reset.'
        );
    }
}
