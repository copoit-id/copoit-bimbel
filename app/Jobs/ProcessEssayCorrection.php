<?php

namespace App\Jobs;

use App\Models\EssayCorrectionJob;
use App\Models\Question;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessEssayCorrection implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 menit cukup untuk kirim request
    public $tries = 3;

    protected $jobId;

    public function __construct(int $jobId)
    {
        $this->jobId = $jobId;
    }

    public function handle(): void
    {
        $correctionJob = EssayCorrectionJob::find($this->jobId);
        
        if (!$correctionJob) {
            Log::error("EssayCorrectionJob #{$this->jobId} not found");
            return;
        }

        try {
            DB::beginTransaction();

            // Update status jadi processing
            $correctionJob->update([
                'status' => 'processing',
                'started_at' => Carbon::now(),
            ]);

            DB::commit();

            // PER ATTEMPT: Ambil essay yang pending untuk user_answer_id spesifik ini
            $query = UserAnswerDetail::query()
                ->join('user_answers', 'user_answer_details.user_answer_id', '=', 'user_answers.user_answer_id')
                ->join('questions', 'user_answer_details.question_id', '=', 'questions.question_id')
                ->where('user_answers.tryout_id', $correctionJob->tryout_id)
                ->where('questions.question_type', 'essay')
                ->whereRaw("JSON_EXTRACT(user_answer_details.answer_json, '$.pending_review') = true")
                ->with(['question', 'userAnswer.user']);

            // PER ATTEMPT: Filter by user_answer_id jika ada (spesifik attempt)
            if ($correctionJob->user_answer_id) {
                $query->where('user_answers.user_answer_id', $correctionJob->user_answer_id);
            } elseif ($correctionJob->user_id) {
                // Fallback: filter by user_id (untuk backward compatibility)
                $query->where('user_answers.user_id', $correctionJob->user_id);
            }

            $essays = $query->select('user_answer_details.*')->get();
            
            $total = $essays->count();
            
            if ($total === 0) {
                $correctionJob->update([
                    'status' => 'completed',
                    'total_essays' => 0,
                    'processed_essays' => 0,
                    'completed_at' => Carbon::now(),
                ]);
                Log::info("EssayCorrectionJob #{$this->jobId}: No pending essays found");
                return;
            }

            // Update total count
            $correctionJob->update(['total_essays' => $total]);

            Log::info("EssayCorrectionJob #{$this->jobId}: Processing {$total} essays");

            // Cek apakah pakai AI atau lokal
            $useAi = config('services.ai_similarity.enabled', true);
            $aiServiceUrl = config('services.ai_similarity.url');
            
            // Kalau AI URL tidak ada, fallback ke lokal
            if (!$aiServiceUrl || empty($aiServiceUrl)) {
                $useAi = false;
                Log::warning("EssayCorrectionJob #{$this->jobId}: AI URL not configured, using local");
            }
            
            if ($useAi) {
                // Cek apakah di local/development
                $isLocal = app()->environment('local');
                
                if ($isLocal) {
                    // LOCAL MODE: Process langsung tanpa webhook (simulate AI)
                    $this->processLocalAiSimulation($correctionJob, $essays);
                } else {
                    // PRODUCTION MODE: Full async dengan webhook
                    $this->dispatchToAiAndFinish($correctionJob, $essays);
                }
            } else {
                // Gunakan metode lokal (string matching) - ini synchronous
                $this->processLocal($correctionJob, $essays);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("EssayCorrectionJob #{$this->jobId} failed: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            $correctionJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => Carbon::now(),
            ]);
            throw $e;
        }
    }

    /**
     * FULL ASYNC: Kirim ke AI Service lalu selesai. Webhook akan handle hasil.
     */
    protected function dispatchToAiAndFinish(EssayCorrectionJob $correctionJob, $essays): void
    {
        $aiServiceUrl = rtrim(config('services.ai_similarity.url'), '/');
        $timeout = config('services.ai_similarity.timeout', 20);
        $callbackUrl = config('services.ai_similarity.callback_url');
        $method = $correctionJob->method ?: 'semantic';

        Log::info("EssayCorrectionJob #{$this->jobId}: [FULL ASYNC] Dispatching to AI Service at {$aiServiceUrl}");

        // Format answers untuk AI Service - Group by user_id
        $groupedByUser = $essays->groupBy(fn($e) => $e->userAnswer?->user_id ?? 'unknown');
        
        $totalDispatched = 0;
        $aiJobIds = [];

        foreach ($groupedByUser as $userId => $userEssays) {
            if ($userId === 'unknown') {
                Log::warning("EssayCorrectionJob #{$this->jobId}: Skipping essays with unknown user");
                continue;
            }

            $answers = [];

            foreach ($userEssays as $essay) {
                $question = $essay->question;
                if (!$question) continue;

                // Ambil kunci jawaban
                $kunci = $question->metadata['correct_answer'] ?? 
                         ($question->metadata['short_answer']['expected_answers'][0] ?? null) ?? 
                         $question->answer_key ?? '';

                if (empty($kunci)) {
                    Log::warning("EssayCorrectionJob #{$this->jobId}: No answer key for question {$question->question_id}");
                    continue;
                }

                $answers[] = [
                    'question_id' => (string) $essay->user_answer_detail_id,
                    'kunci' => $kunci,
                    'jawaban' => $essay->answer_text ?? '',
                ];
            }

            if (empty($answers)) {
                continue;
            }

            Log::info("EssayCorrectionJob #{$this->jobId}: Sending " . count($answers) . " answers for user {$userId}");

            // Kirim ke AI Service - Async
            try {
                $payload = [
                    'user_id' => (string) $userId,
                    'method' => $method,
                    'answers' => $answers,
                ];

                if ($callbackUrl) {
                    $payload['callback_url'] = $callbackUrl;
                }

                $response = Http::timeout($timeout)->post("{$aiServiceUrl}/api/v1/similarity/batch-async", $payload);

                if ($response->failed()) {
                    Log::error("EssayCorrectionJob #{$this->jobId}: AI Service error for user {$userId}: " . $response->body());
                    continue;
                }

                $result = $response->json();
                $aiJobId = $result['job_id'] ?? null;

                if ($aiJobId) {
                    $aiJobIds[] = $aiJobId;
                    $totalDispatched += count($answers);
                    Log::info("EssayCorrectionJob #{$this->jobId}: AI Job ID: {$aiJobId} for user {$userId}");
                }

            } catch (\Exception $e) {
                Log::error("EssayCorrectionJob #{$this->jobId}: Failed to send to AI Service for user {$userId}: " . $e->getMessage());
            }
        }

        // Update job status ke QUEUED dan simpan AI Job IDs
        // Job ini SELESAI di sini. Webhook akan update status ke COMPLETED nanti.
        if (count($aiJobIds) > 0) {
            $correctionJob->update([
                'ai_job_id' => implode(',', $aiJobIds), // Simpan multiple AI job IDs
                'status' => 'queued',
                'queued_at' => Carbon::now(),
                'processed_essays' => 0, // Akan diupdate oleh webhook
            ]);

            Log::info("EssayCorrectionJob #{$this->jobId}: [FULL ASYNC] Dispatched " . count($aiJobIds) . " AI jobs. Local job QUEUED. Webhook will complete.");
        } else {
            // Tidak ada yang terkirim, mark as failed
            $correctionJob->update([
                'status' => 'failed',
                'error_message' => 'Failed to dispatch any essays to AI service',
                'completed_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * LOCAL DEV ONLY: Simulate AI processing tanpa webhook
     * Process langsung dengan random similarity (tapi konsisten)
     */
    protected function processLocalAiSimulation(EssayCorrectionJob $correctionJob, $essays): void
    {
        $threshold = $correctionJob->threshold ?: config('services.ai_similarity.threshold', 0.6);
        
        $correctionJob->update([
            'status' => 'processing',
            'started_at' => Carbon::now(),
        ]);
        
        Log::info("EssayCorrectionJob #{$this->jobId}: [LOCAL SIMULATION] Processing " . $essays->count() . " essays");
        
        // Sleep 15 detik biar kelihatan "Menunggu" di pembahasan
        // User bisa lihat status "Menunggu" baru kemudian berubah jadi nilai
        sleep(15);
        
        $processed = 0;
        $correctCount = 0;
        $incorrectCount = 0;
        $totalSimilarity = 0;
        
        foreach ($essays as $detail) {
            try {
                $question = $detail->question;
                if (!$question) {
                    Log::warning("EssayCorrectionJob #{$this->jobId}: Question not found for detail {$detail->user_answer_detail_id}");
                    continue;
                }
                
                // Simulate AI similarity (deterministic berdasarkan jawaban)
                $userAnswer = $detail->answer_text ?? '';
                $correctAnswer = $question->metadata['short_answer']['expected_answers'][0] ?? '';
                
                // Hitung similarity sederhana untuk determinism
                similar_text(
                    strtolower(trim($userAnswer)), 
                    strtolower(trim($correctAnswer)), 
                    $percent
                );
                
                // Tambah random factor tapi tetap deterministik
                $baseSimilarity = $percent / 100;
                $randomFactor = (crc32($userAnswer) % 20) / 100; // -0.2 sampai 0.2
                $similarity = max(0, min(1, $baseSimilarity + $randomFactor));
                $similarity = round($similarity, 2);
                
                $isCorrect = $similarity >= $threshold;
                
                // Hitung skor
                $scoreObtained = $question->calculateEssayScore($similarity);
                
                if ($isCorrect) {
                    $correctCount++;
                } else {
                    $incorrectCount++;
                }
                $totalSimilarity += $similarity;
                $processed++;
                
                // Update answer
                $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
                $answerMeta['pending_review'] = false;
                $answerMeta['reviewed_at'] = Carbon::now('Asia/Jakarta')->toIso8601String();
                $answerMeta['auto_corrected'] = true;
                $answerMeta['ai_similarity'] = $similarity;
                $answerMeta['ai_method'] = $correctionJob->method ?? 'semantic';
                $answerMeta['score_obtained'] = $scoreObtained;
                $answerMeta['local_simulation'] = true; // Tandai sebagai simulasi
                
                $detail->update([
                    'is_correct' => $isCorrect,
                    'answer_json' => $answerMeta,
                ]);
                
                Log::info("EssayCorrectionJob #{$this->jobId}: Processed detail {$detail->user_answer_detail_id}, similarity: {$similarity}, score: {$scoreObtained}");
                
                // Recalculate stats
                if ($detail->userAnswer) {
                    $this->recalculateSubtestStats($detail->userAnswer);
                }
                
            } catch (\Exception $e) {
                Log::error("EssayCorrectionJob #{$this->jobId}: Error processing detail {$detail->user_answer_detail_id}: " . $e->getMessage());
                $incorrectCount++;
                $processed++;
            }
        }
        
        // Update job final status
        $avgSimilarity = $processed > 0 ? ($totalSimilarity / $processed) : 0;
        
        $correctionJob->update([
            'status' => 'completed',
            'processed_essays' => $processed,
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'total_similarity_score' => $avgSimilarity * 100,
            'completed_at' => Carbon::now(),
        ]);
        
        Log::info("EssayCorrectionJob #{$this->jobId}: [LOCAL SIMULATION] Completed! Processed: {$processed}, Correct: {$correctCount}, Incorrect: {$incorrectCount}");
    }

    /**
     * Proses dengan metode lokal (string matching) - Synchronous
     */
    protected function processLocal(EssayCorrectionJob $correctionJob, $essays): void
    {
        $threshold = config('services.ai_similarity.threshold', 0.6);

        $correctionJob->update([
            'status' => 'processing',
            'method' => 'local',
        ]);

        Log::info("EssayCorrectionJob #{$this->jobId}: Processing locally with threshold {$threshold}");

        $processed = 0;
        $correctCount = 0;
        $incorrectCount = 0;
        $totalSimilarity = 0;

        foreach ($essays as $detail) {
            try {
                $result = $this->processEssayLocal($detail, $threshold);
                
                if ($result['is_correct']) {
                    $correctCount++;
                } else {
                    $incorrectCount++;
                }
                $totalSimilarity += $result['similarity'];
                $processed++;

                // Update progress setiap 5 essay
                if ($processed % 5 === 0) {
                    $correctionJob->update([
                        'processed_essays' => $processed,
                        'correct_count' => $correctCount,
                        'incorrect_count' => $incorrectCount,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("EssayCorrectionJob #{$this->jobId}: Error processing essay #{$detail->user_answer_detail_id}: " . $e->getMessage());
                $incorrectCount++;
                $processed++;
            }
        }

        // Update final status
        $correctionJob->update([
            'status' => 'completed',
            'processed_essays' => $processed,
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'total_similarity_score' => $processed > 0 ? ($totalSimilarity / $processed) : 0,
            'completed_at' => Carbon::now(),
        ]);

        Log::info("EssayCorrectionJob #{$this->jobId}: Local processing completed. Processed: {$processed}, Correct: {$correctCount}, Incorrect: {$incorrectCount}");
    }

    /**
     * Proses single essay dengan metode lokal
     * @return array ['similarity' => float, 'is_correct' => bool]
     */
    protected function processEssayLocal(UserAnswerDetail $detail, float $threshold): array
    {
        $question = $detail->question;
        $userAnswer = $detail->answer_text ?? '';
        
        if (!$question) {
            throw new \Exception("Question not found");
        }

        // Ambil jawaban benar dari metadata
        $correctAnswer = $question->metadata['correct_answer'] ?? 
                         ($question->metadata['short_answer']['expected_answers'][0] ?? null) ?? 
                         $question->answer_key ?? '';

        if (empty($correctAnswer)) {
            Log::warning("EssayCorrectionJob #{$this->jobId}: No correct answer for question {$question->question_id}");
        }

        // Koreksi dengan similar_text
        $similarity = $this->calculateSimilarity($userAnswer, $correctAnswer);
        $isCorrect = $similarity >= $threshold;

        // Hitung skor berdasarkan scoring mode
        $scoreObtained = $question->calculateEssayScore($similarity);

        $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $answerMeta['pending_review'] = false;
        $answerMeta['reviewed_by'] = null;
        $answerMeta['reviewed_at'] = Carbon::now('Asia/Jakarta')->toIso8601String();
        $answerMeta['auto_corrected'] = true;
        $answerMeta['local_similarity'] = $similarity;
        $answerMeta['correct_answer'] = $correctAnswer;
        $answerMeta['method'] = 'local';
        $answerMeta['score_obtained'] = $scoreObtained;
        $answerMeta['essay_scoring_mode'] = $question->essay_scoring_mode;
        $answerMeta['essay_score_correct'] = $question->getEssayScoreCorrect();
        $answerMeta['essay_score_wrong'] = $question->getEssayScoreWrong();

        $detail->update([
            'is_correct' => $isCorrect,
            'answer_json' => $answerMeta,
        ]);

        if ($detail->userAnswer) {
            $this->recalculateSubtestStats($detail->userAnswer);
        }

        return [
            'similarity' => $similarity,
            'is_correct' => $isCorrect,
            'score_obtained' => $scoreObtained,
        ];
    }

    protected function calculateSimilarity(string $userAnswer, string $correctAnswer): float
    {
        $user = $this->normalizeText($userAnswer);
        $correct = $this->normalizeText($correctAnswer);

        if (empty($user) || empty($correct)) {
            return 0.0;
        }

        similar_text($user, $correct, $percent);
        return round($percent / 100, 2);
    }

    protected function normalizeText(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        $text = preg_replace('/[^\w\s]/', '', $text);
        return $text;
    }

    /**
     * Recalculate subtest stats dengan essay scoring
     */
    protected function recalculateSubtestStats(UserAnswer $userAnswer): void
    {
        $userAnswerDetails = UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->with(['questionOption', 'question'])
            ->get();

        $totalQuestions = Question::where('tryout_detail_id', $userAnswer->tryout_detail_id)->count();
        $correctAnswers = 0;
        $wrongAnswers = 0;
        $totalScore = 0;

        foreach ($userAnswerDetails as $d) {
            $question = $d->question;
            if (!$question) continue;

            $questionType = $question->question_type ?? 'multiple_choice';
            $pendingReview = false;
            $answerMeta = is_array($d->answer_json) ? $d->answer_json : [];
            if (isset($answerMeta['pending_review'])) {
                $pendingReview = (bool) $answerMeta['pending_review'];
            }

            switch ($questionType) {
                case 'essay':
                    if ($pendingReview) continue 2;
                    
                    if ($d->is_correct) {
                        $correctAnswers++;
                    } else {
                        $wrongAnswers++;
                    }

                    // Gunakan score_obtained kalau ada
                    if (isset($answerMeta['score_obtained'])) {
                        $totalScore += (float) $answerMeta['score_obtained'];
                    } else {
                        // Fallback ke binary scoring
                        $weight = $question->getEssayScoreCorrect();
                        $totalScore += $d->is_correct ? $weight : $question->getEssayScoreWrong();
                    }
                    break;

                default:
                    if ($d->questionOption) {
                        if ($d->is_correct) {
                            $correctAnswers++;
                        } else {
                            $wrongAnswers++;
                        }
                        $w = (float) ($d->questionOption->weight ?? 0);
                        $totalScore += $d->is_correct ? ($w > 0 ? $w : 1) : 0;
                    }
                    break;
            }
        }

        $unanswered = max(0, $totalQuestions - $userAnswerDetails->count());
        
        // Hitung max score - include essay scoring
        $maxScore = $this->getMaxPossibleScoreForDetail($userAnswer->tryout_detail_id);
        $percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;

        $userAnswer->update([
            'correct_answers' => $correctAnswers,
            'wrong_answers' => $wrongAnswers,
            'unanswered' => $unanswered,
            'score' => $percentage,
        ]);

        Log::info("Recalculated stats for user_answer {$userAnswer->user_answer_id}: score={$percentage}, correct={$correctAnswers}, wrong={$wrongAnswers}");
    }

    /**
     * Hitung max possible score dengan essay scoring
     */
    protected function getMaxPossibleScoreForDetail(int $tryoutDetailId): float
    {
        $questions = Question::where('tryout_detail_id', $tryoutDetailId)->get();

        if ($questions->isEmpty()) {
            return 0;
        }

        $total = 0;

        foreach ($questions as $question) {
            $questionType = $question->question_type ?? 'multiple_choice';

            switch ($questionType) {
                case 'essay':
                    // Max score untuk essay adalah essay_score_correct
                    $total += $question->getEssayScoreCorrect();
                    break;

                default:
                    $total += (float) ($question->default_weight ?? 1);
                    break;
            }
        }

        return $total;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("EssayCorrectionJob #{$this->jobId} failed: " . $exception->getMessage());
        
        $correctionJob = EssayCorrectionJob::find($this->jobId);
        if ($correctionJob) {
            $correctionJob->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'completed_at' => Carbon::now(),
            ]);
        }
    }
}
