<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Models\Question;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use App\Models\UserPackageAcces;
use App\Models\QuestionOption;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Services\ToeflScoringService;

class TryoutController extends Controller
{
    public function __construct()
    {
        // Set timezone untuk semua method dalam controller ini
        Carbon::setLocale('id');
        date_default_timezone_set('Asia/Jakarta');
    }

    private function processAnswerByType(Request $request, Question $question, ?UserAnswerDetail $existingDetail): array
    {
        $type = $question->question_type ?? 'multiple_choice';

        if ($type === 'true_false') {
            $type = 'multiple_choice';
        }

        switch ($type) {
            case 'matching':
                return $this->handleMatchingAnswer($request, $question);
            case 'short_answer':
            case 'essay':
                return $this->handleShortAnswer($request, $question);
            case 'audio':
                return $this->handleAudioAnswer($request, $question, $existingDetail);
            default:
                return $this->handleMultipleChoiceAnswer($request, $question);
        }
    }

    private function handleMultipleChoiceAnswer(Request $request, Question $question): array
    {
        $request->validate([
            'option_id' => 'required|exists:question_options,question_option_id'
        ]);

        $selectedOption = $question->questionOptions()
            ->where('question_option_id', $request->option_id)
            ->first();

        if (!$selectedOption) {
            throw ValidationException::withMessages([
                'option_id' => 'Pilihan jawaban tidak valid untuk soal ini.'
            ]);
        }

        $isCorrect = $this->determineCorrectAnswer($question, $selectedOption);

        return [
            'detail' => [
                'question_option_id' => $selectedOption->question_option_id,
                'answer_text' => null,
                'answer_json' => null,
                'answer_file_path' => null,
                'is_correct' => $isCorrect,
            ],
            'response' => [
                'option_id' => $selectedOption->question_option_id,
                'is_correct' => $isCorrect,
            ],
            'delete_file' => false,
        ];
    }

    private function handleShortAnswer(Request $request, Question $question): array
    {
        $request->validate([
            'answer_text' => 'required|string'
        ]);

        $answerText = trim($request->input('answer_text'));
        $metadata = is_array($question->metadata) ? $question->metadata : [];
        $shortMeta = isset($metadata['short_answer']) && is_array($metadata['short_answer']) ? $metadata['short_answer'] : [];

        $expectedAnswers = isset($shortMeta['expected_answers']) && is_array($shortMeta['expected_answers']) ? $shortMeta['expected_answers'] : [];
        $caseSensitive = $shortMeta['case_sensitive'] ?? false;
        $manualReview = $shortMeta['manual_review'] ?? false;

        $isCorrect = false;

        if (!empty($expectedAnswers)) {
            foreach ($expectedAnswers as $expected) {
                $expectedValue = trim((string) $expected);
                $expectedComparable = $caseSensitive ? $expectedValue : mb_strtolower($expectedValue);
                $userComparable = $caseSensitive ? $answerText : mb_strtolower($answerText);

                if ($userComparable === $expectedComparable) {
                    $isCorrect = true;
                    break;
                }
            }
        }

        if ($question->question_type === 'essay' || empty($expectedAnswers)) {
            $manualReview = true;
            $isCorrect = false;
        }

        $answerJson = [
            'pending_review' => $manualReview,
            'case_sensitive' => $caseSensitive,
            'expected_answers' => $expectedAnswers,
        ];

        return [
            'detail' => [
                'question_option_id' => null,
                'answer_text' => $answerText,
                'answer_json' => $answerJson,
                'answer_file_path' => null,
                'is_correct' => $isCorrect,
            ],
            'response' => [
                'is_correct' => $isCorrect,
                'manual_review' => $manualReview,
            ],
            'delete_file' => false,
        ];
    }

