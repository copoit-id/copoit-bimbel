<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Question;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use App\Services\PlanQuotaService;
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
        $allowUtbkTypes = $this->allowUtbkControls();
        $utbkSubtests = $allowUtbkTypes ? $this->getUtbkSubtests() : [];
        $utbkSingleTypes = $allowUtbkTypes ? $this->getUtbkSingleTypeOptions() : [];
        $securityDefaults = PlanQuotaService::getDefaultProctoringSettings();

        return view('admin.pages.tryout.create', compact('packages', 'utbkSubtests', 'utbkSingleTypes', 'allowUtbkTypes', 'securityDefaults'));
    }

    public function store(Request $request)
    {
        $request->validate($this->tryoutValidationRules());
        $isIrtEnabled = $this->shouldEnableIrt($request);
        $securitySettings = PlanQuotaService::proctoringSettingsFromRequest($request);

        try {
            $tryout = Tryout::create([
                'name' => $request->name,
                'description' => $request->description,
                'type_tryout' => $request->type_tryout,
                'assessment_type' => $request->assessment_type,
                'section_break_duration' => max(0, (int) $request->input('section_break_duration', 0)),
                'answer_persistence_mode' => $request->input('answer_persistence_mode', 'client_side'),
                'subtest_display_mode' => $request->input('subtest_display_mode', 'per_subtest'),
                'enable_anti_copy' => $securitySettings['enable_anti_copy'],
                'enable_tab_switch_detection' => $securitySettings['enable_tab_switch_detection'],
                'enable_webcam_check' => $securitySettings['enable_webcam_check'],
                'enable_screen_check' => $securitySettings['enable_screen_check'],
                'is_certification' => $request->has('is_certification'),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->has('is_active'),
                'is_toefl' => $request->has('is_toefl'),
                'is_irt' => $isIrtEnabled,
                'is_for_sale' => $request->has('is_for_sale'),
                'is_displayed' => $request->has('is_displayed'),
                'results_release_at' => $isIrtEnabled ? ($request->end_date ?? null) : null,
                'results_released_at' => null,
                'price' => $request->price ?? 0,
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
            $allowUtbkTypes = $this->allowUtbkControls($tryout->type_tryout);
            $utbkSubtests = $allowUtbkTypes ? $this->getUtbkSubtests() : [];
            $utbkSingleTypes = $allowUtbkTypes ? $this->getUtbkSingleTypeOptions() : [];
            $securityDefaults = PlanQuotaService::getDefaultProctoringSettings();

            return view('admin.pages.tryout.create', compact('tryout', 'utbkSubtests', 'utbkSingleTypes', 'allowUtbkTypes', 'securityDefaults'));
        } catch (\Exception $e) {
            return redirect()->route('admin.tryout.index')
                ->with('error', 'Tryout tidak ditemukan');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $tryout = Tryout::with('tryoutDetails')->findOrFail($id);
        } catch (\Exception $e) {
            return redirect()->route('admin.tryout.index')
                ->with('error', 'Tryout tidak ditemukan');
        }

        $request->validate($this->tryoutValidationRules($tryout->type_tryout));
        $isIrtEnabled = $this->shouldEnableIrt($request);

        try {
            $originalType = $tryout->type_tryout;
            $originalPassing = $tryout->tryoutDetails->mapWithKeys(function ($detail) {
                return [
                    $detail->type_subtest => [
                        'score' => $detail->passing_score,
                        'type' => $detail->passing_type ?? 'score',
                    ],
                ];
            });
            $originalTypes = $tryout->tryoutDetails->pluck('type_subtest')->sort()->values();
            $securitySettings = PlanQuotaService::proctoringSettingsFromRequest($request);

            // Update master tryout fields
            $tryout->update([
                'name' => $request->name,
                'description' => $request->description,
                'type_tryout' => $request->type_tryout,
                'assessment_type' => $request->assessment_type,
                'section_break_duration' => max(0, (int) $request->input('section_break_duration', 0)),
                'answer_persistence_mode' => $request->input('answer_persistence_mode', 'client_side'),
                'subtest_display_mode' => $request->input('subtest_display_mode', 'per_subtest'),
                'enable_anti_copy' => $securitySettings['enable_anti_copy'],
                'enable_tab_switch_detection' => $securitySettings['enable_tab_switch_detection'],
                'enable_webcam_check' => $securitySettings['enable_webcam_check'],
                'enable_screen_check' => $securitySettings['enable_screen_check'],
                'is_certification' => $request->has('is_certification'),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->has('is_active'),
                'is_toefl' => $request->has('is_toefl'),
                'is_irt' => $isIrtEnabled,
                'is_for_sale' => $request->has('is_for_sale'),
                'is_displayed' => $request->has('is_displayed'),
                'results_release_at' => $isIrtEnabled ? ($request->end_date ?? $tryout->end_date) : null,
                'results_released_at' => $isIrtEnabled ? $tryout->results_released_at : null,
                'price' => $request->price ?? 0,
            ]);

            // If type changed, rebuild subtests based on new type; else update existing ones
            if ($originalType !== $request->type_tryout) {
                // Remove all existing details to avoid stale subtests
                $tryout->tryoutDetails()->delete();
                $this->createTryoutDetails($tryout, $request);
            } else {
                $this->updateTryoutDetails($tryout, $request);
            }

            $tryout->load('tryoutDetails');
            $newPassing = $tryout->tryoutDetails->mapWithKeys(function ($detail) {
                return [
                    $detail->type_subtest => [
                        'score' => $detail->passing_score,
                        'type' => $detail->passing_type ?? 'score',
                    ],
                ];
            });
            $newTypes = $tryout->tryoutDetails->pluck('type_subtest')->sort()->values();

            $shouldRecalculate = $originalType !== $request->type_tryout
                || $originalTypes->count() !== $newTypes->count()
                || $originalTypes->diff($newTypes)->isNotEmpty();

            if (! $shouldRecalculate) {
                foreach ($newPassing as $type => $values) {
                    $original = $originalPassing->get($type);
                    if (! $original || $original['score'] != $values['score'] || $original['type'] !== $values['type']) {
                        $shouldRecalculate = true;
                        break;
                    }
                }
            }

            if (
                $shouldRecalculate
                && ! $tryout->requiresIrtScoring()
                && ! $tryout->is_toefl
            ) {
                $this->recalculateTryoutPassedStatus($tryout);
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
                $this->createSubtest($tryout->tryout_id, 'twk', $request->duration_twk ?? 35, $request->passing_score_twk ?? 65, $request->input('passing_type_twk', 'score'));
                $this->createSubtest($tryout->tryout_id, 'tiu', $request->duration_tiu ?? 90, $request->passing_score_tiu ?? 80, $request->input('passing_type_tiu', 'score'));
                $this->createSubtest($tryout->tryout_id, 'tkp', $request->duration_tkp ?? 45, $request->passing_score_tkp ?? 166, $request->input('passing_type_tkp', 'score'));
                break;

            case 'certification':
                $this->createSubtest($tryout->tryout_id, 'listening', $request->duration_listening ?? 60, $request->passing_score_listening ?? 60, $request->input('passing_type_listening', 'score'));
                $this->createSubtest($tryout->tryout_id, 'writing', $request->duration_writing ?? 60, $request->passing_score_writing ?? 60, $request->input('passing_type_writing', 'score'));
                $this->createSubtest($tryout->tryout_id, 'reading', $request->duration_reading ?? 60, $request->passing_score_reading ?? 60, $request->input('passing_type_reading', 'score'));
                break;

            case 'pppk_full':
                $this->createSubtest($tryout->tryout_id, 'teknis', $request->duration_teknis ?? 90, $request->passing_score_teknis ?? 65, $request->input('passing_type_teknis', 'score'));
                $this->createSubtest($tryout->tryout_id, 'social culture', $request->duration_social_culture ?? 60, $request->passing_score_social_culture ?? 65, $request->input('passing_type_social_culture', 'score'));
                $this->createSubtest($tryout->tryout_id, 'interview', $request->duration_interview ?? 30, $request->passing_score_interview ?? 70, $request->input('passing_type_interview', 'score'));
                break;

            case 'twk':
                $this->createSubtest($tryout->tryout_id, 'twk', $request->duration_general ?? 35, $request->passing_score_general ?? 65, $request->input('passing_type_general', 'score'));
                break;

            case 'tiu':
                $this->createSubtest($tryout->tryout_id, 'tiu', $request->duration_general ?? 90, $request->passing_score_general ?? 80, $request->input('passing_type_general', 'score'));
                break;

            case 'tkp':
                $this->createSubtest($tryout->tryout_id, 'tkp', $request->duration_general ?? 45, $request->passing_score_general ?? 166, $request->input('passing_type_general', 'score'));
                break;

            case 'general':
                $this->createSubtest($tryout->tryout_id, 'general', $request->duration_general ?? 60, $request->passing_score_general ?? 60, $request->input('passing_type_general', 'score'));
                break;

            case 'tpa':
                $this->createSubtest($tryout->tryout_id, 'tpa', $request->duration_general ?? 60, $request->passing_score_general ?? 60, $request->input('passing_type_general', 'score'));
                break;

            case 'tbi':
                $this->createSubtest($tryout->tryout_id, 'tbi', $request->duration_general ?? 60, $request->passing_score_general ?? 60, $request->input('passing_type_general', 'score'));
                break;

            case 'listening':
                $this->createSubtest($tryout->tryout_id, 'listening', $request->duration_general ?? 45, $request->passing_score_general ?? 60, $request->input('passing_type_general', 'score'));
                break;

            case 'reading':
                $this->createSubtest($tryout->tryout_id, 'reading', $request->duration_general ?? 60, $request->passing_score_general ?? 60, $request->input('passing_type_general', 'score'));
                break;

            case 'writing':
                $this->createSubtest($tryout->tryout_id, 'writing', $request->duration_general ?? 60, $request->passing_score_general ?? 60, $request->input('passing_type_general', 'score'));
                break;

            case 'teknis':
                $this->createSubtest($tryout->tryout_id, 'teknis', $request->duration_general ?? 90, $request->passing_score_general ?? 65, $request->input('passing_type_general', 'score'));
                break;

            case 'social culture':
                $this->createSubtest($tryout->tryout_id, 'social culture', $request->duration_general ?? 60, $request->passing_score_general ?? 65, $request->input('passing_type_general', 'score'));
                break;

            case 'interview':
                $this->createSubtest($tryout->tryout_id, 'interview', $request->duration_general ?? 30, $request->passing_score_general ?? 70, $request->input('passing_type_general', 'score'));
                break;
            case 'word':
                $this->createSubtest($tryout->tryout_id, 'word', $request->duration_word ?? 30, $request->passing_score_word ?? 70, $request->input('passing_type_word', 'score'));
                break;
            case 'excel':
                $this->createSubtest($tryout->tryout_id, 'excel', $request->duration_excel ?? 30, $request->passing_score_excel ?? 70, $request->input('passing_type_excel', 'score'));
                break;
            case 'ppt':
                $this->createSubtest($tryout->tryout_id, 'ppt', $request->duration_ppt ?? 30, $request->passing_score_ppt ?? 70, $request->input('passing_type_ppt', 'score'));
                break;

            case 'computer':
                $this->createSubtest($tryout->tryout_id, 'word', $request->duration_word ?? 30, $request->passing_score_word ?? 70, $request->input('passing_type_word', 'score'));
                $this->createSubtest($tryout->tryout_id, 'excel', $request->duration_excel ?? 30, $request->passing_score_excel ?? 70, $request->input('passing_type_excel', 'score'));
                $this->createSubtest($tryout->tryout_id, 'ppt', $request->duration_ppt ?? 30, $request->passing_score_ppt ?? 70, $request->input('passing_type_ppt', 'score'));
                break;

            case 'word':
                $this->createSubtest($tryout->tryout_id, 'word', $request->duration_word ?? 30, $request->passing_score_word ?? 70, $request->input('passing_type_word', 'score'));
                break;

            case 'excel':
                $this->createSubtest($tryout->tryout_id, 'excel', $request->duration_excel ?? 30, $request->passing_score_excel ?? 70, $request->input('passing_type_excel', 'score'));
                break;

            case 'ppt':
                $this->createSubtest($tryout->tryout_id, 'ppt', $request->duration_ppt ?? 30, $request->passing_score_ppt ?? 70, $request->input('passing_type_ppt', 'score'));
                break;
            default:
                $this->createUtbkSingleSubtest($tryout, $request);
                break;
        }
    }

    private function createSubtest($tryoutId, $type, $duration, $passingScore, $passingType)
    {
        TryoutDetail::create([
            'tryout_id' => $tryoutId,
            'type_subtest' => $type,
            'duration' => $duration,
            'passing_score' => $passingScore,
            'passing_type' => $passingType,
        ]);
    }

    private function updateOrCreateSubtest(Tryout $tryout, string $type, $duration, $passingScore, $passingType): void
    {
        $detail = $tryout->tryoutDetails()->where('type_subtest', $type)->first();
        if ($detail) {
            $detail->update([
                'duration' => $duration,
                'passing_score' => $passingScore,
                'passing_type' => $passingType,
            ]);
        } else {
            $this->createSubtest($tryout->tryout_id, $type, $duration, $passingScore, $passingType);
        }
    }

    private function updateTryoutDetails(Tryout $tryout, Request $request): void
    {
        switch ($tryout->type_tryout) {
            case 'utbk_full':
                $this->syncUtbkFullSubtests($tryout, $request);
                break;
            case 'skd_full':
                $this->updateOrCreateSubtest($tryout, 'twk', $request->duration_twk ?? 35, $request->passing_score_twk ?? 65, $request->input('passing_type_twk', 'score'));
                $this->updateOrCreateSubtest($tryout, 'tiu', $request->duration_tiu ?? 90, $request->passing_score_tiu ?? 80, $request->input('passing_type_tiu', 'score'));
                $this->updateOrCreateSubtest($tryout, 'tkp', $request->duration_tkp ?? 45, $request->passing_score_tkp ?? 166, $request->input('passing_type_tkp', 'score'));
                break;
            case 'certification':
                $this->updateOrCreateSubtest($tryout, 'listening', $request->duration_listening ?? 60, $request->passing_score_listening ?? 60, $request->input('passing_type_listening', 'score'));
                $this->updateOrCreateSubtest($tryout, 'writing', $request->duration_writing ?? 60, $request->passing_score_writing ?? 60, $request->input('passing_type_writing', 'score'));
                $this->updateOrCreateSubtest($tryout, 'reading', $request->duration_reading ?? 60, $request->passing_score_reading ?? 60, $request->input('passing_type_reading', 'score'));
                break;
            case 'pppk_full':
                $this->updateOrCreateSubtest($tryout, 'teknis', $request->duration_teknis ?? 90, $request->passing_score_teknis ?? 65, $request->input('passing_type_teknis', 'score'));
                $this->updateOrCreateSubtest($tryout, 'social culture', $request->duration_social_culture ?? 60, $request->passing_score_social_culture ?? 65, $request->input('passing_type_social_culture', 'score'));
                $this->updateOrCreateSubtest($tryout, 'interview', $request->duration_interview ?? 30, $request->passing_score_interview ?? 70, $request->input('passing_type_interview', 'score'));
                break;
            case 'computer':
                $this->updateOrCreateSubtest($tryout, 'word', $request->duration_word ?? 30, $request->passing_score_word ?? 70, $request->input('passing_type_word', 'score'));
                $this->updateOrCreateSubtest($tryout, 'excel', $request->duration_excel ?? 30, $request->passing_score_excel ?? 70, $request->input('passing_type_excel', 'score'));
                $this->updateOrCreateSubtest($tryout, 'ppt', $request->duration_ppt ?? 30, $request->passing_score_ppt ?? 70, $request->input('passing_type_ppt', 'score'));
                break;
            case 'twk':
                $this->updateOrCreateSubtest($tryout, 'twk', $request->duration_general ?? 35, $request->passing_score_general ?? 65, $request->input('passing_type_general', 'score'));
                break;
            case 'tiu':
                $this->updateOrCreateSubtest($tryout, 'tiu', $request->duration_general ?? 90, $request->passing_score_general ?? 80, $request->input('passing_type_general', 'score'));
                break;
            case 'tkp':
                $this->updateOrCreateSubtest($tryout, 'tkp', $request->duration_general ?? 45, $request->passing_score_general ?? 166, $request->input('passing_type_general', 'score'));
                break;
            case 'listening':
                $this->updateOrCreateSubtest($tryout, 'listening', $request->duration_general ?? 45, $request->passing_score_general ?? 60, $request->input('passing_type_general', 'score'));
                break;
            case 'reading':
                $this->updateOrCreateSubtest($tryout, 'reading', $request->duration_general ?? 60, $request->passing_score_general ?? 60, $request->input('passing_type_general', 'score'));
                break;
            case 'writing':
                $this->updateOrCreateSubtest($tryout, 'writing', $request->duration_general ?? 60, $request->passing_score_general ?? 60, $request->input('passing_type_general', 'score'));
                break;
            case 'teknis':
                $this->updateOrCreateSubtest($tryout, 'teknis', $request->duration_general ?? 90, $request->passing_score_general ?? 65, $request->input('passing_type_general', 'score'));
                break;
            case 'social culture':
                $this->updateOrCreateSubtest($tryout, 'social culture', $request->duration_general ?? 60, $request->passing_score_general ?? 65, $request->input('passing_type_general', 'score'));
                break;
            case 'interview':
                $this->updateOrCreateSubtest($tryout, 'interview', $request->duration_general ?? 30, $request->passing_score_general ?? 70, $request->input('passing_type_general', 'score'));
                break;
            case 'general':
                $this->updateOrCreateSubtest($tryout, 'general', $request->duration_general ?? 60, $request->passing_score_general ?? 60, $request->input('passing_type_general', 'score'));
                break;
            case 'tpa':
                $this->updateOrCreateSubtest($tryout, 'tpa', $request->duration_general ?? 60, $request->passing_score_general ?? 60, $request->input('passing_type_general', 'score'));
                break;
            case 'tbi':
                $this->updateOrCreateSubtest($tryout, 'tbi', $request->duration_general ?? 60, $request->passing_score_general ?? 60, $request->input('passing_type_general', 'score'));
                break;
            case 'word':
                $this->updateOrCreateSubtest($tryout, 'word', $request->duration_word ?? $request->duration_general ?? 30, $request->passing_score_word ?? $request->passing_score_general ?? 70, $request->input('passing_type_word', $request->input('passing_type_general', 'score')));
                break;
            case 'excel':
                $this->updateOrCreateSubtest($tryout, 'excel', $request->duration_excel ?? $request->duration_general ?? 30, $request->passing_score_excel ?? $request->passing_score_general ?? 70, $request->input('passing_type_excel', $request->input('passing_type_general', 'score')));
                break;
            case 'ppt':
                $this->updateOrCreateSubtest($tryout, 'ppt', $request->duration_ppt ?? $request->duration_general ?? 30, $request->passing_score_ppt ?? $request->passing_score_general ?? 70, $request->input('passing_type_ppt', $request->input('passing_type_general', 'score')));
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
            $passingTypeField = 'passing_type_' . $type;

            $duration = $request->input($durationField, $config['default_duration']);
            $passing = $request->input($passingField, $config['default_passing']);
            $passingType = $request->input($passingTypeField, 'score');

            $this->updateOrCreateSubtest($tryout, $type, $duration, $passing, $passingType);
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
        $passingTypeField = 'passing_type_' . $slug;

        $duration = $request->input($durationField, $defaults['default_duration']);
        $passing = $request->input($passingField, $defaults['default_passing']);
        $passingType = $request->input($passingTypeField, 'score');

        if ($isUpdate) {
            $this->updateOrCreateSubtest($tryout, $slug, $duration, $passing, $passingType);
        } else {
            $this->createSubtest($tryout->tryout_id, $slug, $duration, $passing, $passingType);
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
            'tpa'               => 'TPA',
            'tbi'               => 'TBI',
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

    private function tryoutValidationRules(?string $currentType = null): array
    {
        $typeOptions = [
            'tiu',
            'twk',
            'tkp',
            'skd_full',
            'general',
            'tpa',
            'tbi',
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
        ];

        if ($this->allowUtbkControls($currentType)) {
            $typeOptions = array_merge(
                $typeOptions,
                ['utbk_full', 'utbk_section'],
                array_keys(self::UTBK_SINGLE_TYPES)
            );
        }

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type_tryout' => ['required', Rule::in($typeOptions)],
            'assessment_type' => ['required', Rule::in(['standard', 'pre_test', 'post_test'])],
            'section_break_duration' => 'nullable|integer|min:0|max:3600',
            'answer_persistence_mode' => ['nullable', Rule::in(['client_side', 'hybrid_subtest'])],
            'subtest_display_mode' => ['nullable', Rule::in(['per_subtest', 'combined'])],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_certification' => 'boolean',
            'is_active' => 'boolean',
            'is_toefl' => 'boolean',
            'is_irt' => 'boolean',
            'enable_anti_copy' => 'boolean',
            'enable_tab_switch_detection' => 'boolean',
            'enable_webcam_check' => 'boolean',
            'enable_screen_check' => 'boolean',
            'price' => 'nullable|numeric|min:0',
        ];

        foreach (array_keys(self::UTBK_SUBTESTS) as $slug) {
            $rules['duration_' . $slug] = 'nullable|integer|min:1';
            $rules['passing_score_' . $slug] = 'nullable|numeric|min:0|max:100';
            $rules['passing_type_' . $slug] = 'nullable|in:score,percentage';
        }

        $passingTypeFields = [
            'twk',
            'tiu',
            'tkp',
            'general',
            'listening',
            'reading',
            'writing',
            'teknis',
            'social_culture',
            'interview',
            'word',
            'excel',
            'ppt',
        ];

        foreach ($passingTypeFields as $field) {
            $rules['passing_type_' . $field] = 'nullable|in:score,percentage';
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

    private function allowUtbkControls(?string $currentType = null): bool
    {
        return $this->utbkEnabled() || $this->isUtbkType($currentType);
    }

    private function utbkEnabled(): bool
    {
        return (bool) config('client.branding.utbk_enabled', true);
    }

    private function isUtbkType(?string $type): bool
    {
        if (!$type) {
            return false;
        }

        return $type === 'utbk_full'
            || $type === 'utbk_section'
            || array_key_exists($type, self::UTBK_SINGLE_TYPES);
    }

    private function recalculateTryoutPassedStatus(Tryout $tryout): void
    {
        UserAnswer::where('tryout_id', $tryout->tryout_id)
            ->whereIn('status', ['completed', 'pending_release'])
            ->orderBy('user_answer_id')
            ->chunkById(200, function ($answers) {
                foreach ($answers as $answer) {
                    $answer->loadMissing(['tryoutDetail', 'userAnswerDetails.question', 'userAnswerDetails.questionOption']);
                    $detail = $answer->tryoutDetail;
                    if (! $detail) {
                        continue;
                    }

                    $type = $detail->type_subtest;
                    $rawScore = $this->calculateTotalScore($answer, $type);
                    $maxScore = $this->getMaxPossibleScoreForDetail($answer->tryout_detail_id, $type);
                    $isPassed = $this->isSubtestPassed($detail, $rawScore, $maxScore, $type);

                    $answer->update([
                        'is_passed' => $isPassed,
                    ]);
                }
            }, 'user_answer_id');
    }

    private function calculateTotalScore(UserAnswer $userAnswer, string $type_subtest): float
    {
        $totalScore = 0.0;

        $details = UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->with(['questionOption', 'question'])
            ->get();

        foreach ($details as $detail) {
            $question = $detail->question;
            if (! $question) {
                continue;
            }

            $questionType = $question->question_type ?? 'multiple_choice';
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
            $pendingReview = (bool) ($answerMeta['pending_review'] ?? false);

            switch ($questionType) {
                case 'multiple_answer':
                    $totalScore += $this->resolveMultipleAnswerAwardedScore($question, $detail);
                    break;

                case 'matching':
                    $totalScore += $this->resolveMatchingAwardedScore($question, $detail);
                    break;

                case 'multiple_true_false':
                    $totalScore += $this->resolveMultipleTrueFalseAwardedScore($question, $detail);
                    break;

                case 'short_answer':
                case 'essay':
                    if ($pendingReview) {
                        continue 2;
                    }
                    // Gunakan score_obtained dari answer_json (hasil koreksi AI/manual)
                    $scoreObtained = isset($answerMeta['score_obtained']) ? (float) $answerMeta['score_obtained'] : null;
                    if ($scoreObtained !== null) {
                        $totalScore += $scoreObtained;
                    } else {
                        // Fallback: gunakan essay_score_correct atau default_weight
                        $weight = (float) ($question->getEssayScoreCorrect() ?? $question->default_weight ?? 1);
                        $totalScore += $detail->is_correct ? ($weight > 0 ? $weight : 1) : 0;
                    }
                    break;

                case 'audio':
                    continue 2;

                default:
                    if ($detail->questionOption) {
                        switch ($type_subtest) {
                            case 'twk':
                            case 'tiu':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 5) : 0;
                                break;
                            case 'tkp':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $w > 0 ? $w : 1;
                                break;
                            case 'writing':
                            case 'reading':
                            case 'listening':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 10) : 0;
                                break;
                            default:
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 1) : 0;
                                break;
                        }
                    }
                    break;
            }
        }

        return $totalScore;
    }

    private function resolveMultipleAnswerAwardedScore(Question $question, UserAnswerDetail $detail): float
    {
        $defaultWeight = (float) ($question->default_weight ?? 1);
        $maxWeight = $defaultWeight > 0 ? $defaultWeight : 1;
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $selectedIds = collect($meta['selected_option_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (!empty($selectedIds)) {
            $correctIds = $question->questionOptions()
                ->where('is_correct', true)
                ->pluck('question_option_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            sort($selectedIds);
            sort($correctIds);
            $multipleAnswerMeta = is_array($question->metadata) ? ($question->metadata['multiple_answer'] ?? []) : [];
            $matchedCorrect = count(array_intersect($selectedIds, $correctIds));
            $wrongSelected = max(0, count($selectedIds) - $matchedCorrect);
            $scoreCorrect = (float) ($multipleAnswerMeta['score_correct'] ?? (($maxWeight > 0 && count($correctIds) > 0) ? ($maxWeight / count($correctIds)) : 1));
            $scoreWrong = (float) ($multipleAnswerMeta['score_wrong'] ?? 0);
            $scoringMode = in_array(($multipleAnswerMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
                ? $multipleAnswerMeta['scoring_mode']
                : 'fullscore';
            $totalCorrectCount = max(1, count($correctIds));
            $missedCorrect = max(0, $totalCorrectCount - $matchedCorrect);
            $wrongCount = $missedCorrect + $wrongSelected;
            $isExactCorrect = ($selectedIds === $correctIds);
            $fullScore = $scoreCorrect;
            $score = 0.0;

            if ($scoringMode === 'partial') {
                $score = $matchedCorrect > 0
                    ? ($matchedCorrect / $totalCorrectCount) * $fullScore
                    : $scoreWrong;
            } else {
                $score = $isExactCorrect ? $scoreCorrect : $scoreWrong;
            }

            return max(0, $score);
        }

        $storedScore = $meta['score_obtained'] ?? null;
        if (is_numeric($storedScore)) {
            return max(0, min((float) $storedScore, $maxWeight));
        }

        return $detail->is_correct ? $maxWeight : 0;
    }

    private function resolveMatchingAwardedScore(Question $question, UserAnswerDetail $detail): float
    {
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $questionMeta = is_array($question->metadata) ? $question->metadata : [];
        $matchingMeta = is_array($questionMeta['matching_scores'] ?? null) ? $questionMeta['matching_scores'] : [];
        $scoreCorrect = (float) ($matchingMeta['score_correct'] ?? 1);
        $scoreWrong = (float) ($matchingMeta['score_wrong'] ?? 0);
        $scoringMode = in_array(($matchingMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $matchingMeta['scoring_mode']
            : 'fullscore';

        $summary = is_array($meta['summary'] ?? null) ? $meta['summary'] : [];
        $correctCount = (int) ($summary['correct'] ?? 0);
        $totalCount = (int) ($summary['total'] ?? 0);
        $wrongCount = max(0, $totalCount - $correctCount);

        if ($totalCount > 0) {
            $fullScore = max(0, $scoreCorrect);
            $isExactCorrect = ($correctCount === $totalCount);
            $score = 0.0;
            if ($scoringMode === 'partial') {
                $score = $correctCount > 0
                    ? ($correctCount / $totalCount) * $fullScore
                    : $scoreWrong;
            } else {
                $score = $isExactCorrect ? $fullScore : $scoreWrong;
            }

            return max(0, $score);
        }

        $storedScore = $meta['score_obtained'] ?? null;
        if (is_numeric($storedScore)) {
            return max(0, (float) $storedScore);
        }

        $weight = (float) ($question->default_weight ?? 1);
        return $detail->is_correct ? max(0, $weight) : 0;
    }

    private function resolveMultipleTrueFalseAwardedScore(Question $question, UserAnswerDetail $detail): float
    {
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $questionMeta = is_array($question->metadata) ? ($question->metadata['multiple_true_false'] ?? []) : [];
        $scoreCorrect = (float) ($questionMeta['score_correct'] ?? ($question->default_weight ?? 1));
        $scoreWrong = (float) ($questionMeta['score_wrong'] ?? 0);
        $scoringMode = in_array(($questionMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $questionMeta['scoring_mode']
            : 'fullscore';

        $summary = is_array($meta['summary'] ?? null) ? $meta['summary'] : [];
        $correctCount = (int) ($summary['correct'] ?? 0);
        $totalCount = (int) ($summary['total'] ?? 0);

        if ($totalCount > 0) {
            $fullScore = max(0, $scoreCorrect);
            $isExactCorrect = $correctCount === $totalCount;
            if ($scoringMode === 'partial') {
                return max(0, $correctCount > 0 ? ($correctCount / $totalCount) * $fullScore : $scoreWrong);
            }

            return max(0, $isExactCorrect ? $fullScore : $scoreWrong);
        }

        $storedScore = $meta['score_obtained'] ?? null;
        if (is_numeric($storedScore)) {
            return max(0, (float) $storedScore);
        }

        $weight = (float) ($question->default_weight ?? 1);
        return $detail->is_correct ? max(0, $weight) : 0;
    }

    private function getMaxPossibleScoreForDetail(int $tryoutDetailId, ?string $type_subtest): float
    {
        $questions = Question::where('tryout_detail_id', $tryoutDetailId)
            ->with('questionOptions')
            ->get();

        if ($questions->isEmpty()) {
            return 0;
        }

        $total = 0.0;

        foreach ($questions as $question) {
            $questionType = $question->question_type ?? 'multiple_choice';

            switch ($questionType) {
                case 'multiple_answer':
                    $weight = (float) ($question->default_weight ?? 1);
                    $total += $weight > 0 ? $weight : 1;
                    break;

                case 'matching':
                    $matchingMeta = is_array($question->metadata['matching_scores'] ?? null) ? $question->metadata['matching_scores'] : [];
                    $weight = (float) ($matchingMeta['score_correct'] ?? ($question->default_weight ?? 0));
                    if ($weight <= 0) {
                        $weight = 1;
                    }
                    $total += $weight;
                    break;

                case 'multiple_true_false':
                    $mtfMeta = is_array($question->metadata['multiple_true_false'] ?? null) ? $question->metadata['multiple_true_false'] : [];
                    $weight = (float) ($mtfMeta['score_correct'] ?? ($question->default_weight ?? 0));
                    if ($weight <= 0) {
                        $weight = 1;
                    }
                    $total += $weight;
                    break;

                case 'short_answer':
                case 'essay':
                    // Gunakan essay_score_correct (field "Benar") untuk max score
                    $weight = (float) ($question->getEssayScoreCorrect() ?? $question->default_weight ?? 1);
                    $total += $weight > 0 ? $weight : 1;
                    break;

                case 'audio':
                    break;

                default:
                    $options = $question->questionOptions;
                    switch ($type_subtest) {
                        case 'twk':
                        case 'tiu':
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 5;
                            break;
                        case 'tkp':
                            $maxWeight = (float) ($options->max('weight') ?? 0);
                            $total += $maxWeight > 0 ? $maxWeight : 1;
                            break;
                        case 'writing':
                        case 'reading':
                        case 'listening':
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 10;
                            break;
                        default:
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 1;
                            break;
                    }
                    break;
            }
        }

        return $total;
    }

    private function getDefaultPassingScore(?string $type_subtest): int
    {
        return match ($type_subtest) {
            'word', 'excel', 'ppt' => 70,
            'teknis', 'social culture', 'management', 'interview' => 65,
            default => 60,
        };
    }

    private function isSubtestPassed($detail, float $rawScore, float $maxScore, ?string $type): bool
    {
        $passingScore = $detail?->passing_score ?? $this->getDefaultPassingScore($type);
        if ($passingScore === null) {
            return false;
        }

        $passingType = $detail?->passing_type ?? 'score';
        if ($passingType === 'percentage') {
            $percentage = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;
            return $percentage >= $passingScore;
        }

        return $rawScore >= $passingScore;
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
