<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\MaterialCategory;
use App\Models\CertificateTemplate;
use App\Models\ClientProfile;
use App\Models\Package;
use App\Models\Question;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use App\Services\PlanQuotaService;
use App\Services\PurchaseAccessDuration;
use App\Services\TryoutQuestionDownloadService;
use App\Services\MultipleAnswerScoringService;
use App\Services\UtbkResultReleaseService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
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

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $type = strtolower((string) $request->query('type', ''));
        $filterStatus = $request->query('status');

        if (! in_array($filterStatus, ['akan_datang', 'aktif', 'selesai'], true)) {
            $filterStatus = null;
        }

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
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($type !== '', fn ($query) => $query->where('type_tryout', $type))
            ->when($filterStatus === 'akan_datang', fn ($query) => $query->where('start_date', '>', now()))
            ->when($filterStatus === 'selesai', fn ($query) => $query->where('end_date', '<', now()))
            ->when($filterStatus === 'aktif', fn ($query) => $query
                ->where(function ($activeQuery): void {
                    $activeQuery->whereNull('start_date')->orWhere('start_date', '<=', now());
                })
                ->where(function ($activeQuery): void {
                    $activeQuery->whereNull('end_date')->orWhere('end_date', '>=', now());
                }))
            ->latest()
            ->paginate(\App\Support\Pagination::perPage(10))
            ->withQueryString();

        $tryouts->getCollection()->each(function ($tryout) {
            $tryout->tryoutDetails->each(function ($detail) {
                $detail->setAttribute('subtest_name', $this->subtestLabel($detail->type_subtest));
            });
        });

        $packages = Package::all();

        return view('admin.pages.tryout.index', compact('tryouts', 'packages', 'search', 'type', 'filterStatus'));
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

    private const LEGACY_TRYOUT_TYPES = [
        'tiu',
        'twk',
        'tkp',
        'skd_full',
        'general',
        'tpa',
        'tob',
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
        'utbk_penalaran_umum',
        'utbk_pengetahuan_umum',
        'utbk_pengetahuan_kuantitatif',
        'utbk_pemahaman_bacaan_menulis',
        'utbk_literasi_bahasa_indonesia',
        'utbk_literasi_bahasa_inggris',
        'utbk_penalaran_matematika',
    ];

    public function create()
    {
        $packages = Package::all();
        $allowUtbkTypes = $this->allowUtbkControls();
        $utbkSubtests = $allowUtbkTypes ? $this->getUtbkSubtests() : [];
        $utbkSingleTypes = $allowUtbkTypes ? $this->getUtbkSingleTypeOptions() : [];
        $tryoutTypeOptions = $this->getTryoutTypeOptions($allowUtbkTypes);
        $certificateTemplates = CertificateTemplate::query()
            ->where('client_profile_id', $this->clientProfileId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $securityDefaults = PlanQuotaService::getDefaultProctoringSettings();

        return view('admin.pages.tryout.create', compact('packages', 'utbkSubtests', 'utbkSingleTypes', 'allowUtbkTypes', 'tryoutTypeOptions', 'certificateTemplates', 'securityDefaults'));
    }

    public function store(Request $request)
    {
        $this->normalizeDurationInputs($request);
        $request->validate($this->tryoutValidationRules());
        $scoringMethod = $this->normalizedScoringMethod($request);
        $saleData = $this->individualSaleData($request);
        $lobbyTokenData = $this->lobbyTokenData($request);
        $isIrtEnabled = $scoringMethod === 'irt_utbk';
        $isToeflEnabled = $scoringMethod === 'toefl_itp';
        $securitySettings = PlanQuotaService::proctoringSettingsFromRequest($request);
        $cardDisplay = $request->input('user_card_display', 'icon');

        if ($cardDisplay === 'thumbnail' && ! $request->hasFile('thumbnail')) {
            return back()
                ->withErrors(['thumbnail' => 'Thumbnail wajib diupload jika tampilan kartu user memakai thumbnail.'])
                ->withInput();
        }

        $thumbnailUrl = $cardDisplay === 'thumbnail'
            ? $this->storeTryoutThumbnail($request)
            : null;

        try {
            $tryout = Tryout::create([
                'created_by' => $request->user()?->id,
                'name' => $request->name,
                'description' => $request->input('description') ?? '',
                'type_tryout' => $request->type_tryout,
                'material_category_id' => $this->categoryIdForCode($request->type_tryout),
                'assessment_type' => $request->assessment_type,
                'section_break_duration' => max(0, (int) $request->input('section_break_duration', 0)),
                'max_attempts' => max(0, (int) $request->input('max_attempts', 0)),
                'answer_persistence_mode' => $this->normalizedAnswerPersistenceMode($request),
                'subtest_display_mode' => $request->input('subtest_display_mode', 'per_subtest'),
                'user_card_display' => $cardDisplay,
                'thumbnail_url' => $thumbnailUrl,
                'icon_class' => 'ri-file-list-3-line',
                'enable_anti_copy' => $securitySettings['enable_anti_copy'],
                'enable_tab_switch_detection' => $securitySettings['enable_tab_switch_detection'],
                'enable_webcam_check' => $securitySettings['enable_webcam_check'],
                'enable_screen_check' => $securitySettings['enable_screen_check'],
                'is_certification' => $request->has('is_certification'),
                'certificate_template_id' => $request->has('is_certification') ? $request->input('certificate_template_id') : null,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->has('is_active'),
                'is_toefl' => $isToeflEnabled,
                'is_irt' => $isIrtEnabled,
                'scoring_method' => $scoringMethod,
                ...$saleData,
                'is_displayed' => $request->has('is_displayed'),
                'show_discussion' => $request->has('show_discussion'),
                ...$lobbyTokenData,
                'show_leaderboard' => $request->has('show_leaderboard'),
                'show_passing_grade' => $request->has('show_passing_grade'),
                'show_result_scores' => $request->has('show_result_scores'),
                'result_score_display' => $request->input('result_score_display', 'total_and_subtest'),
                'results_release_at' => $isIrtEnabled ? ($request->end_date ?? null) : null,
                'results_released_at' => null,
                ...$this->accessDurationData($request),
            ]);

            $this->createTryoutDetails($tryout, $request);

            return redirect()->route('admin.tryout.index')
                ->with('success', 'Tryout "'.$tryout->name.'" berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan tryout: '.$e->getMessage())
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
            $tryoutTypeOptions = $this->getTryoutTypeOptions($allowUtbkTypes, $tryout->type_tryout);
            $certificateTemplates = CertificateTemplate::query()
                ->where('client_profile_id', $this->clientProfileId())
                ->where(function ($query) use ($tryout): void {
                    $query->where('is_active', true)
                        ->orWhere('certificate_template_id', $tryout->certificate_template_id);
                })
                ->orderBy('name')
                ->get();
            $securityDefaults = PlanQuotaService::getDefaultProctoringSettings();

            return view('admin.pages.tryout.create', compact('tryout', 'utbkSubtests', 'utbkSingleTypes', 'allowUtbkTypes', 'tryoutTypeOptions', 'certificateTemplates', 'securityDefaults'));
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

        $this->normalizeDurationInputs($request);
        $request->validate($this->tryoutValidationRules($tryout->type_tryout));
        // Metode scoring dikunci sejak tryout dibuat agar perubahan konfigurasi
        // tidak mengubah arti nilai dan riwayat hasil peserta.
        $scoringMethod = $this->storedScoringMethod($tryout);
        $saleData = $this->individualSaleData($request);
        $lobbyTokenData = $this->lobbyTokenData($request, $tryout);
        $isIrtEnabled = $scoringMethod === 'irt_utbk';
        $isToeflEnabled = $scoringMethod === 'toefl_itp';

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
            $cardDisplay = $request->input('user_card_display', 'icon');
            $thumbnailUrl = $tryout->thumbnail_url;

            if ($cardDisplay === 'thumbnail') {
                if ($request->hasFile('thumbnail')) {
                    $this->deleteTryoutThumbnail($tryout->thumbnail_url);
                    $thumbnailUrl = $this->storeTryoutThumbnail($request);
                } elseif (blank($thumbnailUrl)) {
                    return back()
                        ->withErrors(['thumbnail' => 'Thumbnail wajib diupload jika tampilan kartu user memakai thumbnail.'])
                        ->withInput();
                }
            } else {
                $this->deleteTryoutThumbnail($tryout->thumbnail_url);
                $thumbnailUrl = null;
            }

            // Update master tryout fields
            $tryout->update([
                'name' => $request->name,
                'description' => $request->input('description') ?? '',
                'type_tryout' => $request->type_tryout,
                'material_category_id' => $this->categoryIdForCode($request->type_tryout),
                'assessment_type' => $request->assessment_type,
                'section_break_duration' => max(0, (int) $request->input('section_break_duration', 0)),
                'max_attempts' => max(0, (int) $request->input('max_attempts', 0)),
                'answer_persistence_mode' => $this->normalizedAnswerPersistenceMode($request),
                'subtest_display_mode' => $request->input('subtest_display_mode', 'per_subtest'),
                'user_card_display' => $cardDisplay,
                'thumbnail_url' => $thumbnailUrl,
                'icon_class' => 'ri-file-list-3-line',
                'enable_anti_copy' => $securitySettings['enable_anti_copy'],
                'enable_tab_switch_detection' => $securitySettings['enable_tab_switch_detection'],
                'enable_webcam_check' => $securitySettings['enable_webcam_check'],
                'enable_screen_check' => $securitySettings['enable_screen_check'],
                'is_certification' => $request->has('is_certification'),
                'certificate_template_id' => $request->has('is_certification') ? $request->input('certificate_template_id') : null,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->has('is_active'),
                'is_toefl' => $isToeflEnabled,
                'is_irt' => $isIrtEnabled,
                'scoring_method' => $scoringMethod,
                ...$saleData,
                'is_displayed' => $request->has('is_displayed'),
                'show_discussion' => $request->has('show_discussion'),
                ...$lobbyTokenData,
                'show_leaderboard' => $request->has('show_leaderboard'),
                'show_passing_grade' => $request->has('show_passing_grade'),
                'show_result_scores' => $request->has('show_result_scores'),
                'result_score_display' => $request->input('result_score_display', 'total_and_subtest'),
                'results_release_at' => $isIrtEnabled ? $request->input('end_date') : null,
                'results_released_at' => $isIrtEnabled ? $tryout->results_released_at : null,
                ...$this->accessDurationData($request),
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

            return redirect()->route('admin.tryout.index', $request->query())
                ->with('success', 'Tryout berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui tryout: '.$e->getMessage())
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
                ->with('error', 'Gagal menghapus tryout: '.$e->getMessage());
        }
    }

    public function clone(Request $request, Tryout $tryout)
    {
        try {
            $tryout->load([
                'tryoutDetails.questions.questionOptions',
            ]);

            $clone = DB::transaction(function () use ($tryout, $request) {
                $newTryout = $tryout->replicate();
                $newTryout->created_by = $request->user()?->id;
                $newTryout->name = $this->uniqueCloneName($tryout->name);
                $newTryout->is_active = false;
                $newTryout->is_displayed = false;
                $newTryout->results_released_at = null;
                $newTryout->results_reset_at = null;
                $newTryout->save();

                foreach ($tryout->tryoutDetails as $detail) {
                    $newDetail = $detail->replicate();
                    $newDetail->tryout_id = $newTryout->tryout_id;
                    $newDetail->save();

                    foreach ($detail->questions as $question) {
                        $newQuestion = $question->replicate();
                        $newQuestion->tryout_detail_id = $newDetail->tryout_detail_id;
                        $newQuestion->save();

                        foreach ($question->questionOptions as $option) {
                            $newOption = $option->replicate();
                            $newOption->question_id = $newQuestion->question_id;
                            $newOption->save();
                        }
                    }
                }

                return $newTryout;
            });

            return redirect()
                ->route('admin.tryout.index', $request->query())
                ->with('success', 'Tryout berhasil diclone ke "'.$clone->name.'". Clone dibuat nonaktif dan tidak ditampilkan agar bisa dicek dulu.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.tryout.index', $request->query())
                ->with('error', 'Gagal clone tryout: '.$e->getMessage());
        }
    }

    private function uniqueCloneName(string $name): string
    {
        $baseName = $name.' (Copy)';
        $candidate = $baseName;
        $counter = 2;

        while (Tryout::where('name', $candidate)->exists()) {
            $candidate = $name.' (Copy '.$counter.')';
            $counter++;
        }

        return $candidate;
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
                },
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

    public function downloadQuestions(Request $request, Tryout $tryout, TryoutQuestionDownloadService $questionDownloadService): HttpResponse
    {
        $type = $request->validate([
            'type' => ['nullable', 'in:soal,pembahasan'],
        ])['type'] ?? 'soal';
        $questions = Question::query()
            ->with(['questionOptions', 'tryoutDetail'])
            ->whereHas('tryoutDetail', fn ($query) => $query->where('tryout_id', $tryout->tryout_id))
            ->orderBy('tryout_detail_id')
            ->orderBy('question_id')
            ->get();

        return $questionDownloadService->download($tryout, $questions, $type);
    }

    private function createTryoutDetails(Tryout $tryout, Request $request): void
    {
        $dynamicSubtestCategories = $this->dynamicSubtestCategoriesFor($tryout->type_tryout);

        if ($dynamicSubtestCategories->isNotEmpty()) {
            foreach ($dynamicSubtestCategories as $category) {
                $this->createSubtestForMaterialCategory($tryout, $category, $request);
            }

            return;
        }

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

            case 'management':
                $this->createSubtest($tryout->tryout_id, 'management', $request->duration_general ?? 60, $request->passing_score_general ?? 65, $request->input('passing_type_general', 'score'));
                break;

            case 'interview':
                $this->createSubtest($tryout->tryout_id, 'interview', $request->duration_general ?? 30, $request->passing_score_general ?? 70, $request->input('passing_type_general', 'score'));
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
                if ($this->getUtbkSlugForType($tryout->type_tryout)) {
                    $this->createUtbkSingleSubtest($tryout, $request);
                    break;
                }

                $this->createSubtest(
                    $tryout->tryout_id,
                    $tryout->type_tryout,
                    $request->duration_general ?? 60,
                    $request->passing_score_general ?? 60,
                    $request->input('passing_type_general', 'score')
                );
                break;
        }
    }

    private function createSubtest($tryoutId, $type, $duration, $passingScore, $passingType)
    {
        TryoutDetail::create([
            'tryout_id' => $tryoutId,
            'type_subtest' => $type,
            'material_category_id' => $this->categoryIdForCode($type),
            'duration' => $duration,
            'passing_score' => $passingScore,
            'passing_type' => $passingType,
        ]);
    }

    /**
     * Buat detail subtest dari subkategori yang dipilih pada kategori induk.
     */
    private function createSubtestForMaterialCategory(Tryout $tryout, MaterialCategory $category, Request $request): void
    {
        TryoutDetail::create([
            'tryout_id' => $tryout->tryout_id,
            'type_subtest' => $category->code,
            'material_category_id' => $category->category_id,
            'duration' => $this->dynamicSubtestInput($request, 'duration', $category->code, 60),
            'passing_score' => $this->dynamicSubtestInput($request, 'passing_score', $category->code, 60),
            'passing_type' => $this->dynamicSubtestInput($request, 'passing_type', $category->code, 'score'),
        ]);
    }

    private function updateOrCreateSubtest(Tryout $tryout, string $type, $duration, $passingScore, $passingType): void
    {
        $detail = $tryout->tryoutDetails()->where('type_subtest', $type)->first();
        if ($detail) {
            $detail->update([
                'material_category_id' => $this->categoryIdForCode($type),
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
        $dynamicSubtestCategories = $this->dynamicSubtestCategoriesFor($tryout->type_tryout);

        if ($dynamicSubtestCategories->isNotEmpty()) {
            $this->syncDynamicCategorySubtests($tryout, $dynamicSubtestCategories, $request);

            return;
        }

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
            case 'management':
                $this->updateOrCreateSubtest($tryout, 'management', $request->duration_general ?? 60, $request->passing_score_general ?? 65, $request->input('passing_type_general', 'score'));
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
                if ($this->getUtbkSlugForType($tryout->type_tryout)) {
                    $this->createUtbkSingleSubtest($tryout, $request, true);
                    break;
                }

                $this->updateOrCreateSubtest(
                    $tryout,
                    $tryout->type_tryout,
                    $request->duration_general ?? 60,
                    $request->passing_score_general ?? 60,
                    $request->input('passing_type_general', 'score')
                );
                $tryout->tryoutDetails()->where('type_subtest', '!=', $tryout->type_tryout)->delete();
                break;
        }
    }

    /**
     * Sinkronkan detail saat subkategori pada kategori tryout dinamis berubah.
     * Subtest lama yang tidak lagi terdaftar di kategori akan dihapus.
     */
    private function syncDynamicCategorySubtests(Tryout $tryout, Collection $categories, Request $request): void
    {
        $types = $categories->pluck('code')->all();

        foreach ($categories as $category) {
            $tryout->tryoutDetails()->updateOrCreate(
                ['type_subtest' => $category->code],
                [
                    'material_category_id' => $category->category_id,
                    'duration' => $this->dynamicSubtestInput($request, 'duration', $category->code, 60),
                    'passing_score' => $this->dynamicSubtestInput($request, 'passing_score', $category->code, 60),
                    'passing_type' => $this->dynamicSubtestInput($request, 'passing_type', $category->code, 'score'),
                ]
            );
        }

        $tryout->tryoutDetails()->whereNotIn('type_subtest', $types)->delete();
    }

    private function dynamicSubtestInput(Request $request, string $field, string $code, mixed $default): mixed
    {
        return $request->input("{$field}_{$code}", $request->input("{$field}_general", $default));
    }

    /**
     * Kategori induk dengan subkategori aktif adalah tryout multi-subtest.
     */
    private function dynamicSubtestCategoriesFor(?string $type): Collection
    {
        if (
            blank($type)
            || ! Schema::hasTable('material_categories')
            || ! Schema::hasColumn('material_categories', 'code')
        ) {
            return collect();
        }

        $parentCategory = MaterialCategory::query()
            ->active()
            ->whereNull('parent_id')
            ->where('code', $type)
            ->with([
                'children' => fn ($query) => $query->active()->withCode()->ordered(),
            ])
            ->first();

        return $parentCategory?->children ?? collect();
    }

    private function syncUtbkFullSubtests(Tryout $tryout, Request $request): void
    {
        $allowedTypes = array_keys(self::UTBK_SUBTESTS);

        foreach (self::UTBK_SUBTESTS as $type => $config) {
            $durationField = 'duration_'.$type;
            $passingField = 'passing_score_'.$type;
            $passingTypeField = 'passing_type_'.$type;

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
        $durationField = 'duration_'.$slug;
        $passingField = 'passing_score_'.$slug;
        $passingTypeField = 'passing_type_'.$slug;

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
            'twk' => 'Tes Wawasan Kebangsaan',
            'tiu' => 'Tes Intelegensi Umum',
            'tkp' => 'Tes Karakteristik Pribadi',
            'tpa' => 'TPA',
            'tbi' => 'TBI',
            'tob' => 'TOB',
            'writing' => 'Writing Test',
            'reading' => 'Reading Comprehension',
            'listening' => 'Listening Test',
            'teknis' => 'Tes Teknis',
            'social culture' => 'Sosial-Kultural & Manajerial',
            'management' => 'Manajerial',
            'interview' => 'Wawancara',
            'word' => 'Microsoft Word',
            'excel' => 'Microsoft Excel',
            'ppt' => 'Microsoft PowerPoint',
            'penalaran_umum' => 'Penalaran Umum',
            'pengetahuan_umum' => 'Pengetahuan & Pemahaman Umum',
            'pengetahuan_kuantitatif' => 'Pengetahuan Kuantitatif',
            'pemahaman_bacaan_menulis' => 'Pemahaman Bacaan & Menulis',
            'literasi_bahasa_indonesia' => 'Literasi Bahasa Indonesia',
            'literasi_bahasa_inggris' => 'Literasi Bahasa Inggris',
            'penalaran_matematika' => 'Penalaran Matematika',
        ];

        // Fallback: bikin judul yang oke kalau kodenya belum dipetakan
        return $map[$key] ?? Str::headline((string) $type);
    }

    private function normalizedScoringMethod(Request $request): string
    {
        $method = $request->input('scoring_method', 'normal');

        if ($method === 'irt') {
            $method = 'irt_utbk';
        }

        if ($method === 'toefl_itp' && ! in_array($request->type_tryout, ['certification', 'listening', 'reading', 'writing'], true)) {
            return 'normal';
        }

        return in_array($method, ['normal', 'irt_utbk', 'toefl_itp'], true)
            ? $method
            : 'normal';
    }

    private function storedScoringMethod(Tryout $tryout): string
    {
        if (in_array($tryout->scoring_method, ['normal', 'irt_utbk', 'toefl_itp'], true)) {
            return $tryout->scoring_method;
        }

        if ($tryout->scoring_method === 'irt' || $tryout->is_irt) {
            return 'irt_utbk';
        }

        return $tryout->is_toefl ? 'toefl_itp' : 'normal';
    }

    private function storeTryoutThumbnail(Request $request): ?string
    {
        if (! $request->hasFile('thumbnail')) {
            return null;
        }

        $path = $request->file('thumbnail')->store('tryouts/thumbnails', 'public');

        return Storage::url($path);
    }

    private function deleteTryoutThumbnail(?string $thumbnailUrl): void
    {
        if (blank($thumbnailUrl) || ! Str::startsWith($thumbnailUrl, '/storage/')) {
            return;
        }

        Storage::disk('public')->delete(Str::after($thumbnailUrl, '/storage/'));
    }

    private function clientProfileId(): ?int
    {
        return ClientProfile::query()->value('id');
    }

    private function tryoutValidationRules(?string $currentType = null): array
    {
        $typeOptions = array_keys($this->getTryoutTypeOptions($this->allowUtbkControls($currentType), $currentType));

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_tryout' => ['required', Rule::in($typeOptions)],
            'assessment_type' => ['required', Rule::in(['standard', 'pre_test', 'post_test'])],
            'section_break_duration' => 'nullable|integer|min:0|max:3600',
            'max_attempts' => 'nullable|integer|min:0|max:1000',
            'answer_persistence_mode' => ['nullable', Rule::in(['client_side', 'hybrid_subtest'])],
            'subtest_display_mode' => ['nullable', Rule::in(['per_subtest', 'combined'])],
            'user_card_display' => ['nullable', Rule::in(['icon', 'thumbnail'])],
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $startDate = request()->input('start_date');

                    if (blank($value) || blank($startDate)) {
                        return;
                    }

                    if (\Carbon\Carbon::parse($value)->lte(\Carbon\Carbon::parse($startDate))) {
                        $fail('Tanggal selesai harus setelah tanggal mulai.');
                    }
                },
            ],
            'is_certification' => 'boolean',
            'certificate_template_id' => [
                'nullable',
                Rule::exists('certificate_templates', 'certificate_template_id')
                    ->where('client_profile_id', $this->clientProfileId()),
            ],
            'is_active' => 'boolean',
            'is_toefl' => 'boolean',
            'is_irt' => 'boolean',
            'scoring_method' => ['nullable', Rule::in(['normal', 'irt', 'irt_utbk', 'toefl_itp'])],
            'show_discussion' => 'boolean',
            'lobby_token_enabled' => 'boolean',
            'lobby_token' => 'nullable|string|min:6|max:100',
            'show_leaderboard' => 'boolean',
            'show_passing_grade' => 'boolean',
            'show_result_scores' => 'boolean',
            'result_score_display' => ['nullable', Rule::in(['total_and_subtest', 'subtest_only'])],
            'enable_anti_copy' => 'boolean',
            'enable_tab_switch_detection' => 'boolean',
            'enable_webcam_check' => 'boolean',
            'enable_screen_check' => 'boolean',
            'is_for_sale' => 'boolean',
            'type_price' => ['nullable', Rule::in(['paid', 'free_unconditional', 'free_conditional'])],
            'conditional_requirement' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'access_duration_unit' => ['nullable', Rule::in(PurchaseAccessDuration::UNITS)],
            'access_duration_value' => 'nullable|integer|min:1|max:1200',
        ];

        $durationFields = collect(array_keys($this->durationInputNames()))
            ->merge(
                collect(array_keys(request()->all()))
                    ->filter(fn (string $field): bool => Str::startsWith($field, 'duration_'))
            )
            ->unique();

        foreach ($durationFields as $field) {
            $rules[$field] = 'nullable|numeric|min:0.01|max:300';
        }

        foreach (array_keys(self::UTBK_SUBTESTS) as $slug) {
            $rules['passing_score_'.$slug] = 'nullable|numeric|min:0|max:100';
            $rules['passing_type_'.$slug] = 'nullable|in:score,percentage';
        }

        $passingTypeFields = [
            'twk',
            'tiu',
            'tkp',
            'general',
            'listening',
            'reading',
            'writing',
            'tob',
            'teknis',
            'social_culture',
            'management',
            'interview',
            'word',
            'excel',
            'ppt',
        ];

        foreach ($passingTypeFields as $field) {
            $rules['passing_type_'.$field] = 'nullable|in:score,percentage';
        }

        if (request()->boolean('is_for_sale')) {
            $typePrice = request()->input('type_price', 'paid');

            if ($typePrice === 'paid') {
                $rules['price'] = 'required|numeric|min:1';
            } elseif ($typePrice === 'free_conditional') {
                $rules['conditional_requirement'] = 'required|string|max:1000';
            }
        }

        return $rules;
    }

    private function lobbyTokenData(Request $request, ?Tryout $tryout = null): array
    {
        $enabled = $request->boolean('lobby_token_enabled');
        $token = trim((string) $request->input('lobby_token'));

        if ($enabled && $token === '' && blank($tryout?->lobby_token_hash)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lobby_token' => 'Token lobby wajib diisi saat pengaman token diaktifkan.',
            ]);
        }

        return [
            'lobby_token_enabled' => $enabled,
            'lobby_token_hash' => ! $enabled
                ? null
                : ($token !== '' ? Hash::make($token) : $tryout?->lobby_token_hash),
        ];
    }

    /**
     * Normalisasi durasi dari format Indonesia (mis. 0,5) ke format numerik
     * yang dapat divalidasi dan disimpan MySQL (0.5).
     */
    private function normalizeDurationInputs(Request $request): void
    {
        $normalized = [];

        $durationFields = collect(array_keys($this->durationInputNames()))
            ->merge(
                collect(array_keys($request->all()))
                    ->filter(fn (string $field): bool => Str::startsWith($field, 'duration_'))
            )
            ->unique();

        foreach ($durationFields as $field) {
            $value = $request->input($field);

            if (! is_string($value)) {
                continue;
            }

            $normalized[$field] = str_replace(',', '.', trim($value));
        }

        if ($normalized !== []) {
            $request->merge($normalized);
        }
    }

    private function durationInputNames(): array
    {
        $knownTypes = [
            'general',
            'twk',
            'tiu',
            'tkp',
            'listening',
            'writing',
            'reading',
            'teknis',
            'social_culture',
            'management',
            'interview',
            'word',
            'excel',
            'ppt',
            'word_single',
            'excel_single',
            'ppt_single',
        ];

        return collect($knownTypes)
            ->merge(array_keys(self::UTBK_SUBTESTS))
            ->mapWithKeys(fn (string $type): array => ['duration_'.$type => true])
            ->all();
    }

    private function accessDurationData(Request $request): array
    {
        if (! $request->has('is_for_sale')) {
            return [
                'access_duration_unit' => 'forever',
                'access_duration_value' => null,
            ];
        }

        $unit = PurchaseAccessDuration::normalizedUnit($request->input('access_duration_unit'));

        return [
            'access_duration_unit' => $unit,
            'access_duration_value' => PurchaseAccessDuration::normalizedValue(
                $unit,
                $request->input('access_duration_value')
            ),
        ];
    }

    private function individualSaleData(Request $request): array
    {
        $isForSale = $request->boolean('is_for_sale');
        $typePrice = $isForSale ? $request->input('type_price', 'paid') : 'paid';

        if (! in_array($typePrice, ['paid', 'free_unconditional', 'free_conditional'], true)) {
            $typePrice = 'paid';
        }

        return [
            'is_for_sale' => $isForSale,
            'type_price' => $typePrice,
            'price' => $isForSale && $typePrice === 'paid' ? (int) $request->input('price', 0) : 0,
            'conditional_requirement' => $isForSale && $typePrice === 'free_conditional'
                ? $request->input('conditional_requirement')
                : null,
        ];
    }

    private function normalizedAnswerPersistenceMode(Request $request): string
    {
        if ($request->input('subtest_display_mode', 'per_subtest') === 'combined') {
            return 'client_side';
        }

        return $request->input('answer_persistence_mode', 'client_side');
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

    private function getTryoutTypeOptions(bool $includeUtbk = true, ?string $currentType = null): array
    {
        $allowedCodes = collect(self::LEGACY_TRYOUT_TYPES);

        if (Schema::hasTable('material_categories') && Schema::hasColumn('material_categories', 'code')) {
            $this->ensureMaterialCategoryCodes();

            $allowedCodes = $allowedCodes->merge(
                MaterialCategory::query()
                    ->withCode()
                    ->active()
                    ->pluck('code')
            );
        }

        if (! $includeUtbk) {
            $allowedCodes = $allowedCodes->reject(fn ($code) => $this->isUtbkType($code));
        }

        if ($currentType !== 'utbk_section') {
            $allowedCodes = $allowedCodes->reject(fn ($code) => $code === 'utbk_section');
        }

        if ($currentType) {
            $allowedCodes->push($currentType);
        }

        $canonicalUtbkSubtests = collect(array_values(self::UTBK_SINGLE_TYPES));
        $allowedCodes = $allowedCodes->reject(
            fn ($code) => $canonicalUtbkSubtests->contains($code) && $code !== $currentType
        );

        $codes = $allowedCodes->unique()->values()->all();

        if (! Schema::hasTable('material_categories') || ! Schema::hasColumn('material_categories', 'code')) {
            return $this->fallbackTryoutTypeOptions($codes);
        }

        $categoryCodesByType = collect($codes)
            ->mapWithKeys(fn ($type) => [$type => $this->categoryCodeForTryoutType($type)]);
        $categoriesByCode = MaterialCategory::query()
            ->with([
                'parent',
                'activeChildren' => fn ($query) => $query->withCode()->ordered(),
            ])
            ->withCount('activeChildren')
            ->withCode()
            ->active()
            ->whereIn('code', $categoryCodesByType->values()->unique()->all())
            ->ordered()
            ->get()
            ->keyBy('code');
        $categories = collect($codes)
            ->mapWithKeys(function (string $type) use ($categoryCodesByType, $categoriesByCode) {
                $category = $categoriesByCode->get($categoryCodesByType->get($type));

                if (! $category) {
                    return [];
                }

                return [
                    $type => [
                        'label' => $this->tryoutOptionLabel($type, $category),
                        'category_id' => $category->category_id,
                        'subtests' => $this->isDynamicMultiSubtestCategory($type, $category)
                            ? $category->activeChildren
                                ->map(fn (MaterialCategory $child) => [
                                    'code' => $child->code,
                                    'name' => $child->name,
                                ])
                                ->values()
                                ->all()
                            : [],
                        'group' => $category->parent_id
                            ? 'single'
                            : ($this->isDynamicMultiSubtestCategory($type, $category)
                                ? 'full'
                                : $this->tryoutOptionGroup($type)),
                    ],
                ];
            })
            ->all();

        return $categories ?: $this->fallbackTryoutTypeOptions($codes);
    }

    private function ensureMaterialCategoryCodes(): void
    {
        MaterialCategory::query()
            ->where(function ($query) {
                $query->whereNull('code')
                    ->orWhere('code', '');
            })
            ->orderBy('category_id')
            ->get(['category_id', 'name'])
            ->each(function (MaterialCategory $category) {
                $category->forceFill([
                    'code' => $this->uniqueMaterialCategoryCode($category->name, $category->category_id),
                ])->save();
            });
    }

    private function uniqueMaterialCategoryCode(string $name, ?int $ignoreCategoryId = null): string
    {
        $baseCode = Str::of($name)->slug('_')->lower()->toString() ?: 'kategori';
        $code = $baseCode;
        $suffix = 2;

        while (
            MaterialCategory::query()
                ->where('code', $code)
                ->when($ignoreCategoryId, fn ($query) => $query->whereKeyNot($ignoreCategoryId))
                ->exists()
        ) {
            $code = "{$baseCode}_{$suffix}";
            $suffix++;
        }

        return $code;
    }

    private function fallbackTryoutTypeOptions(array $codes): array
    {
        $labels = [
            'skd_full' => 'SKD Full (TWK + TIU + TKP)',
            'utbk_full' => 'UTBK TPS (Full)',
            'utbk_section' => 'UTBK Section',
            'certification' => 'Certification Full (TOEFL ITP)',
            'pppk_full' => 'PPPK Full',
            'computer' => 'Computer Full (Word + Excel + PPT)',
        ];

        return collect($codes)
            ->mapWithKeys(fn ($code) => [
                $code => [
                    'label' => $labels[$code] ?? $this->subtestLabel($code),
                    'category_id' => null,
                    'group' => $this->tryoutOptionGroup($code),
                ],
            ])
            ->all();
    }

    private function tryoutOptionGroup(string $type): string
    {
        return in_array($type, ['skd_full', 'utbk_full', 'certification', 'pppk_full', 'computer'], true)
            ? 'full'
            : (in_array($type, ['tpa', 'tbi', 'general'], true) ? 'standalone' : 'single');
    }

    private function tryoutOptionLabel(string $type, MaterialCategory $category): string
    {
        if ($category->parent) {
            return $category->display_name;
        }

        if ($this->isDynamicMultiSubtestCategory($type, $category)) {
            return "{$category->name} ({$category->active_children_count} Subtest)";
        }

        $subtestCounts = [
            'skd_full' => 3,
            'utbk_full' => 7,
            'certification' => 3,
            'pppk_full' => 4,
            'computer' => 3,
        ];

        return isset($subtestCounts[$type])
            ? "{$category->name} ({$subtestCounts[$type]} Subtest)"
            : $category->name;
    }

    private function isDynamicMultiSubtestCategory(string $type, MaterialCategory $category): bool
    {
        return ! $category->parent_id
            && (int) ($category->active_children_count ?? 0) > 0;
    }

    private function categoryIdForCode(?string $code): ?int
    {
        if (! $code || ! Schema::hasTable('material_categories') || ! Schema::hasColumn('material_categories', 'code')) {
            return null;
        }

        return MaterialCategory::query()
            ->where('code', $this->categoryCodeForTryoutType($code))
            ->value('category_id');
    }

    private function categoryCodeForTryoutType(string $type): string
    {
        return self::UTBK_SINGLE_TYPES[$type] ?? $type;
    }

    private function allowUtbkControls(?string $currentType = null): bool
    {
        return $this->utbkEnabled()
            || $this->isUtbkType($currentType)
            || $this->hasActiveUtbkCategory();
    }

    private function utbkEnabled(): bool
    {
        return (bool) config('client.branding.utbk_enabled', true);
    }

    private function hasActiveUtbkCategory(): bool
    {
        if (! Schema::hasTable('material_categories') || ! Schema::hasColumn('material_categories', 'code')) {
            return false;
        }

        return MaterialCategory::query()
            ->withCode()
            ->active()
            ->whereIn('code', $this->utbkCategoryCodes())
            ->exists();
    }

    private function utbkCategoryCodes(): array
    {
        return array_values(array_unique(array_merge(
            ['utbk_full', 'utbk_section'],
            array_keys(self::UTBK_SINGLE_TYPES),
            array_values(self::UTBK_SINGLE_TYPES),
        )));
    }

    private function isUtbkType(?string $type): bool
    {
        if (! $type) {
            return false;
        }

        return $type === 'utbk_full'
            || $type === 'utbk_section'
            || array_key_exists($type, self::UTBK_SINGLE_TYPES)
            || in_array($type, array_values(self::UTBK_SINGLE_TYPES), true);
    }

    private function recalculateTryoutPassedStatus(Tryout $tryout): void
    {
        UserAnswer::where('tryout_id', $tryout->tryout_id)
            ->whereIn('status', ['completed', 'pending_release'])
            ->orderBy('user_answer_id')
            ->chunkById(200, function ($answers) {
                foreach ($answers as $answer) {
                    $answer->loadMissing(['tryoutDetail', 'userAnswerDetails.question.questionOptions', 'userAnswerDetails.questionOption']);
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
            ->with(['questionOption', 'question.questionOptions'])
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
                    $totalScore += app(MultipleAnswerScoringService::class)->scoreForDetail($question, $detail);
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

        if (! empty($selectedIds)) {
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
                    $total += app(MultipleAnswerScoringService::class)->config($question)['score_correct'];
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
            return redirect()->back()->with('error', 'Tryout ini tidak menggunakan IRT.');
        }

        $released = $service->releaseForTryout($tryout);

        return redirect()->back()->with(
            $released ? 'success' : 'info',
            $released ? 'Hasil IRT berhasil dirilis.' : 'Tidak ada jawaban pending untuk dirilis.'
        );
    }

    public function resetUtbk(Tryout $tryout, UtbkResultReleaseService $service)
    {
        if (! $tryout->requiresIrtScoring()) {
            return redirect()->back()->with('error', 'Tryout ini tidak menggunakan IRT.');
        }

        $reset = $service->resetResults($tryout);

        return redirect()->back()->with(
            $reset ? 'success' : 'info',
            $reset ? 'Skor IRT berhasil di-reset. Silakan rilis ulang.' : 'Tidak ada skor IRT yang bisa di-reset.'
        );
    }
}