    private function handleMatchingAnswer(Request $request, Question $question): array
    {
        $input = $request->input('matching_answers');

        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input = $decoded;
            }
        }

        if (!is_array($input)) {
            throw ValidationException::withMessages([
                'matching_answers' => 'Jawaban pencocokan tidak valid.'
            ]);
        }

        $metadata = is_array($question->metadata) ? $question->metadata : [];
        $pairs = isset($metadata['matching_pairs']) && is_array($metadata['matching_pairs']) ? $metadata['matching_pairs'] : [];

        if (empty($pairs)) {
            throw ValidationException::withMessages([
                'matching_answers' => 'Soal pencocokan belum memiliki pasangan jawaban.'
            ]);
        }

        $correctMap = [];
        foreach ($pairs as $pair) {
            $left = trim((string) ($pair['left'] ?? ''));
            $right = trim((string) ($pair['right'] ?? ''));
            if ($left === '' || $right === '') {
                continue;
            }
            $correctMap[$left] = $right;
        }

        $selected = [];
        $correctCount = 0;

        foreach ($correctMap as $left => $right) {
            if (!array_key_exists($left, $input)) {
                throw ValidationException::withMessages([
                    'matching_answers' => 'Harap lengkapi jawaban untuk semua pasangan.'
                ]);
            }

            $chosen = trim((string) $input[$left]);
            $selected[$left] = $chosen;

            if ($chosen !== '' && $chosen === $right) {
                $correctCount++;
            }
        }

        $total = count($correctMap);
        $isCorrect = $total > 0 ? $correctCount === $total : false;

        return [
            'detail' => [
                'question_option_id' => null,
                'answer_text' => null,
                'answer_json' => [
                    'matches' => $selected,
                    'summary' => [
                        'correct' => $correctCount,
                        'total' => $total,
                    ],
                ],
                'answer_file_path' => null,
                'is_correct' => $isCorrect,
            ],
            'response' => [
                'is_correct' => $isCorrect,
                'correct' => $correctCount,
                'total' => $total,
            ],
            'delete_file' => false,
        ];
    }

    private function handleAudioAnswer(Request $request, Question $question, ?UserAnswerDetail $existingDetail): array
    {
        $metadata = is_array($question->metadata) ? $question->metadata : [];
        $audioMeta = isset($metadata['audio_answer']) && is_array($metadata['audio_answer']) ? $metadata['audio_answer'] : [];

        $maxSizeMb = isset($audioMeta['max_size']) ? (int) $audioMeta['max_size'] : 15;
        $allowedExtensions = ['mp3', 'wav', 'm4a'];

        $request->validate([
            'answer_audio' => 'required|file|mimes:' . implode(',', $allowedExtensions) . '|max:' . ($maxSizeMb * 1024)
        ], [], [
            'answer_audio' => 'jawaban audio'
        ]);

        $file = $request->file('answer_audio');
        $storagePath = $file->store('user_answers/audio/' . Auth::id(), 'public');

        $answerJson = [
            'pending_review' => true,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ];

        return [
            'detail' => [
                'question_option_id' => null,
                'answer_text' => null,
                'answer_json' => $answerJson,
                'answer_file_path' => $storagePath,
                'is_correct' => false,
            ],
            'response' => [
                'file_url' => Storage::disk('public')->url($storagePath),
                'manual_review' => true,
            ],
            'delete_file' => true,
        ];
    }

    public function indexLobby($id_package, $id_tryout)
    {
        if ($id_package === 'free') {
            // Free tryout access dengan timezone Jakarta
            $now = Carbon::now('Asia/Jakarta');
            $tryout = Tryout::where('tryout_id', $id_tryout)
                ->where('is_active', true)
                ->where('start_date', '<=', $now)
                ->where('end_date', '>=', $now)
                ->firstOrFail();
            $package = null;
        } else {
            $package = Package::findOrFail($id_package);
            $tryout = Tryout::findOrFail($id_tryout);

            // Check if user has access to package
            $hasAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $id_package)
                ->where('status', 'active')
                ->where(function ($query) {
                    $now = Carbon::now('Asia/Jakarta');
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', $now);
                })
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('user.package.index')
                    ->with('error', 'Anda tidak memiliki akses ke paket ini');
            }
        }

        // Check if tryout is still active dengan timezone Jakarta
        $now = Carbon::now('Asia/Jakarta');
        if (Carbon::parse($tryout->end_date)->lt($now)) {
            return redirect()->back()->with('error', 'Tryout sudah berakhir');
        }

        // Get tryout details untuk menampilkan info di lobby
        $tryoutDetails = $tryout->tryoutDetails()->orderBy('tryout_detail_id')->get();

        // Calculate total duration dan questions untuk SKD Full
        $totalDuration = $tryoutDetails->sum('duration');
        $totalQuestions = 0;
        foreach ($tryoutDetails as $detail) {
            $totalQuestions += Question::where('tryout_detail_id', $detail->tryout_detail_id)->count();
        }

        // Get user's previous attempts
        $attempts = UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->count();

        return view('user.pages.tryout.lobby', compact(
            'package',
            'tryout',
            'attempts',
            'tryoutDetails',
            'totalDuration',
            'totalQuestions'
        ));
    }

    public function indexTryout($id_package, $id_tryout, $number)
    {
        $now = Carbon::now('Asia/Jakarta');

        // Handle free tryouts or package tryouts
        if ($id_package === 'free') {
            $tryout = Tryout::where('tryout_id', $id_tryout)
                ->where('is_active', true)
                ->where('start_date', '<=', $now)
                ->where('end_date', '>=', $now)
                ->firstOrFail();
            $package = null;
        } else {
            $package = Package::findOrFail($id_package);
            $tryout = Tryout::findOrFail($id_tryout);

            // Check access
            $hasAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $id_package)
                ->where('status', 'active')
                ->where(function ($query) use ($now) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', $now);
                })
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('user.package.index')
                    ->with('error', 'Anda tidak memiliki akses ke paket ini');
            }
        }

        // Get all tryout details dalam urutan yang benar
        $tryoutDetails = $tryout->tryoutDetails()->get(); // ambil semua dulu

        if ($tryout->system_tryout === 'toefl') {
            // Tentukan urutan khusus TOEFL
            $order = ['listening', 'writing', 'reading']; // type_subtest sesuai database
            $tryoutDetails = $tryoutDetails->sortBy(function ($detail) use ($order) {
                return array_search($detail->type_subtest, $order);
            });
        } else {
            // Default: urutkan berdasarkan tryout_detail_id
            $tryoutDetails = $tryoutDetails->sortBy('tryout_detail_id');
        }

        $allQuestions = collect();
        $subtestInfo = [];

        foreach ($tryoutDetails as $detail) {
            $questions = Question::where('tryout_detail_id', $detail->tryout_detail_id)
                ->with('questionOptions')
                ->orderBy('question_id')
                ->get();

            foreach ($questions as $question) {
                $question->subtest_type = $detail->type_subtest;
                $question->subtest_name = $this->getSubtestName($detail->type_subtest);
                $question->tryout_detail = $detail;
            }

            $allQuestions = $allQuestions->concat($questions);

            $subtestInfo[] = [
                'type' => $detail->type_subtest,
                'name' => $this->getSubtestName($detail->type_subtest),
                'start_number' => $allQuestions->count() - $questions->count() + 1,
                'end_number' => $allQuestions->count(),
                'duration' => $detail->duration,
                'passing_score' => $detail->passing_score,
                'tryout_detail_id' => $detail->tryout_detail_id
            ];
        }

        if ($allQuestions->isEmpty()) {
            return redirect()->back()->with('error', 'Tryout belum memiliki soal');
        }

        if ($number > $allQuestions->count()) {
            return $this->finishTryout($id_package, $id_tryout);
        }

        $currentQuestion = $allQuestions[$number - 1];
        $totalQuestions = $allQuestions->count();

        // Tentukan subtest saat ini
        $currentSubtest = null;
        foreach ($subtestInfo as $subtest) {
            if ($number >= $subtest['start_number'] && $number <= $subtest['end_number']) {
                $currentSubtest = $subtest;
                break;
            }
        }

        // Get or create user answer sessions untuk SKD Full
        // Cek apakah sudah ada attempt_token untuk tryout ini
        $existingUserAnswer = UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->where('status', 'in_progress')
            ->first();

        $attemptToken = $existingUserAnswer ? $existingUserAnswer->attempt_token : Str::uuid()->toString();

        // Untuk SKD Full: buat UserAnswer terpisah untuk setiap subtest dengan token yang sama
        if ($tryoutDetails->count() > 1) {
            foreach ($tryoutDetails as $detail) {
                $userAnswerForSubtest = UserAnswer::where('user_id', Auth::id())
                    ->where('tryout_id', $id_tryout)
                    ->where('tryout_detail_id', $detail->tryout_detail_id)
                    ->where('status', 'in_progress')
                    ->first();

                if (!$userAnswerForSubtest) {
                    UserAnswer::create([
                        'user_id' => Auth::id(),
                        'tryout_id' => $id_tryout,
                        'tryout_detail_id' => $detail->tryout_detail_id,
                        'attempt_token' => $attemptToken,
                        'started_at' => $now,
                        'status' => 'in_progress'
                    ]);
                }
            }
        } else {
            // Single subtest: buat satu UserAnswer saja
            if (!$existingUserAnswer) {
                UserAnswer::create([
                    'user_id' => Auth::id(),
                    'tryout_id' => $id_tryout,
                    'tryout_detail_id' => $tryoutDetails->first()->tryout_detail_id,
                    'attempt_token' => $attemptToken,
                    'started_at' => $now,
                    'status' => 'in_progress'
                ]);
            }
        }

        // Get current subtest's UserAnswer untuk menyimpan jawaban
        $currentUserAnswer = UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->where('tryout_detail_id', $currentSubtest['tryout_detail_id'])
            ->where('status', 'in_progress')
            ->first();

        if (!$currentUserAnswer) {
            return redirect()->back()->with('error', 'Session tryout tidak ditemukan');
        }

        // Calculate total time untuk SKD Full
        $totalDuration = $tryoutDetails->sum('duration');
        $startTime = Carbon::parse($currentUserAnswer->started_at, 'Asia/Jakarta');
        $endTime = $startTime->copy()->addMinutes($totalDuration);

        // Check if time is up - auto finish jika waktu habis
        if ($now->gte($endTime)) {
            return $this->finishTryout($id_package, $id_tryout);
        }

        $remainingSeconds = $endTime->diffInSeconds($now);

        // Get user's answer for current question dari subtest yang sesuai
        $userAnswerDetail = UserAnswerDetail::where('user_answer_id', $currentUserAnswer->user_answer_id)
            ->where('question_id', $currentQuestion->question_id)
            ->with('questionOption')
            ->first();

        // Get all user answers untuk navigation status dari semua subtest
        $allUserAnswers = UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->where('attempt_token', $attemptToken)
            ->where('status', 'in_progress')
            ->get();

        $userAnswerDetails = UserAnswerDetail::whereIn('user_answer_id', $allUserAnswers->pluck('user_answer_id'))
            ->pluck('question_id')
            ->toArray();

        // Get flagged questions dari session dengan attempt_token
        $flaggedQuestions = session('flagged_questions_' . $attemptToken, []);

        return view('user.pages.tryout.index', compact(
            'package',
            'tryout',
            'tryoutDetails',
            'currentQuestion',
            'userAnswerDetail',
            'currentUserAnswer',
            'number',
            'totalQuestions',
            'allQuestions',
            'userAnswerDetails',
            'flaggedQuestions',
            'subtestInfo',
            'currentSubtest',
            'remainingSeconds',
            'attemptToken'
        ));
    }

    private function getSubtestName($type)
    {
        switch ($type) {
            case 'twk':
                return 'Tes Wawasan Kebangsaan';
            case 'tiu':
                return 'Tes Intelegensi Umum';
            case 'tkp':
                return 'Tes Karakteristik Pribadi';
            case 'writing':
                return 'Writing Test';
            case 'reading':
                return 'Reading Comprehension';
            case 'listening':
                return 'Listening Test';
            case 'general':
                return 'General Test';
            case 'teknis':
                return 'Tes Teknis';
            case 'social culture':
                return 'Sosial-Kultural';
            case 'management':
                return 'Manajerial';
            case 'interview':
                return 'Wawancara';
            case 'word':
                return 'Microsoft Word';
            case 'excel':
                return 'Microsoft Excel';
            case 'ppt':
                return 'Microsoft PowerPoint';
            default:
                return ucfirst($type);
        }
    }

    public function saveAnswer(Request $request, $id_package, $id_tryout, $number)
    {
        try {
            $request->validate([
                'question_id' => 'required|exists:questions,question_id',
            ]);

            $now = Carbon::now('Asia/Jakarta');

            $question = Question::with(['questionOptions', 'tryoutDetail'])->find($request->question_id);

            if (!$question) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Question not found'], 404);
                }
                return redirect()->back()->with('error', 'Soal tidak ditemukan');
            }

            $userAnswer = UserAnswer::where('user_id', Auth::id())
                ->where('tryout_id', $id_tryout)
                ->where('tryout_detail_id', $question->tryout_detail_id)
                ->where('status', 'in_progress')
                ->first();

            if (!$userAnswer) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Session not found'], 404);
                }
                return redirect()->back()->with('error', 'Session tryout tidak ditemukan');
            }

            $tryout = Tryout::findOrFail($id_tryout);
            $tryoutDetails = $tryout->tryoutDetails()->get();
            $totalDuration = $tryoutDetails->sum('duration');

            $startTime = Carbon::parse($userAnswer->started_at, 'Asia/Jakarta');
            $endTime = $startTime->copy()->addMinutes($totalDuration);

            if ($now->gte($endTime)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Time is up',
                        'redirect' => route('user.tryout.result', [$id_package, $id_tryout])
                    ], 400);
                }

                return redirect()->route('user.tryout.result', [$id_package, $id_tryout])
                    ->with('error', 'Waktu ujian telah habis');
            }

            $existingDetail = UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
                ->where('question_id', $question->question_id)
                ->first();

            $result = $this->processAnswerByType($request, $question, $existingDetail);

            $detailPayload = $result['detail'];
            $detailPayload['answered_at'] = $now;

            $userAnswerDetail = UserAnswerDetail::updateOrCreate(
                [
                    'user_answer_id' => $userAnswer->user_answer_id,
                    'question_id' => $question->question_id
                ],
                $detailPayload
            );

            if (!empty($result['delete_file']) && $existingDetail && $existingDetail->answer_file_path && Storage::disk('public')->exists($existingDetail->answer_file_path)) {
                Storage::disk('public')->delete($existingDetail->answer_file_path);
            }

            $this->updateSingleSubtestStats($userAnswer);

            $responsePayload = array_merge([
                'success' => true,
                'message' => 'Jawaban berhasil disimpan',
                'question_id' => $question->question_id,
                'question_type' => $question->question_type,
                'answered_at' => $now->toDateTimeString(),
            ], $result['response'] ?? []);

            if ($request->expectsJson()) {
                return response()->json($responsePayload);
            }

            return redirect()->route('user.tryout.index', [$id_package, $id_tryout, $number])
                ->with('success', 'Jawaban berhasil disimpan');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Gagal menyimpan jawaban',
                    'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Gagal menyimpan jawaban');
        }
    }

    /**
     * Determine if answer is correct based on subtest type and rules
     */
    private function determineCorrectAnswer(Question $question, QuestionOption $selectedOption)
    {
        $subtestType = optional($question->tryoutDetail)->type_subtest;

        switch ($subtestType) {
            case 'twk':
            case 'tiu':
                return (bool) $selectedOption->is_correct;

            case 'tkp':
                return $selectedOption->weight > 0;

            case 'writing':
            case 'reading':
            case 'listening':
                return (bool) $selectedOption->is_correct;

            default:
                return (bool) $selectedOption->is_correct;
        }
    }

    /**
     * Calculate total score based on subtest type and rules
     */
    private function calculateTotalScore($userAnswer, $type_subtest)
    {
        $totalScore = 0;

        $userAnswerDetails = UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->with(['questionOption', 'question'])
            ->get();

        foreach ($userAnswerDetails as $detail) {
            $question = $detail->question;
            if (!$question) {
                continue;
            }

            $questionType = $question->question_type ?? 'multiple_choice';
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
            $pendingReview = $answerMeta['pending_review'] ?? false;

            switch ($questionType) {
                case 'matching':
                    if ($detail->is_correct) {
                        $weight = (float) ($question->default_weight ?? 1);
                        if ($weight <= 0) {
                            $pairs = isset($question->metadata['matching_pairs']) && is_array($question->metadata['matching_pairs']) ? count($question->metadata['matching_pairs']) : 1;
                            $weight = max(1, $pairs);
                        }
                        $totalScore += $weight;
                    }
                    break;

                case 'short_answer':
                case 'essay':
                    if ($pendingReview) {
                        break;
                    }

                    if ($detail->is_correct) {
                        $weight = (float) ($question->default_weight ?? 1);
                        $totalScore += $weight > 0 ? $weight : 1;
                    }
                    break;

                case 'audio':
                    // Manual scoring
                    break;

                default:
                    if ($detail->questionOption) {
                        switch ($type_subtest) {
                            case 'twk':
                            case 'tiu':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 5) : 0;
                                break;

                            case 'tkp':
                                $totalScore += (float) ($detail->questionOption->weight ?? 0);
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

    /**
     * Get maximum possible score based on subtest type
     */
    private function getMaxPossibleScore($type_subtest, $totalQuestions)
    {
        switch ($type_subtest) {
            case 'twk':
            case 'tiu':
                return $totalQuestions * 5; // 5 poin per soal

            case 'tkp':
                return $totalQuestions * 5; // Maksimal 5 poin per soal

            case 'writing':
            case 'reading':
            case 'listening':
                return $totalQuestions * 10; // 10 poin per soal untuk certification

            default:
                return $totalQuestions; // 1 poin per soal untuk tryout biasa
        }
    }

    /**
     * Get maximum possible score for SKD Full or Certification Full
     */
    private function getMaxPossibleScoreForSKDFull($tryoutDetails)
    {
        $maxScore = 0;

        foreach ($tryoutDetails as $detail) {
            $maxScore += $this->getMaxPossibleScoreForDetail($detail->tryout_detail_id, $detail->type_subtest);
        }

        return $maxScore;
    }

    public function finishTryout($id_package, $id_tryout)
    {
        $now = Carbon::now('Asia/Jakarta');

        // Get tryout information
        $tryout = Tryout::findOrFail($id_tryout);

        // Get all user answers untuk tryout ini
        $userAnswers = UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->where('status', 'in_progress')
            ->with(['tryoutDetail'])
            ->get();

        if ($userAnswers->isEmpty()) {
            return redirect()->route('user.tryout.result', [$id_package, $id_tryout]);
        }

        // Check if this is a TOEFL test
        if ($tryout->is_toefl == 1) {
            // Use TOEFL scoring system
            $this->processToeflScoring($userAnswers, $now);
        } else {
            // Use regular scoring system
            $this->processRegularScoring($userAnswers, $now);
        }

        return redirect()->route('user.tryout.result', [$id_package, $id_tryout]);
    }

    /**
     * Process TOEFL scoring system
     */
    private function processToeflScoring($userAnswers, $now)
    {
        foreach ($userAnswers as $userAnswer) {
            $this->updateSingleSubtestStats($userAnswer);
        }

        $toeflResults = ToeflScoringService::processToeflScoring($userAnswers);

        foreach ($userAnswers as $userAnswer) {
            $sectionType = $userAnswer->tryoutDetail->type_subtest;
            $sectionKey = $this->mapSectionType($sectionType);

            if (isset($toeflResults[$sectionKey])) {
                $userAnswer->update([
                    'finished_at'       => $now,
                    'raw_score'         => $toeflResults[$sectionKey]['raw_score'],
                    'scaled_score'      => $toeflResults[$sectionKey]['scaled_score'],
                    'toefl_total_score' => $toeflResults['total_score'],
                    // SIMPAN skor per subtest = scaled_score subtest tsb (BUKAN total)
                    'score'             => $toeflResults[$sectionKey]['scaled_score'],
                    'status'            => 'completed',
                    // Lulus berdasarkan total (threshold bisa kamu atur)
                    'is_passed'         => $toeflResults['total_score'] >= 217,
                ]);
            }
        }
    }

    /**
     * Process regular scoring system
     */
    private function processRegularScoring($userAnswers, $now)
    {
        foreach ($userAnswers as $userAnswer) {
            $this->updateSingleSubtestStats($userAnswer);

            // Determine if passed untuk subtest ini
            $passingScore = $userAnswer->tryoutDetail->passing_score ?? 60;
            $isPassed = $userAnswer->score >= $passingScore;

            // Update user answer
            $userAnswer->update([
                'finished_at' => $now,
                'is_passed' => $isPassed,
                'status' => 'completed'
            ]);
        }
    }

    /**
     * Map section type to TOEFL section key
     */
    private function mapSectionType($sectionType)
    {
        switch ($sectionType) {
            case 'listening':
                return 'section1';
            case 'writing':
                return 'section2';
            case 'reading':
                return 'section3';
            case 'word':
                return 'section1';
            case 'excel':
                return 'section2';
            case 'ppt':
                return 'section3';
            case 'teknis':
                return 'section1';
            case 'social culture':
                return 'section2';
            case 'management':
                return 'section3';
            case 'interview':
                return 'section4';
            default:
                return null;
        }
    }

    public function indexResult($id_package, $id_tryout)
    {
        $now = Carbon::now('Asia/Jakarta');

        // Handle free tryouts or package tryouts
        if ($id_package === 'free') {
            $package = null;
        } else {
            $package = Package::findOrFail($id_package);

            // Check access for package tryouts
            $hasAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $id_package)
                ->where('status', 'active')
                ->where(function ($query) use ($now) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', $now);
                })
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('user.package.index')
                    ->with('error', 'Anda tidak memiliki akses ke paket ini');
            }
        }

        // Get tryout information
        $tryout = Tryout::findOrFail($id_tryout);

        // Get all completed user answers untuk tryout ini dengan attempt_token yang sama
        $userAnswers = UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->where('status', 'completed')
            ->with(['tryout.tryoutDetails', 'userAnswerDetails.question.questionOptions', 'tryoutDetail'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($userAnswers->isEmpty()) {
            return redirect()->route('user.tryout.lobby', [$id_package, $id_tryout])
                ->with('error', 'Belum ada hasil tryout yang dapat ditampilkan');
        }

        // Group by attempt_token untuk mendapatkan hasil terbaru
        $latestAttemptToken = $userAnswers->first()->attempt_token;
        $latestUserAnswers = $userAnswers->where('attempt_token', $latestAttemptToken);

        $tryoutDetails = $tryout->tryoutDetails;

        // Check if this is TOEFL test and calculate accordingly
        if ($tryout->is_toefl == 1) {
            return $this->processToeflResults($package, $tryout, $latestUserAnswers, $latestAttemptToken);
        } else {
            return $this->processRegularResults($package, $tryout, $latestUserAnswers, $latestAttemptToken, $tryoutDetails);
        }
    }

    /**
     * Process TOEFL test results
     */
    private function processToeflResults($package, $tryout, $latestUserAnswers, $latestAttemptToken)
    {
        $toeflResults = ToeflScoringService::processToeflScoring($latestUserAnswers);

        // Siapkan hasil per seksi
        $sectionResults = [];
        foreach ($latestUserAnswers as $userAnswer) {
            $sectionType = $userAnswer->tryoutDetail->type_subtest;
            $sectionKey = $this->mapSectionType($sectionType);

            if ($sectionKey && isset($toeflResults[$sectionKey])) {
                $sectionResults[] = [
                    'type'             => $sectionType,
                    'name'             => $this->getSubtestName($sectionType),
                    'raw_score'        => $toeflResults[$sectionKey]['raw_score'],
                    'scaled_score'     => $toeflResults[$sectionKey]['scaled_score'],
                    'correct_answers'  => $userAnswer->correct_answers ?? 0,
                    'wrong_answers'    => $userAnswer->wrong_answers ?? 0,
                    'unanswered'       => $userAnswer->unanswered ?? 0,
                    'total_questions'  => $userAnswer->userAnswerDetails->count(),
                    // TAMPILKAN skor seksi = scaled_score seksi tsb
                    'score'            => $toeflResults[$sectionKey]['scaled_score'],
                ];
            }
        }

        // Simpan juga total untuk ditampilkan di header/summary
        $overallTotal = $toeflResults['total_score'] ?? null;

        return view('user.pages.tryout.result-toefl', compact(
            'package',
            'tryout',
            'toeflResults',     // masih berisi total_score + detail
            'sectionResults',   // per-seksi
            'latestAttemptToken',
            'overallTotal'      // opsional dipakai di blade
        ));
    }

    /**
     * Process regular test results
     */
    private function processRegularResults($package, $tryout, $latestUserAnswers, $latestAttemptToken, $tryoutDetails)
    {
        // Calculate overall statistics
        $totalQuestions = $latestUserAnswers->sum(function ($ua) {
            return $ua->userAnswerDetails->count();
        });
        $correctAnswers = $latestUserAnswers->sum('correct_answers');
        $wrongAnswers = $latestUserAnswers->sum('wrong_answers');

        if ($tryoutDetails->count() > 1) {
            // Multiple subtest calculation
            $rawScore = $this->calculateTotalScoreForSKDFullFromUserAnswers($latestUserAnswers);
            $maxScore = $this->getMaxPossibleScoreForSKDFull($tryoutDetails);

            // Calculate per subtest results
            $subtestResults = $this->calculateSubtestResultsFromUserAnswers($latestUserAnswers);
        } else {
            // Single subtest calculation
            $singleUserAnswer = $latestUserAnswers->first();
            $rawScore = $this->calculateTotalScore($singleUserAnswer, $singleUserAnswer->tryoutDetail->type_subtest);
            $maxScore = $this->getMaxPossibleScoreForDetail($singleUserAnswer->tryout_detail_id, $singleUserAnswer->tryoutDetail->type_subtest);
            $subtestResults = null;
        }

        // Calculate overall percentage
        $overallPercentage = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;

        return view('user.pages.tryout.result', compact(
            'package',
            'latestUserAnswers',
            'latestAttemptToken',
            'totalQuestions',
            'correctAnswers',
            'wrongAnswers',
            'rawScore',
            'maxScore',
            'tryout',
            'tryoutDetails',
            'subtestResults',
            'overallPercentage'
        ));
    }

    /**
     * Calculate total score for SKD Full from multiple UserAnswer records
     */
    private function calculateTotalScoreForSKDFullFromUserAnswers($userAnswers)
    {
        $totalScore = 0;

        foreach ($userAnswers as $userAnswer) {
            $totalScore += $this->calculateTotalScore($userAnswer, $userAnswer->tryoutDetail->type_subtest);
        }

        return $totalScore;
    }

    /**
     * Calculate results per subtest from UserAnswer records
     */
    private function calculateSubtestResultsFromUserAnswers($userAnswers)
    {
        $subtestResults = [];

        foreach ($userAnswers as $userAnswer) {
            $detail = $userAnswer->tryoutDetail;
            $totalQuestions = Question::where('tryout_detail_id', $detail->tryout_detail_id)->count();

            $subtestScore = $this->calculateTotalScore($userAnswer, $detail->type_subtest);
            $maxSubtestScore = $this->getMaxPossibleScoreForDetail($detail->tryout_detail_id, $detail->type_subtest);
            $percentage = $maxSubtestScore > 0 ? ($subtestScore / $maxSubtestScore) * 100 : 0;

            // Set passing score based on subtest type
            $passingScore = $detail->passing_score ?? $this->getDefaultPassingScore($detail->type_subtest);
            $isPassed = $passingScore !== null ? $subtestScore >= $passingScore : false;
            $passingScorePercentage = ($passingScore !== null && $maxSubtestScore > 0)
                ? ($passingScore / $maxSubtestScore) * 100
                : null;

            $subtestResults[] = [
                'type' => $detail->type_subtest,
                'name' => $this->getSubtestName($detail->type_subtest),
                'total_questions' => $totalQuestions,
                'correct_answers' => $userAnswer->correct_answers ?? 0,
                'wrong_answers' => $userAnswer->wrong_answers ?? 0,
                'unanswered' => $userAnswer->unanswered ?? 0,
                'raw_score' => $subtestScore,
                'max_score' => $maxSubtestScore,
                'percentage' => $percentage,
                'passing_score' => $passingScore,
                'passing_percentage' => $passingScorePercentage,
                'is_passed' => $isPassed
            ];
        }

        return $subtestResults;
    }

    /**
     * Get default passing score for each subtest type
     */
    private function getDefaultPassingScore($type_subtest)
    {
        switch ($type_subtest) {
            case 'word':
            case 'excel':
            case 'ppt':
                return 70; // Computer applications: 70%
            case 'teknis':
            case 'social culture':
            case 'management':
            case 'interview':
                return 65; // PPPK subtests: 65%
            default:
                return 60; // Default: 60%
        }
    }

    public function toggleFlag(Request $request, $id_package, $id_tryout)
    {
        try {
            $request->validate([
                'question_id' => 'required|exists:questions,question_id'
            ]);

            // Get attempt token dari session atau dari user answer
            $userAnswer = UserAnswer::where('user_id', Auth::id())
                ->where('tryout_id', $id_tryout)
                ->where('status', 'in_progress')
                ->first();

            if (!$userAnswer) {
                return response()->json(['error' => 'Session not found'], 404);
            }

            $questionId = $request->question_id;
            $sessionKey = 'flagged_questions_' . $userAnswer->attempt_token;
            $flaggedQuestions = session($sessionKey, []);

            if (in_array($questionId, $flaggedQuestions)) {
                // Remove flag
                $flaggedQuestions = array_diff($flaggedQuestions, [$questionId]);
                $isFlagged = false;
            } else {
                // Add flag
                $flaggedQuestions[] = $questionId;
                $isFlagged = true;
            }

            session([$sessionKey => array_values($flaggedQuestions)]);

            return response()->json([
                'success' => true,
                'flagged' => $isFlagged,
                'message' => $isFlagged ? 'Soal berhasil ditandai' : 'Tanda soal berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal mengubah status tandai',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update statistics for a single subtest UserAnswer
     */
    private function updateSingleSubtestStats($userAnswer)
    {
        $userAnswerDetails = UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->with(['questionOption', 'question'])
            ->get();

        $totalQuestions = Question::where('tryout_detail_id', $userAnswer->tryout_detail_id)->count();
        $correctAnswers = 0;
        $wrongAnswers = 0;
        $totalScore = 0;

        foreach ($userAnswerDetails as $detail) {
            $question = $detail->question;
            if (!$question) {
                continue;
            }

            $questionType = $question->question_type ?? 'multiple_choice';
            $pendingReview = false;
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
            if (isset($answerMeta['pending_review'])) {
                $pendingReview = (bool) $answerMeta['pending_review'];
            }

            switch ($questionType) {
                case 'matching':
                    if ($detail->is_correct) {
                        $correctAnswers++;
                    } else {
                        $wrongAnswers++;
                    }

                    $weight = (float) ($question->default_weight ?? 1);
                    if ($weight <= 0) {
                        $pairs = isset($question->metadata['matching_pairs']) && is_array($question->metadata['matching_pairs']) ? count($question->metadata['matching_pairs']) : 1;
                        $weight = max(1, $pairs);
                    }
                    $totalScore += $detail->is_correct ? $weight : 0;
                    break;

                case 'short_answer':
                case 'essay':
                    if ($pendingReview) {
                        continue 2;
                    }

                    if ($detail->is_correct) {
                        $correctAnswers++;
                    } else {
                        $wrongAnswers++;
                    }

                    $weight = (float) ($question->default_weight ?? 1);
                    $totalScore += $detail->is_correct ? ($weight > 0 ? $weight : 1) : 0;
                    break;

                case 'audio':
                    // Audio answers require manual review, skip scoring for now
                    continue 2;

                default:
                    if ($detail->questionOption) {
                        if ($detail->is_correct) {
                            $correctAnswers++;
                        } else {
                            $wrongAnswers++;
                        }

                        switch ($userAnswer->tryoutDetail->type_subtest) {
                            case 'twk':
                            case 'tiu':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 5) : 0;
                                break;
                            case 'tkp':
                                $totalScore += (float) ($detail->questionOption->weight ?? 0);
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

        $unanswered = $totalQuestions - $userAnswerDetails->count();

        // Calculate percentage
        $maxScore = $this->getMaxPossibleScoreForDetail($userAnswer->tryout_detail_id, $userAnswer->tryoutDetail->type_subtest);
        $percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;

        // Update the User
        $userAnswer->update([
            'correct_answers' => $correctAnswers,
            'wrong_answers' => $wrongAnswers,
            'unanswered' => $unanswered,
            'score' => $percentage
        ]);
    }

    // Maksimum skor dinamis berdasarkan bobot pada template
    private function getMaxPossibleScoreForDetail(int $tryoutDetailId, string $type_subtest)
    {
        $questions = Question::where('tryout_detail_id', $tryoutDetailId)
            ->with('questionOptions')
            ->get();

        if ($questions->isEmpty()) {
            return 0;
        }

        $total = 0;

        foreach ($questions as $question) {
            $questionType = $question->question_type ?? 'multiple_choice';

            switch ($questionType) {
                case 'matching':
                    $weight = (float) ($question->default_weight ?? 0);
                    if ($weight <= 0) {
                        $pairs = isset($question->metadata['matching_pairs']) && is_array($question->metadata['matching_pairs']) ? count($question->metadata['matching_pairs']) : 1;
                        $weight = max(1, $pairs);
                    }
                    $total += $weight;
                    break;

                case 'short_answer':
                case 'essay':
                    $weight = (float) ($question->default_weight ?? 1);
                    $total += $weight > 0 ? $weight : 1;
                    break;

                case 'audio':
                    $total += (float) ($question->default_weight ?? 0);
                    break;

                default:
                    $options = $question->questionOptions;
                    switch ($type_subtest) {
                        case 'tkp':
                            $maxWeight = $options->max(function ($opt) {
                                return (float) ($opt->weight ?? 0);
                            });
                            $total += $maxWeight ?? 0;
                            break;

                        case 'twk':
                        case 'tiu':
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 5;
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

    public function markPlayed($id_package, $id_tryout, $question_id)
    {
        $userId = Auth::id();

        $answerDetail = UserAnswerDetail::where('question_id', $question_id)
            ->whereHas('userAnswer', function ($query) use ($userId, $id_tryout) {
                $query->where('user_id', $userId)
                    ->where('tryout_id', $id_tryout);
            })
            ->first();

        if (!$answerDetail) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        if ($answerDetail->is_played) {
            return response()->json(['status' => 'already_played']);
        }

        $answerDetail->is_played = true;
        $answerDetail->save();

        return response()->json(['status' => 'success']);
    }
}
