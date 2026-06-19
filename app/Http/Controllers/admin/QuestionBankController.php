<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionBankQuestion;
use App\Models\QuestionBankQuestionOption;
use App\Models\QuestionOption;
use App\Models\TryoutDetail;
use App\Services\AiQuestionGeneratorService;
use App\Services\PlanQuotaService;
use App\Services\QuestionPptImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class QuestionBankController extends Controller
{
    public function index(Request $request)
    {
        $importTarget = $request->integer('import_for');
        $tryoutDetail = $importTarget
            ? TryoutDetail::with('tryout')->find($importTarget)
            : null;
        $bankSort = $request->input('sort', 'newest');
        $bankSortDirection = $bankSort === 'oldest' ? 'asc' : 'desc';

        $rootBanks = QuestionBank::withCount('questions')
            ->with(['children' => function ($query) use ($bankSortDirection) {
                $query->withCount('questions')->orderBy('created_at', $bankSortDirection);
            }])
            ->whereNull('parent_id')
            ->orderBy('created_at', $bankSortDirection)
            ->get();
        $recursiveQuestionCounts = $this->buildRecursiveQuestionCounts(
            QuestionBank::withCount('questions')->get(['id', 'parent_id'])
        );

        $stats = [
            'total_banks' => QuestionBank::count(),
            'total_questions' => QuestionBankQuestion::count(),
            'child_banks' => QuestionBank::whereNotNull('parent_id')->count(),
        ];

        $bankOptions = QuestionBank::orderBy('name')->get();

        return view('admin.pages.question-bank.index', compact(
            'rootBanks',
            'stats',
            'bankOptions',
            'tryoutDetail',
            'importTarget',
            'bankSort',
            'recursiveQuestionCounts'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:question_banks,id'],
        ]);

        QuestionBank::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Bank soal berhasil disimpan.');
    }

    public function update(Request $request, QuestionBank $questionBank)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $questionBank->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Bank soal berhasil diperbarui.');
    }

    public function destroy(QuestionBank $questionBank)
    {
        // Delete all questions in this bank and sub-banks recursively
        $this->deleteBankRecursively($questionBank);

        return redirect()->route('admin.question-bank.index')
            ->with('success', 'Bank soal berhasil dihapus.');
    }

    private function deleteBankRecursively(QuestionBank $bank): void
    {
        // Delete all questions in sub-banks first
        foreach ($bank->children as $child) {
            $this->deleteBankRecursively($child);
        }

        // Delete all questions in this bank (cascade will handle options if configured)
        $bank->questions()->delete();

        // Delete the bank
        $bank->delete();
    }

    public function show(QuestionBank $questionBank, Request $request)
    {
        $pptImportPreview = null;
        $pptPreviewToken = session('ppt_import_preview_token') ?: $request->query('ppt_preview_token');
        if ($pptPreviewToken && is_string($pptPreviewToken) && preg_match('/^[A-Za-z0-9]{40}$/', $pptPreviewToken)) {
            $previewPath = "question-bank/ppt-previews/{$pptPreviewToken}.json";
            if (Storage::exists($previewPath)) {
                $pptImportPreview = json_decode(Storage::get($previewPath), true);
                Storage::delete($previewPath);
            }
        }

        $importTarget = $request->integer('import_for');
        $tryoutDetail = $importTarget
            ? TryoutDetail::with('tryout')->find($importTarget)
            : null;
        $questionSort = $request->input('sort', 'newest');
        $questionSortDirection = $questionSort === 'oldest' ? 'asc' : 'desc';
        $questionType = $request->input('question_type', 'all');
        $questionSearch = trim((string) $request->input('search', ''));
        $perPage = in_array($request->integer('per_page'), [5, 10, 15, 25], true)
            ? $request->integer('per_page')
            : 5;

        $questionBank->load(['children' => function ($query) use ($questionSortDirection) {
            $query->withCount('questions')->orderBy('created_at', $questionSortDirection);
        }]);
        $recursiveQuestionCounts = $this->buildRecursiveQuestionCounts(
            QuestionBank::withCount('questions')->get(['id', 'parent_id'])
        );
        $bankTotalQuestions = $recursiveQuestionCounts[$questionBank->id] ?? 0;

        $questionTypeOptions = $questionBank->questions()
            ->select('question_type')
            ->whereNotNull('question_type')
            ->distinct()
            ->orderBy('question_type')
            ->pluck('question_type');

        $questionsQuery = $questionBank->questions()
            ->with('options');

        if ($questionType !== 'all') {
            $questionsQuery->where('question_type', $questionType);
        }

        if ($questionSearch !== '') {
            $questionsQuery->where(function ($query) use ($questionSearch) {
                $query->where('question_text', 'like', "%{$questionSearch}%")
                    ->orWhere('explanation', 'like', "%{$questionSearch}%")
                    ->orWhere('question_type', 'like', "%{$questionSearch}%")
                    ->orWhereHas('options', function ($optionQuery) use ($questionSearch) {
                        $optionQuery->where('option_text', 'like', "%{$questionSearch}%");
                    });
            });
        }

        $questions = $questionsQuery
            ->orderBy('created_at', $questionSortDirection)
            ->paginate($perPage);

        $breadcrumbs = $this->buildBreadcrumbs($questionBank);
        $bankOptions = $this->buildBankOptions();

        return view('admin.pages.question-bank.show', [
            'bank' => $questionBank,
            'questions' => $questions,
            'breadcrumbs' => $breadcrumbs,
            'tryoutDetail' => $tryoutDetail,
            'importTarget' => $importTarget,
            'questionSort' => $questionSort,
            'questionType' => $questionType,
            'questionSearch' => $questionSearch,
            'perPage' => $perPage,
            'questionTypeOptions' => $questionTypeOptions,
            'bankOptions' => $bankOptions,
            'pptImportPreview' => $pptImportPreview,
            'recursiveQuestionCounts' => $recursiveQuestionCounts,
            'bankTotalQuestions' => $bankTotalQuestions,
        ]);
    }

    public function aiGeneratorForm(QuestionBank $questionBank, Request $request, AiQuestionGeneratorService $aiGeneratorService)
    {
        abort_unless($aiGeneratorService->isEnabled(), 404);

        $importTarget = $request->integer('import_for');
        $tryoutDetail = $importTarget
            ? TryoutDetail::with('tryout')->find($importTarget)
            : null;
        $breadcrumbs = $this->buildBreadcrumbs($questionBank);
        $models = $aiGeneratorService->availableModels();
        abort_if(empty($models), 404);

        $defaultModel = $aiGeneratorService->defaultModel();
        $preview = session($this->aiPreviewSessionKey($questionBank));

        return view('admin.pages.question-bank.ai-generator', [
            'bank' => $questionBank,
            'breadcrumbs' => $breadcrumbs,
            'tryoutDetail' => $tryoutDetail,
            'importTarget' => $importTarget,
            'models' => $models,
            'defaultModel' => $defaultModel,
            'preview' => $preview,
        ]);
    }

    public function previewAiQuestions(Request $request, QuestionBank $questionBank, AiQuestionGeneratorService $aiGeneratorService)
    {
        abort_unless($aiGeneratorService->isEnabled(), 404);

        $models = array_keys($aiGeneratorService->availableModels());
        abort_if(empty($models), 404);

        $validated = $request->validate([
            'model' => ['required', Rule::in($models)],
            'subject' => ['required', 'string', 'max:120'],
            'topic' => ['required', 'string', 'max:180'],
            'difficulty' => ['required', Rule::in(['mudah', 'sedang', 'sulit', 'campuran'])],
            'question_count' => ['required', 'integer', 'min:1', 'max:25'],
            'option_count' => ['required', 'integer', 'min:2', 'max:5'],
            'explanation_style' => ['required', Rule::in(['singkat', 'normal', 'detail'])],
            'instruction' => ['nullable', 'string', 'max:1500'],
            'import_for' => ['nullable', 'integer', 'exists:tryout_details,tryout_detail_id'],
        ], [], [
            'subject' => 'mata pelajaran/kategori',
            'topic' => 'topik',
            'question_count' => 'jumlah soal',
            'option_count' => 'jumlah opsi',
            'explanation_style' => 'gaya pembahasan',
            'instruction' => 'instruksi tambahan',
        ]);

        try {
            $preview = $aiGeneratorService->generate($validated);
            $preview['request'] = $validated;

            session()->put($this->aiPreviewSessionKey($questionBank), $preview);

            return redirect()
                ->route('admin.question-bank.questions.ai-generator', [
                    'questionBank' => $questionBank->id,
                    'import_for' => $request->integer('import_for') ?: null,
                ])
                ->with('success', count($preview['questions']) . ' soal berhasil dibuat sebagai preview. Review dulu sebelum disimpan.');
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }
    }

    public function storeAiQuestions(Request $request, QuestionBank $questionBank, AiQuestionGeneratorService $aiGeneratorService)
    {
        abort_unless($aiGeneratorService->isEnabled(), 404);

        $models = array_keys($aiGeneratorService->availableModels());
        abort_if(empty($models), 404);

        $validated = $request->validate([
            'questions_json' => ['required', 'string'],
            'model' => ['required', Rule::in($models)],
            'import_for' => ['nullable', 'integer', 'exists:tryout_details,tryout_detail_id'],
        ]);

        $questions = json_decode($validated['questions_json'], true);
        if (!is_array($questions)) {
            return back()->with('error', 'Data preview AI tidak valid. Silakan generate ulang.');
        }

        $questions = $this->normalizeAiPreviewQuestions($questions);
        if (empty($questions)) {
            return back()->with('error', 'Tidak ada soal valid untuk disimpan.');
        }

        $storedCount = 0;
        DB::transaction(function () use ($questionBank, $questions, $validated, &$storedCount) {
            foreach ($questions as $question) {
                $bankQuestion = QuestionBankQuestion::create([
                    'question_bank_id' => $questionBank->id,
                    'question_type' => 'multiple_choice',
                    'question_text' => $question['question_text'],
                    'explanation' => $question['explanation'] ?: null,
                    'default_weight' => 1,
                    'custom_score' => 'no',
                    'metadata' => [
                        'source' => 'ai_generator',
                        'model' => $validated['model'],
                        'generated_at' => now()->toDateTimeString(),
                    ],
                    'created_by' => Auth::id(),
                ]);

                foreach ($question['options'] as $position => $option) {
                    QuestionBankQuestionOption::create([
                        'question_bank_question_id' => $bankQuestion->id,
                        'option_text' => $option['text'],
                        'weight' => $option['label'] === $question['correct_option'] ? 1 : 0,
                        'is_correct' => $option['label'] === $question['correct_option'],
                        'position' => $position + 1,
                    ]);
                }

                $storedCount++;
            }
        });

        session()->forget($this->aiPreviewSessionKey($questionBank));

        return redirect()
            ->route('admin.question-bank.show', [
                'questionBank' => $questionBank->id,
                'import_for' => $request->integer('import_for') ?: null,
            ])
            ->with('success', "{$storedCount} soal AI berhasil disimpan ke {$questionBank->name}.");
    }

    public function downloadImportTemplate(QuestionBank $questionBank)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'A1' => 'question_text',
            'B1' => 'question_type',
            'C1' => 'option_a_text',
            'D1' => 'option_a_correct',
            'E1' => 'option_a_weight',
            'F1' => 'option_b_text',
            'G1' => 'option_b_correct',
            'H1' => 'option_b_weight',
            'I1' => 'option_c_text',
            'J1' => 'option_c_correct',
            'K1' => 'option_c_weight',
            'L1' => 'option_d_text',
            'M1' => 'option_d_correct',
            'N1' => 'option_d_weight',
            'O1' => 'option_e_text',
            'P1' => 'option_e_correct',
            'Q1' => 'option_e_weight',
            'R1' => 'explanation',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        $instructions = [
            'A2' => 'Tulis pertanyaan di sini',
            'B2' => 'multiple_choice / essay',
            'C2' => 'Teks pilihan A',
            'D2' => '1 (jika benar) / 0 (jika salah)',
            'E2' => 'Bobot nilai (1-5 untuk TKP)',
            'F2' => 'Teks pilihan B',
            'G2' => '1 (jika benar) / 0 (jika salah)',
            'H2' => 'Bobot nilai (1-5 untuk TKP)',
            'I2' => 'Teks pilihan C',
            'J2' => '1 (jika benar) / 0 (jika salah)',
            'K2' => 'Bobot nilai (1-5 untuk TKP)',
            'L2' => 'Teks pilihan D',
            'M2' => '1 (jika benar) / 0 (jika salah)',
            'N2' => 'Bobot nilai (1-5 untuk TKP)',
            'O2' => 'Teks pilihan E',
            'P2' => '1 (jika benar) / 0 (jika salah)',
            'Q2' => 'Bobot nilai (1-5 untuk TKP)',
            'R2' => 'Penjelasan jawaban (opsional)',
        ];

        foreach ($instructions as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setItalic(true);
            $sheet->getStyle($cell)->getFill()->getStartColor()->setARGB('FFE6E6E6');
        }

        $sampleData = [
            'A3' => 'Siapa presiden pertama Indonesia?',
            'B3' => 'multiple_choice',
            'C3' => 'Ir. Soekarno',
            'D3' => '1',
            'E3' => '5',
            'F3' => 'Mohammad Hatta',
            'G3' => '0',
            'H3' => '1',
            'I3' => 'Soeharto',
            'J3' => '0',
            'K3' => '1',
            'L3' => 'B.J. Habibie',
            'M3' => '0',
            'N3' => '1',
            'O3' => 'Megawati',
            'P3' => '0',
            'Q3' => '1',
            'R3' => 'Ir. Soekarno adalah presiden pertama Republik Indonesia yang memproklamasikan kemerdekaan pada 17 Agustus 1945',
        ];

        foreach ($sampleData as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $notesSheet = $spreadsheet->createSheet();
        $notesSheet->setTitle('Petunjuk');

        $notes = [
            'A1' => 'PETUNJUK PENGGUNAAN TEMPLATE IMPORT SOAL',
            'A3' => '1. question_text: Tulis soal lengkap dengan konteks',
            'A4' => '2. question_type: Pilih "multiple_choice" atau "essay"',
            'A5' => '3. option_x_text: Isi dengan teks pilihan jawaban',
            'A6' => '4. option_x_correct: Isi dengan 1 jika benar, 0 jika salah',
            'A7' => '5. option_x_weight: Untuk TKP isi bobot 1-5, untuk lainnya isi 1',
            'A8' => '6. explanation: Isi dengan penjelasan jawaban (opsional)',
            'A10' => 'CATATAN PENTING:',
            'A11' => '- Pastikan hanya ada 1 jawaban benar per soal (kecuali TKP)',
            'A12' => '- Untuk TKP, semua pilihan bisa memiliki bobot berbeda',
            'A13' => '- Jangan ubah format header (baris 1)',
            'A14' => '- Hapus baris instruksi (baris 2) sebelum import',
            'A15' => '- Maksimal 100 soal per file',
        ];

        foreach ($notes as $cell => $value) {
            $notesSheet->setCellValue($cell, $value);
            if ($cell === 'A1' || $cell === 'A10') {
                $notesSheet->getStyle($cell)->getFont()->setBold(true)->setSize(14);
            }
        }

        $notesSheet->getColumnDimension('A')->setWidth(80);
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'template_bank_soal_' . str($questionBank->name)->slug('_') . '_' . date('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'bank_soal_template_');

        (new Xlsx($spreadsheet))->save($tempFile);

        return Response::download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    public function importQuestions(Request $request, QuestionBank $questionBank)
    {
        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
            'import_for' => ['nullable', 'integer', 'exists:tryout_details,tryout_detail_id'],
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('excel_file')->getPathname());
            $data = $spreadsheet->getActiveSheet()->toArray();

            array_shift($data);

            $rowOffset = 2;
            if (isset($data[0][0]) && str_contains((string) $data[0][0], 'Tulis pertanyaan')) {
                array_shift($data);
                $rowOffset = 3;
            }

            $importedCount = 0;
            $processedCount = 0;
            $errors = [];

            foreach ($data as $index => $row) {
                $rowNumber = $index + $rowOffset;
                $questionText = trim((string) ($row[0] ?? ''));

                if ($questionText === '') {
                    continue;
                }

                $processedCount++;
                if ($processedCount > 100) {
                    $errors[] = 'Maksimal 100 soal per file. Baris setelah 100 soal dilewati.';
                    break;
                }

                $questionType = strtolower(trim((string) ($row[1] ?? '')));
                if (!in_array($questionType, ['multiple_choice', 'essay'], true)) {
                    $errors[] = "Baris {$rowNumber}: Tipe soal harus multiple_choice atau essay";
                    continue;
                }

                try {
                    DB::transaction(function () use ($questionBank, $questionText, $questionType, $row, &$importedCount, $rowNumber, &$errors) {
                        $options = $this->buildBankImportOptions($row);

                        if ($questionType === 'multiple_choice') {
                            if (count($options) < 2) {
                                $errors[] = "Baris {$rowNumber}: Minimal isi 2 pilihan jawaban";
                                return;
                            }

                            if (!collect($options)->contains('is_correct', true)) {
                                $errors[] = "Baris {$rowNumber}: Harus ada minimal 1 jawaban benar";
                                return;
                            }
                        }

                        $maxWeight = collect($options)->max('weight');
                        $hasCustomScores = collect($options)->contains(fn ($option) => (float) $option['weight'] > 1);

                        $bankQuestion = QuestionBankQuestion::create([
                            'question_bank_id' => $questionBank->id,
                            'question_type' => $questionType,
                            'question_text' => $questionText,
                            'explanation' => filled($row[17] ?? null) ? trim((string) $row[17]) : null,
                            'default_weight' => $maxWeight ?: 1,
                            'custom_score' => $hasCustomScores ? 'yes' : 'no',
                            'metadata' => null,
                            'created_by' => Auth::id(),
                        ]);

                        if ($questionType === 'multiple_choice') {
                            foreach ($options as $index => $option) {
                                QuestionBankQuestionOption::create([
                                    'question_bank_question_id' => $bankQuestion->id,
                                    'option_text' => $option['text'],
                                    'weight' => $option['weight'],
                                    'is_correct' => $option['is_correct'],
                                    'position' => $index + 1,
                                ]);
                            }
                        }

                        $importedCount++;
                    });
                } catch (\Exception $e) {
                    $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                }
            }

            $message = "Berhasil import {$importedCount} soal ke {$questionBank->name}";
            if (!empty($errors)) {
                $message .= '. Error: ' . implode(', ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= ' dan ' . (count($errors) - 3) . ' error lainnya';
                }
            }

            return redirect()
                ->route('admin.question-bank.show', [
                    'questionBank' => $questionBank->id,
                    'import_for' => $request->integer('import_for') ?: null,
                ])
                ->with($importedCount > 0 ? 'success' : 'error', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal import file: ' . $e->getMessage());
        }
    }

    public function previewPptQuestions(Request $request, QuestionBank $questionBank, QuestionPptImportService $pptImportService)
    {
        $request->validate([
            'ppt_files' => ['required', 'array', 'min:1', 'max:10'],
            'ppt_files.*' => ['required', 'file', 'mimes:pptx', 'max:10240'],
            'import_for' => ['nullable', 'integer', 'exists:tryout_details,tryout_detail_id'],
        ]);

        try {
            $groups = [];
            $allErrors = [];

            foreach ($request->file('ppt_files', []) as $file) {
                $result = $pptImportService->parse($file);
                $questions = $result['questions'];
                $fileName = $file->getClientOriginalName();

                if (empty($questions)) {
                    $allErrors[] = "{$fileName}: tidak ada soal yang terbaca.";
                    continue;
                }

                $groups[] = [
                    'file_name' => $fileName,
                    'target_bank_id' => $questionBank->id,
                    'questions' => $questions,
                    'errors' => $result['errors'],
                    'total_slides' => $result['total_slides'],
                ];
            }

            if (empty($groups)) {
                $message = 'Tidak ada soal yang terbaca dari PPT. Pastikan file berisi teks, bukan gambar/scan.';
                if (!empty($allErrors)) {
                    $message .= ' ' . implode(' ', array_slice($allErrors, 0, 3));
                }

                return back()->with('error', $message);
            }

            $previewToken = $this->storePptPreviewPayload([
                'groups' => $groups,
                'errors' => $allErrors,
                'total_files' => count($groups),
            ]);

            return redirect()
                ->route('admin.question-bank.show', [
                    'questionBank' => $questionBank->id,
                    'import_for' => $request->integer('import_for') ?: null,
                    'ppt_preview_token' => $previewToken,
                ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membaca PPT: ' . $e->getMessage());
        }
    }

    public function storePptQuestions(Request $request, QuestionBank $questionBank)
    {
        $validated = $request->validate([
            'groups_json' => ['required', 'string'],
            'import_for' => ['nullable', 'integer', 'exists:tryout_details,tryout_detail_id'],
        ]);

        $groups = json_decode($validated['groups_json'], true);
        if (!is_array($groups)) {
            return back()->with('error', 'Data preview PPT tidak valid. Silakan upload ulang file PPT.');
        }

        $importedCount = 0;
        $errors = [];
        $targetBankIds = collect($groups)
            ->pluck('target_bank_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $targetBanks = QuestionBank::whereIn('id', $targetBankIds)
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($questionBank, $groups, $targetBanks, &$importedCount, &$errors) {
            foreach ($groups as $groupIndex => $group) {
                $fileName = trim((string) ($group['file_name'] ?? 'File ' . ($groupIndex + 1)));
                $targetBankId = (int) ($group['target_bank_id'] ?? $questionBank->id);
                $targetBank = $targetBanks->get($targetBankId) ?? $questionBank;
                $questions = is_array($group['questions'] ?? null) ? $group['questions'] : [];

                foreach (array_slice($questions, 0, 100) as $index => $question) {
                    $rowNumber = $index + 1;
                    $questionText = trim((string) ($question['question_text'] ?? ''));
                    $explanation = trim((string) ($question['explanation'] ?? ''));
                    $correctAnswer = strtoupper(trim((string) ($question['correct_answer'] ?? '')));
                    $optionsInput = is_array($question['options'] ?? null) ? $question['options'] : [];

                    if ($questionText === '') {
                        $errors[] = "{$fileName} soal {$rowNumber}: Teks soal wajib diisi.";
                        continue;
                    }

                    $options = [];
                    foreach (range('A', 'E') as $letter) {
                        $optionValue = $optionsInput[$letter] ?? null;
                        $option = is_array($optionValue) ? $optionValue : [];
                        $optionText = is_array($optionValue)
                            ? trim((string) ($option['text'] ?? ''))
                            : trim((string) $optionValue);

                        if ($optionText === '') {
                            continue;
                        }

                        $weight = $this->normalizeImportScore(
                            $option['weight'] ?? null,
                            $letter === $correctAnswer ? 1 : 0
                        );
                        $options[] = [
                            'letter' => $letter,
                            'text' => $optionText,
                            'weight' => $weight,
                            'is_correct' => $letter === $correctAnswer,
                        ];
                    }

                    if (count($options) < 2) {
                        $errors[] = "{$fileName} soal {$rowNumber}: Minimal isi 2 pilihan jawaban.";
                        continue;
                    }

                    if (!$correctAnswer || !collect($options)->contains('letter', $correctAnswer)) {
                        $errors[] = "{$fileName} soal {$rowNumber}: Jawaban benar wajib dipilih dan harus sesuai opsi.";
                        continue;
                    }

                    $maxWeight = collect($options)->max('weight') ?: 1;
                    $hasCustomScores = collect($options)->contains(fn ($option) => (float) $option['weight'] > 1);

                    $bankQuestion = QuestionBankQuestion::create([
                        'question_bank_id' => $targetBank->id,
                        'question_type' => 'multiple_choice',
                        'question_text' => $questionText,
                        'explanation' => $explanation !== '' ? $explanation : null,
                        'default_weight' => $maxWeight,
                        'custom_score' => $hasCustomScores ? 'yes' : 'no',
                        'metadata' => [
                            'source' => 'ppt_import',
                            'source_file' => $fileName,
                            'slide' => $question['slide'] ?? null,
                            'number' => $question['number'] ?? null,
                        ],
                        'created_by' => Auth::id(),
                    ]);

                    foreach ($options as $position => $option) {
                        QuestionBankQuestionOption::create([
                            'question_bank_question_id' => $bankQuestion->id,
                            'option_text' => $option['text'],
                            'weight' => $option['weight'],
                            'is_correct' => $option['is_correct'],
                            'position' => $position + 1,
                        ]);
                    }

                    $importedCount++;
                }
            }
        });

        $message = "Berhasil import {$importedCount} soal dari PPT ke {$questionBank->name}.";
        if (!empty($errors)) {
            $message .= ' Catatan: ' . implode(', ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= ' dan ' . (count($errors) - 3) . ' catatan lainnya';
            }
        }

        return redirect()
            ->route('admin.question-bank.show', [
                'questionBank' => $questionBank->id,
                'import_for' => $request->integer('import_for') ?: null,
            ])
            ->with($importedCount > 0 ? 'success' : 'error', $message);
    }

    private function storePptPreviewPayload(array $payload): string
    {
        $token = Str::random(40);
        $directory = 'question-bank/ppt-previews';

        Storage::makeDirectory($directory);
        Storage::put("{$directory}/{$token}.json", json_encode($payload, JSON_UNESCAPED_UNICODE));

        return $token;
    }

    public function createQuestionForm(Request $request, QuestionBank $questionBank)
    {
        // Cek quota question bank - backend validation
        $quotaCheck = PlanQuotaService::canCreateQuestionBank();
        if (!$quotaCheck['allowed']) {
            return redirect()->route('admin.question-bank.show', $questionBank)
                ->with('error', $quotaCheck['reason']);
        }

        $importTarget = $request->integer('import_for');
        $matchingPairs = old('matching_pairs', [
            ['left' => '', 'right' => ''],
            ['left' => '', 'right' => ''],
        ]);

        if (is_array($matchingPairs) && count($matchingPairs) < 2) {
            $matchingPairs = array_pad($matchingPairs, 2, ['left' => '', 'right' => '']);
        }

        return view('admin.pages.question-bank.create-question', [
            'bank' => $questionBank,
            'importTarget' => $importTarget,
            'matchingPairs' => $matchingPairs,
        ]);
    }

    public function editQuestionForm(Request $request, QuestionBankQuestion $question)
    {
        $importTarget = $request->integer('import_for');
        $question->load('options', 'bank');

        $metadata = is_array($question->metadata) ? $question->metadata : [];
        $matchingPairs = $metadata['matching_pairs'] ?? [
            ['left' => '', 'right' => ''],
            ['left' => '', 'right' => ''],
        ];

        if (is_array($matchingPairs) && count($matchingPairs) < 2) {
            $matchingPairs = array_pad($matchingPairs, 2, ['left' => '', 'right' => '']);
        }

        return view('admin.pages.question-bank.edit-question', [
            'bank' => $question->bank,
            'question' => $question,
            'importTarget' => $importTarget,
            'matchingPairs' => $matchingPairs,
        ]);
    }

    public function storeQuestion(Request $request, QuestionBank $questionBank)
    {
        // Cek quota question bank - backend validation (hindari bypass)
        $quotaCheck = PlanQuotaService::canCreateQuestionBank();
        if (!$quotaCheck['allowed']) {
            return redirect()->route('admin.question-bank.show', $questionBank)
                ->with('error', $quotaCheck['reason']);
        }

        $questionType = $request->input('question_type', 'multiple_choice');
        $importTarget = $request->integer('import_for');

        $baseRules = [
            'question_type' => ['required', 'in:multiple_choice,multiple_answer,multiple_true_false,true_false,matching,essay,short_answer,audio'],
            'question_text' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'default_weight' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'custom_score' => ['nullable', 'boolean'],
            'multiple_answer_score_correct' => ['nullable', 'numeric'],
            'multiple_answer_score_wrong' => ['nullable', 'numeric'],
            'multiple_answer_scoring_mode' => ['nullable', 'in:fullscore,partial'],
            'sound' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/m4a,audio/x-m4a', 'max:5120'],
        ];

        switch ($questionType) {
            case 'multiple_choice':
                $this->validateMultipleChoice($request);
                break;
            case 'multiple_answer':
                $this->validateMultipleChoice($request, true);
                break;
            case 'multiple_true_false':
                $this->validateMultipleTrueFalse($request);
                break;
            case 'true_false':
                $this->validateTrueFalse($request);
                break;
            case 'matching':
                $this->validateMatching($request);
                break;
            case 'essay':
            case 'short_answer':
                $this->validateShortAnswer($request);
                break;
            case 'audio':
                $this->validateAudio($request);
                break;
        }

        $validated = $request->validate($baseRules);

        $metadata = $this->buildMetadata($request, $questionType);

        $soundPath = null;
        if ($request->hasFile('sound')) {
            $soundPath = $request->file('sound')->store('question-bank/audio', 'public');
        }

        DB::transaction(function () use ($questionBank, $validated, $metadata, $questionType, $request, $soundPath) {
            $correctAnswersCount = $questionType === 'multiple_answer'
                ? max(1, count((array) $request->input('correct_answers', [])))
                : 0;
            $scoreCorrect = (float) $request->input('multiple_answer_score_correct', 1);
            $matchingScoreCorrect = (float) ($metadata['matching_scores']['score_correct'] ?? 1);
            $mtfScoreCorrect = (float) ($metadata['multiple_true_false']['score_correct'] ?? 1);
            $resolvedWeight = $questionType === 'multiple_answer'
                ? max(0, $scoreCorrect) * $correctAnswersCount
                : ($questionType === 'matching'
                    ? max(0, $matchingScoreCorrect)
                    : ($questionType === 'multiple_true_false'
                        ? max(0, $mtfScoreCorrect)
                        : ($validated['default_weight'] ?? 1)));

            $bankQuestion = QuestionBankQuestion::create([
                'question_bank_id' => $questionBank->id,
                'question_type' => $questionType,
                'question_text' => $validated['question_text'],
                'explanation' => $validated['explanation'] ?? null,
                'default_weight' => $resolvedWeight,
                'custom_score' => in_array($questionType, ['multiple_answer', 'multiple_true_false'], true) ? 'yes' : ($request->boolean('use_custom_scores') ? 'yes' : 'no'),
                'metadata' => $metadata ?: null,
                'sound' => $soundPath,
                'created_by' => Auth::id(),
            ]);

            if (in_array($questionType, ['multiple_choice', 'multiple_answer', 'true_false'])) {
                $options = $this->prepareOptions($request, $questionType);
                foreach ($options as $index => $option) {
                    QuestionBankQuestionOption::create([
                        'question_bank_question_id' => $bankQuestion->id,
                        'option_text' => $option['text'],
                        'weight' => $option['weight'],
                        'is_correct' => $option['is_correct'],
                        'position' => $index + 1,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.question-bank.show', ['questionBank' => $questionBank->id, 'import_for' => $importTarget])
            ->with('success', 'Soal berhasil ditambahkan ke bank.');
    }

    public function updateQuestion(Request $request, QuestionBankQuestion $question)
    {
        $questionType = $request->input('question_type', 'multiple_choice');
        $importTarget = $request->integer('import_for');

        $baseRules = [
            'question_type' => ['required', 'in:multiple_choice,multiple_answer,multiple_true_false,true_false,matching,essay,short_answer,audio'],
            'question_text' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'default_weight' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'custom_score' => ['nullable', 'boolean'],
            'multiple_answer_score_correct' => ['nullable', 'numeric'],
            'multiple_answer_score_wrong' => ['nullable', 'numeric'],
            'multiple_answer_scoring_mode' => ['nullable', 'in:fullscore,partial'],
            'sound' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/m4a,audio/x-m4a', 'max:5120'],
        ];

        switch ($questionType) {
            case 'multiple_choice':
                $this->validateMultipleChoice($request);
                break;
            case 'multiple_answer':
                $this->validateMultipleChoice($request, true);
                break;
            case 'multiple_true_false':
                $this->validateMultipleTrueFalse($request);
                break;
            case 'true_false':
                $this->validateTrueFalse($request);
                break;
            case 'matching':
                $this->validateMatching($request);
                break;
            case 'essay':
            case 'short_answer':
                $this->validateShortAnswer($request);
                break;
            case 'audio':
                $this->validateAudio($request);
                break;
        }

        $validated = $request->validate($baseRules);
        $metadata = $this->buildMetadata($request, $questionType);

        $soundPath = $question->sound;
        if ($request->hasFile('sound')) {
            if ($soundPath) {
                Storage::disk('public')->delete($soundPath);
            }
            $soundPath = $request->file('sound')->store('question-bank/audio', 'public');
        }

        DB::transaction(function () use ($question, $validated, $metadata, $questionType, $request, $soundPath) {
            $correctAnswersCount = $questionType === 'multiple_answer'
                ? max(1, count((array) $request->input('correct_answers', [])))
                : 0;
            $scoreCorrect = (float) $request->input('multiple_answer_score_correct', 1);
            $matchingScoreCorrect = (float) ($metadata['matching_scores']['score_correct'] ?? 1);
            $mtfScoreCorrect = (float) ($metadata['multiple_true_false']['score_correct'] ?? 1);
            $resolvedWeight = $questionType === 'multiple_answer'
                ? max(0, $scoreCorrect) * $correctAnswersCount
                : ($questionType === 'matching'
                    ? max(0, $matchingScoreCorrect)
                    : ($questionType === 'multiple_true_false'
                        ? max(0, $mtfScoreCorrect)
                        : ($validated['default_weight'] ?? 1)));

            $question->update([
                'question_type' => $questionType,
                'question_text' => $validated['question_text'],
                'explanation' => $validated['explanation'] ?? null,
                'default_weight' => $resolvedWeight,
                'custom_score' => in_array($questionType, ['multiple_answer', 'multiple_true_false'], true) ? 'yes' : ($request->boolean('use_custom_scores') ? 'yes' : 'no'),
                'metadata' => $metadata ?: null,
                'sound' => $soundPath,
            ]);

            $question->options()->delete();

            if (in_array($questionType, ['multiple_choice', 'multiple_answer', 'true_false'])) {
                $options = $this->prepareOptions($request, $questionType);
                foreach ($options as $index => $option) {
                    QuestionBankQuestionOption::create([
                        'question_bank_question_id' => $question->id,
                        'option_text' => $option['text'],
                        'weight' => $option['weight'],
                        'is_correct' => $option['is_correct'],
                        'position' => $index + 1,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.question-bank.show', ['questionBank' => $question->question_bank_id, 'import_for' => $importTarget])
            ->with('success', 'Soal berhasil diperbarui.');
    }

    public function cloneToTryout(Request $request, QuestionBankQuestion $question)
    {
        $validated = $request->validate([
            'tryout_detail_id' => ['required', 'exists:tryout_details,tryout_detail_id'],
        ]);

        $tryoutDetail = TryoutDetail::findOrFail($validated['tryout_detail_id']);

        DB::transaction(function () use ($question, $tryoutDetail) {
            $newQuestion = Question::create([
                'tryout_detail_id' => $tryoutDetail->tryout_detail_id,
                'question_type' => $question->question_type,
                'question_text' => $question->question_text,
                'sound' => $question->sound,
                'explanation' => $question->explanation,
                'metadata' => $question->metadata,
                'default_weight' => $question->default_weight ?? 1,
                'custom_score' => $question->custom_score ?? 'no',
            ]);

            foreach ($question->options as $option) {
                QuestionOption::create([
                    'question_id' => $newQuestion->question_id,
                    'option_text' => $option->option_text,
                    'weight' => $option->weight,
                    'is_correct' => $option->is_correct,
                ]);
            }
        });

        return redirect()
            ->route('admin.question.index', $validated['tryout_detail_id'])
            ->with('success', 'Soal dari bank berhasil ditambahkan.');
    }

    public function bulkCloneToTryout(Request $request)
    {
        $validated = $request->validate([
            'tryout_detail_id' => ['required', 'exists:tryout_details,tryout_detail_id'],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['exists:question_bank_questions,id'],
        ], [], [
            'question_ids' => 'Daftar soal',
        ]);

        $tryoutDetail = TryoutDetail::findOrFail($validated['tryout_detail_id']);

        $questions = QuestionBankQuestion::with('options')
            ->whereIn('id', $validated['question_ids'])
            ->get();

        DB::transaction(function () use ($questions, $tryoutDetail) {
            foreach ($questions as $question) {
                $newQuestion = Question::create([
                    'tryout_detail_id' => $tryoutDetail->tryout_detail_id,
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'sound' => $question->sound,
                    'explanation' => $question->explanation,
                    'metadata' => $question->metadata,
                    'default_weight' => $question->default_weight ?? 1,
                    'custom_score' => $question->custom_score ?? 'no',
                ]);

                foreach ($question->options as $option) {
                    QuestionOption::create([
                        'question_id' => $newQuestion->question_id,
                        'option_text' => $option->option_text,
                        'weight' => $option->weight,
                        'is_correct' => $option->is_correct,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.question.index', $validated['tryout_detail_id'])
            ->with('success', 'Soal dari bank berhasil ditambahkan.');
    }

    public function bulkMoveQuestions(Request $request)
    {
        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['exists:question_bank_questions,id'],
            'target_question_bank_id' => ['required', 'exists:question_banks,id'],
        ], [], [
            'question_ids' => 'Daftar soal',
            'target_question_bank_id' => 'Bank tujuan',
        ]);

        $questions = QuestionBankQuestion::query()
            ->whereIn('id', $validated['question_ids'])
            ->get(['id', 'question_bank_id']);

        if ($questions->isEmpty()) {
            return back()->with('error', 'Tidak ada soal yang dipilih.');
        }

        $targetBankId = (int) $validated['target_question_bank_id'];
        if ($questions->every(fn($question) => (int) $question->question_bank_id === $targetBankId)) {
            return back()->with('error', 'Pilih bank tujuan yang berbeda.');
        }

        QuestionBankQuestion::query()
            ->whereIn('id', $questions->pluck('id'))
            ->update(['question_bank_id' => $targetBankId]);

        return back()->with('success', $questions->count() . ' soal berhasil dipindahkan.');
    }

    public function bulkDestroyQuestions(Request $request)
    {
        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['exists:question_bank_questions,id'],
        ], [], [
            'question_ids' => 'Daftar soal',
        ]);

        $deleted = QuestionBankQuestion::query()
            ->whereIn('id', $validated['question_ids'])
            ->delete();

        return back()->with('success', $deleted . ' soal berhasil dihapus dari bank.');
    }

    public function destroyQuestion(QuestionBankQuestion $question)
    {
        $question->delete();

        return back()->with('success', 'Soal berhasil dihapus dari bank.');
    }

    private function aiPreviewSessionKey(QuestionBank $bank): string
    {
        return 'ai_question_preview_' . $bank->id;
    }

    private function normalizeAiPreviewQuestions(array $questions): array
    {
        $letters = ['A', 'B', 'C', 'D', 'E'];

        return collect($questions)
            ->take(50)
            ->map(function ($question) use ($letters) {
                $options = collect($question['options'] ?? [])
                    ->values()
                    ->map(function ($option, $index) use ($letters) {
                        return [
                            'label' => strtoupper(trim((string) ($option['label'] ?? $letters[$index] ?? ''))),
                            'text' => trim((string) ($option['text'] ?? '')),
                        ];
                    })
                    ->filter(fn ($option) => $option['label'] !== '' && $option['text'] !== '')
                    ->unique('label')
                    ->values()
                    ->all();

                return [
                    'question_text' => trim((string) ($question['question_text'] ?? '')),
                    'options' => $options,
                    'correct_option' => strtoupper(trim((string) ($question['correct_option'] ?? ''))),
                    'explanation' => trim((string) ($question['explanation'] ?? '')),
                ];
            })
            ->filter(function ($question) {
                return $question['question_text'] !== ''
                    && count($question['options']) >= 2
                    && collect($question['options'])->contains('label', $question['correct_option']);
            })
            ->values()
            ->all();
    }

    private function buildBreadcrumbs(QuestionBank $bank): array
    {
        $breadcrumbs = [];
        $current = $bank;

        while ($current) {
            $breadcrumbs[] = $current;
            $current = $current->parent;
        }

        return array_reverse($breadcrumbs);
    }

    private function buildBankOptions()
    {
        $banks = QuestionBank::orderBy('name')->get(['id', 'name', 'parent_id']);
        $banksById = $banks->keyBy('id');

        return $banks->map(function ($bank) use ($banksById) {
            $segments = [$bank->name];
            $current = $bank;
            $guard = 0;

            while ($current->parent_id && $banksById->has($current->parent_id) && $guard < 20) {
                $current = $banksById->get($current->parent_id);
                array_unshift($segments, $current->name);
                $guard++;
            }

            return [
                'id' => $bank->id,
                'name' => $bank->name,
                'parent_id' => $bank->parent_id,
                'path' => implode(' > ', $segments),
            ];
        })->sortBy('path')->values();
    }

    private function buildRecursiveQuestionCounts($banks): array
    {
        $childrenByParent = $banks->groupBy('parent_id');
        $directCounts = $banks->mapWithKeys(function ($bank) {
            return [$bank->id => (int) $bank->questions_count];
        })->all();
        $totals = [];

        $visit = function ($bankId) use (&$visit, &$totals, $childrenByParent, $directCounts) {
            if (array_key_exists($bankId, $totals)) {
                return $totals[$bankId];
            }

            $total = $directCounts[$bankId] ?? 0;
            foreach ($childrenByParent->get($bankId, collect()) as $child) {
                $total += $visit($child->id);
            }

            $totals[$bankId] = $total;

            return $total;
        };

        foreach ($banks as $bank) {
            $visit($bank->id);
        }

        return $totals;
    }

    private function normalizeImportScore($value, float $fallback = 0): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }

        if (!is_numeric($value)) {
            return $fallback;
        }

        return max(0, min(999, (float) $value));
    }

    private function validateMultipleChoice(Request $request, bool $isMultipleAnswer = false): void
    {
        $rules = [
            'option_a' => ['required', 'string'],
            'option_b' => ['required', 'string'],
            'option_c' => ['required', 'string'],
            'option_d' => ['required', 'string'],
            'option_e' => ['nullable', 'string'],
            'correct_answer' => ['required', 'in:A,B,C,D,E'],
            'correct_answers' => ['nullable', 'array', 'min:1'],
            'correct_answers.*' => ['in:A,B,C,D,E'],
            'score_a' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'score_b' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'score_c' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'score_d' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'score_e' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ];

        if ($isMultipleAnswer) {
            $rules['correct_answer'] = ['nullable', 'in:A,B,C,D,E'];
            $rules['correct_answers'] = ['required', 'array', 'min:1'];
            $rules['multiple_answer_score_correct'] = ['required', 'numeric'];
            $rules['multiple_answer_score_wrong'] = ['required', 'numeric'];
            $rules['multiple_answer_scoring_mode'] = ['required', 'in:fullscore,partial'];
        }

        $request->validate($rules, [], [
            'option_a' => 'Pilihan A',
            'option_b' => 'Pilihan B',
            'option_c' => 'Pilihan C',
            'option_d' => 'Pilihan D',
            'option_e' => 'Pilihan E',
            'correct_answer' => 'Jawaban benar',
            'correct_answers' => 'Daftar jawaban benar',
        ]);
    }

    private function validateTrueFalse(Request $request): void
    {
        $request->validate([
            'correct_answer' => ['required', 'in:A,B'],
            'score_a' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'score_b' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ], [], [
            'correct_answer' => 'Jawaban benar',
        ]);
    }

    private function validateMatching(Request $request): void
    {
        $request->validate([
            'matching_pairs' => ['required', 'array', 'min:2'],
            'matching_pairs.*.left' => ['required', 'string'],
            'matching_pairs.*.right' => ['required', 'string'],
            'matching_score_correct' => ['required', 'numeric'],
            'matching_score_wrong' => ['required', 'numeric'],
            'matching_scoring_mode' => ['required', 'in:fullscore,partial'],
        ], [], [
            'matching_pairs' => 'Pasangan pencocokan',
            'matching_pairs.*.left' => 'Kolom kiri',
            'matching_pairs.*.right' => 'Kolom kanan',
        ]);
    }

    private function validateMultipleTrueFalse(Request $request): void
    {
        $request->validate([
            'mtf_true_label' => ['required', 'string', 'max:50'],
            'mtf_false_label' => ['required', 'string', 'max:50'],
            'mtf_scoring_mode' => ['required', 'in:fullscore,partial'],
            'mtf_score_correct' => ['required', 'numeric'],
            'mtf_score_wrong' => ['required', 'numeric'],
            'mtf_statements' => ['required', 'array', 'min:2'],
            'mtf_statements.*.text' => ['required', 'string'],
            'mtf_statements.*.correct' => ['required', 'in:true,false'],
        ]);
    }

    private function validateShortAnswer(Request $request): void
    {
        $request->validate([
            'short_answer_expected' => ['nullable', 'string'],
            'short_answer_case_sensitive' => ['nullable', 'boolean'],
            'essay_scoring_mode' => ['nullable', 'in:auto,manual'],
        ]);
        
        // Cek Essay AI quota jika mode otomatis dipilih
        $scoringMode = $request->input('essay_scoring_mode');
        if ($scoringMode === 'auto') {
            $quotaCheck = PlanQuotaService::canUseEssayAI();
            if (!$quotaCheck['allowed']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'essay_scoring_mode' => $quotaCheck['reason'] ?? 'Essay AI tidak tersedia atau kuota habis.'
                ]);
            }
        }
    }

    private function validateAudio(Request $request): void
    {
        $request->validate([
            'audio_instructions' => ['nullable', 'string'],
            'audio_max_duration' => ['nullable', 'integer', 'min:5', 'max:600'],
            'audio_max_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }

    private function buildMetadata(Request $request, string $type): array
    {
        return match ($type) {
            'matching' => $this->buildMatchingMetadata($request),
            'multiple_true_false' => $this->buildMultipleTrueFalseMetadata($request),
            'short_answer', 'essay' => $this->buildShortAnswerMetadata($request, $type),
            'audio' => $this->buildAudioMetadata($request),
            'multiple_answer' => [
                'multiple_answer' => [
                    'score_correct' => (float) $request->input('multiple_answer_score_correct', 1),
                    'score_wrong' => (float) $request->input('multiple_answer_score_wrong', 0),
                    'scoring_mode' => in_array($request->input('multiple_answer_scoring_mode'), ['fullscore', 'partial'], true)
                        ? $request->input('multiple_answer_scoring_mode')
                        : 'fullscore',
                ],
            ],
            default => [],
        };
    }

    private function buildMultipleTrueFalseMetadata(Request $request): array
    {
        $statements = [];
        foreach ($request->input('mtf_statements', []) as $index => $row) {
            $text = trim((string) ($row['text'] ?? ''));
            $correct = strtolower((string) ($row['correct'] ?? ''));
            $id = trim((string) ($row['id'] ?? ''));
            if ($text === '' || !in_array($correct, ['true', 'false'], true)) {
                continue;
            }

            $statements[] = [
                'id' => $id !== '' ? $id : 'stmt_' . ($index + 1),
                'text' => $text,
                'correct' => $correct,
            ];
        }

        return [
            'multiple_true_false' => [
                'true_label' => trim((string) $request->input('mtf_true_label', 'Benar')),
                'false_label' => trim((string) $request->input('mtf_false_label', 'Salah')),
                'scoring_mode' => in_array($request->input('mtf_scoring_mode'), ['fullscore', 'partial'], true)
                    ? $request->input('mtf_scoring_mode')
                    : 'fullscore',
                'score_correct' => (float) $request->input('mtf_score_correct', 1),
                'score_wrong' => (float) $request->input('mtf_score_wrong', 0),
                'statements' => $statements,
            ],
        ];
    }

    private function buildMatchingMetadata(Request $request): array
    {
        $pairs = [];
        foreach ($request->input('matching_pairs', []) as $pair) {
            $left = trim($pair['left'] ?? '');
            $right = trim($pair['right'] ?? '');
            if ($left === '' || $right === '') {
                continue;
            }
            $pairs[] = ['left' => $left, 'right' => $right];
        }

        return [
            'matching_pairs' => $pairs,
            'matching_scores' => [
                'score_correct' => (float) $request->input('matching_score_correct', 1),
                'score_wrong' => (float) $request->input('matching_score_wrong', 0),
                'scoring_mode' => in_array($request->input('matching_scoring_mode'), ['fullscore', 'partial'], true)
                    ? $request->input('matching_scoring_mode')
                    : 'fullscore',
            ],
        ];
    }

    private function buildShortAnswerMetadata(Request $request, string $type): array
    {
        $expectedRaw = $request->input('short_answer_expected', '');
        $expectedAnswers = collect(preg_split("/\r\n|\r|\n/", $expectedRaw))
            ->filter(fn ($line) => filled(trim($line)))
            ->map(fn ($line) => trim($line))
            ->values()
            ->all();

        $evaluationMode = $type === 'essay'
            ? $request->input('essay_scoring_mode', 'manual')
            : 'auto';

        if (!in_array($evaluationMode, ['auto', 'manual'], true)) {
            $evaluationMode = 'manual';
        }

        $caseSensitive = $type === 'essay'
            ? false
            : $request->boolean('short_answer_case_sensitive');

        return [
            'short_answer' => [
                'expected_answers' => $expectedAnswers,
                'case_sensitive' => $caseSensitive,
                'evaluation_mode' => $evaluationMode,
                'manual_review' => $type === 'essay'
                    ? ($evaluationMode !== 'auto' || empty($expectedAnswers))
                    : empty($expectedAnswers),
            ],
        ];
    }

    private function buildAudioMetadata(Request $request): array
    {
        return [
            'audio_answer' => [
                'instructions' => $request->input('audio_instructions'),
                'max_duration' => $request->filled('audio_max_duration') ? (int) $request->input('audio_max_duration') : null,
                'max_size' => $request->filled('audio_max_size') ? (int) $request->input('audio_max_size') : null,
                'allowed_mimes' => [
                    'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/m4a', 'audio/x-m4a',
                ],
            ],
        ];
    }

    private function buildBankImportOptions(array $row): array
    {
        $options = [];

        for ($i = 0; $i < 5; $i++) {
            $optionTextIndex = 2 + ($i * 3);
            $optionCorrectIndex = 3 + ($i * 3);
            $optionWeightIndex = 4 + ($i * 3);
            $optionText = trim((string) ($row[$optionTextIndex] ?? ''));

            if ($optionText === '') {
                continue;
            }

            $options[] = [
                'text' => $optionText,
                'is_correct' => (int) ($row[$optionCorrectIndex] ?? 0) === 1,
                'weight' => is_numeric($row[$optionWeightIndex] ?? null) ? (float) $row[$optionWeightIndex] : 1,
            ];
        }

        return $options;
    }

    private function prepareOptions(Request $request, string $type): array
    {
        $options = [];

        if ($type === 'true_false') {
            $options = [
                ['key' => 'A', 'text' => $request->option_a ?: 'Benar'],
                ['key' => 'B', 'text' => $request->option_b ?: 'Salah'],
            ];
        } else {
            $options = [
                ['key' => 'A', 'text' => $request->option_a],
                ['key' => 'B', 'text' => $request->option_b],
                ['key' => 'C', 'text' => $request->option_c],
                ['key' => 'D', 'text' => $request->option_d],
            ];

            if ($request->filled('option_e')) {
                $options[] = ['key' => 'E', 'text' => $request->option_e];
            }
        }

        $useCustomScores = $request->boolean('use_custom_scores') && $type !== 'multiple_answer';
        $correctAnswer = strtoupper((string) $request->input('correct_answer', 'A'));
        $correctAnswers = $type === 'multiple_answer'
            ? collect($request->input('correct_answers', []))
                ->map(fn ($value) => strtoupper((string) $value))
                ->filter()
                ->unique()
                ->values()
                ->all()
            : [$correctAnswer];

        return collect($options)->map(function ($option) use ($useCustomScores, $correctAnswers, $request) {
            $scoreField = 'score_' . strtolower($option['key']);
            $isCorrect = in_array($option['key'], $correctAnswers, true);
            $weight = $useCustomScores ? (float) ($request->input($scoreField, 0)) : ($isCorrect ? 1 : 0);

            return [
                'text' => $option['text'],
                'weight' => $weight,
                'is_correct' => $isCorrect,
            ];
        })->toArray();
    }
}
