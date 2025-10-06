<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TryoutController extends Controller
{
    public function index()
    {
        $tryouts = Tryout::with(['tryoutDetails.questions'])
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

    public function create()
    {
        $packages = Package::all();
        return view('admin.pages.tryout.create', compact('packages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type_tryout' => 'required|in:tiu,twk,tkp,skd_full,general,certification,listening,reading,writing,pppk_full,teknis,social culture,management,interview,word,excel,ppt,computer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_certification' => 'boolean',
            'is_active' => 'boolean',
            'is_toefl' => 'boolean',
        ]);

        try {
            $tryout = Tryout::create([
                'name' => $request->name,
                'description' => $request->description,
                'type_tryout' => $request->type_tryout,
                'is_certification' => $request->has('is_certification'),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->has('is_active'),
                'is_toefl' => $request->has('is_toefl')
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
            return view('admin.pages.tryout.create', compact('tryout'));
        } catch (\Exception $e) {
            return redirect()->route('admin.tryout.index')
                ->with('error', 'Tryout tidak ditemukan');
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type_tryout' => 'required|in:tiu,twk,tkp,skd_full,general,certification,listening,reading,writing,pppk_full,teknis,social culture,management,interview,word,excel,ppt,computer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_certification' => 'boolean',
            'is_active' => 'boolean',
            'is_toefl' => 'boolean',
        ]);

        try {
            $tryout = Tryout::with('tryoutDetails')->findOrFail($id);

            $originalType = $tryout->type_tryout;

            // Update master tryout fields
            $tryout->update([
                'name' => $request->name,
                'description' => $request->description,
                'type_tryout' => $request->type_tryout,
                'is_certification' => $request->has('is_certification'),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->has('is_active'),
                'is_toefl' => $request->has('is_toefl')
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
        }
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
            'pengetahuan_umum'  => 'Pengetahuan Umum',
        ];

        // Fallback: bikin judul yang oke kalau kodenya belum dipetakan
        return $map[$key] ?? Str::headline((string) $type);
    }
}
