<?php

namespace App\Http\Controllers;

use App\Models\EssayCorrectionJob;
use App\Models\UserAnswerDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle callback dari AI Similarity Service
     * 
     * POST /api/webhook/ai-callback
     */
    public function aiCallback(Request $request)
    {
        $secret = (string) $request->header('X-Callback-Secret', '');
        $expectedSecret = (string) config('services.ai_similarity.callback_secret', '');

        if ($expectedSecret === '' || $secret === '' || ! hash_equals($expectedSecret, $secret)) {
            Log::warning('AI Callback: invalid or missing callback secret.');

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validasi input
        try {
            $data = $request->validate([
                'job_id' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._:-]+$/'],
                'user_id' => ['required', 'integer'],
                'status' => 'required|string|in:COMPLETED,FAILED',
                'results' => 'required_if:status,COMPLETED|array|max:500',
                'results.*.question_id' => 'required|integer',
                'results.*.similarity' => 'required|numeric|between:0,1',
                'results.*.score' => 'required|integer|between:0,100',
                'total_score' => 'required_if:status,COMPLETED|numeric',
                'processed_count' => 'required_if:status,COMPLETED|integer|min:0|max:500',
                'processing_time_ms' => 'nullable|integer|min:0|max:3600000',
                'method' => 'nullable|string|max:100',
                'error' => 'required_if:status,FAILED|string|max:1000',
            ]);
        } catch (\Exception $e) {
            Log::error('AI Callback validation error: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 422);
        }

        try {
            DB::beginTransaction();

            $jobId = $data['job_id'];
            $escapedJobId = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $jobId);
            $job = EssayCorrectionJob::where('status', 'queued')
                ->where(function ($query) use ($jobId, $escapedJobId) {
                    $query->where('ai_job_id', $jobId)
                        ->orWhere('ai_job_id', 'like', $escapedJobId . ',%')
                        ->orWhere('ai_job_id', 'like', '%,' . $escapedJobId)
                        ->orWhere('ai_job_id', 'like', '%,' . $escapedJobId . ',%');
                })
                ->first();

            if (!$job) {
                Log::warning("AI Callback: Job not found for ai_job_id: {$data['job_id']} or job not in queued state");
                DB::rollBack();
                return response()->json(['error' => 'Job not found'], 404);
            }

            if ((int) $job->user_id !== (int) $data['user_id']) {
                Log::warning('AI Callback: callback user does not match correction job.', [
                    'job_id' => $job->getKey(),
                ]);
                DB::rollBack();

                return response()->json(['error' => 'Invalid callback payload'], 422);
            }

            Log::info("AI Callback: Found local job {$job->id} for ai_job_id: {$data['job_id']}");

            // Handle failed status
            if ($data['status'] === 'FAILED') {
                $job->update([
                    'status' => 'failed',
                    'error_message' => $data['error'] ?? 'AI processing failed',
                    'completed_at' => Carbon::now(),
                ]);

                DB::commit();
                Log::info("AI Callback: Job {$job->id} marked as failed");
                return response()->json(['status' => 'received']);
            }

            // Process results
            $threshold = $job->threshold ?: config('services.ai_similarity.threshold', 0.6);
            $correctCount = 0;
            $incorrectCount = 0;
            $processedCount = 0;
            $totalSimilarity = 0;

            foreach ($data['results'] as $result) {
                $detailId = $result['question_id'];
                $similarity = $result['similarity'];
                $isCorrect = $similarity >= $threshold;

                $detail = UserAnswerDetail::with('question')
                    ->whereKey($detailId)
                    ->whereHas('userAnswer', function ($query) use ($job) {
                        $query->where('user_answer_id', $job->user_answer_id)
                            ->where('user_id', $job->user_id);
                    })
                    ->first();
                
                if (!$detail) {
                    Log::warning("AI Callback: UserAnswerDetail not found: {$detailId}");
                    continue;
                }

                $question = $detail->question;
                if (!$question) {
                    Log::warning("AI Callback: Question not found for detail: {$detailId}");
                    continue;
                }

                $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
                if (($answerMeta['ai_job_id'] ?? null) === $data['job_id'] && ($answerMeta['pending_review'] ?? true) === false) {
                    continue;
                }

                // Hitung skor berdasarkan essay scoring mode
                $scoreObtained = $question->calculateEssayScore($similarity);

                if ($isCorrect) {
                    $correctCount++;
                } else {
                    $incorrectCount++;
                }
                $totalSimilarity += $similarity;
                $processedCount++;

                // Update answer
                $answerMeta['pending_review'] = false;
                $answerMeta['reviewed_by'] = null; // AI
                $answerMeta['reviewed_at'] = Carbon::now('Asia/Jakarta')->toIso8601String();
                $answerMeta['auto_corrected'] = true;
                $answerMeta['ai_similarity'] = $similarity;
                $answerMeta['ai_score'] = $result['score'];
                $answerMeta['ai_job_id'] = $data['job_id'];
                $answerMeta['ai_method'] = $data['method'] ?? 'semantic';
                $answerMeta['threshold'] = $threshold;
                $answerMeta['score_obtained'] = $scoreObtained;
                $answerMeta['essay_scoring_mode'] = $question->essay_scoring_mode;
                $answerMeta['essay_score_correct'] = $question->getEssayScoreCorrect();
                $answerMeta['essay_score_wrong'] = $question->getEssayScoreWrong();
                $answerMeta['total_score_from_ai'] = $data['total_score'] ?? null;

                $detail->update([
                    'is_correct' => $isCorrect,
                    'answer_json' => $answerMeta,
                ]);

                Log::info("AI Callback: Updated detail {$detailId}, similarity: {$similarity}, score: {$scoreObtained}");

                // Recalculate user stats
                if ($detail->userAnswer) {
                    $this->recalculateSubtestStats($detail->userAnswer);
                }
            }

            // Update job status ke COMPLETED
            // Increment processed count (bisa multiple webhook untuk 1 local job)
            $newProcessed = $job->processed_essays + $processedCount;
            $newCorrect = $job->correct_count + $correctCount;
            $newIncorrect = $job->incorrect_count + $incorrectCount;
            
            // Hitung rata-rata similarity score
            $currentTotalScore = $job->total_similarity_score ? ($job->total_similarity_score * $job->processed_essays) : 0;
            $newTotalScore = $currentTotalScore + ($totalSimilarity * 100); // similarity 0-1 jadi 0-100
            $newAvgScore = $newProcessed > 0 ? ($newTotalScore / $newProcessed) : 0;

            $updateData = [
                'processed_essays' => $newProcessed,
                'correct_count' => $newCorrect,
                'incorrect_count' => $newIncorrect,
                'total_similarity_score' => $newAvgScore,
                'processing_time_ms' => ($job->processing_time_ms ?? 0) + ($data['processing_time_ms'] ?? 0),
            ];

            // Kalau semua sudah diproses, mark as completed
            if ($newProcessed >= $job->total_essays) {
                $updateData['status'] = 'completed';
                $updateData['completed_at'] = Carbon::now();
                Log::info("AI Callback: Job {$job->id} marked as COMPLETED");
            } else {
                Log::info("AI Callback: Job {$job->id} progress: {$newProcessed}/{$job->total_essays}");
            }

            $job->update($updateData);

            DB::commit();

            return response()->json([
                'status' => 'received',
                'processed' => $processedCount,
                'correct' => $correctCount,
                'incorrect' => $incorrectCount,
                'job_progress' => $newProcessed . '/' . $job->total_essays,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AI Callback error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle health check dari AI Service
     */
    public function healthCheck(Request $request)
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Recalculate subtest stats dengan essay scoring
     */
    private function recalculateSubtestStats($userAnswer): void
    {
        $userAnswerDetails = \App\Models\UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->with(['questionOption', 'question'])
            ->get();

        $totalQuestions = \App\Models\Question::where('tryout_detail_id', $userAnswer->tryout_detail_id)->count();
        $correctAnswers = 0;
        $wrongAnswers = 0;
        $totalScore = 0;
        $maxScore = 0;

        foreach ($userAnswerDetails as $d) {
            $question = $d->question;
            if (!$question) continue;

            $questionType = $question->question_type ?? 'multiple_choice';
            $pendingReview = false;
            $answerMeta = is_array($d->answer_json) ? $d->answer_json : [];
            if (isset($answerMeta['pending_review'])) {
                $pendingReview = (bool) $answerMeta['pending_review'];
            }

            if ($questionType === 'essay') {
                // Max score untuk essay adalah essay_score_correct
                $maxScore += $question->getEssayScoreCorrect();

                if ($pendingReview) continue;
                
                if ($d->is_correct) {
                    $correctAnswers++;
                } else {
                    $wrongAnswers++;
                }

                // Gunakan score_obtained kalau ada
                if (isset($answerMeta['score_obtained'])) {
                    $totalScore += (float) $answerMeta['score_obtained'];
                } else {
                    // Fallback ke binary
                    $totalScore += $d->is_correct ? $question->getEssayScoreCorrect() : $question->getEssayScoreWrong();
                }
            } else {
                $maxScore += (float) ($question->default_weight ?? 1);

                if ($d->questionOption) {
                    if ($d->is_correct) {
                        $correctAnswers++;
                    } else {
                        $wrongAnswers++;
                    }
                    $w = (float) ($d->questionOption->weight ?? 0);
                    $totalScore += $d->is_correct ? ($w > 0 ? $w : 1) : 0;
                }
            }
        }

        $unanswered = max(0, $totalQuestions - $userAnswerDetails->count());
        $percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;

        $userAnswer->update([
            'correct_answers' => $correctAnswers,
            'wrong_answers' => $wrongAnswers,
            'unanswered' => $unanswered,
            'score' => $percentage,
        ]);

        Log::info("Webhook recalculated stats for user_answer {$userAnswer->user_answer_id}: score={$percentage}");
    }
}
