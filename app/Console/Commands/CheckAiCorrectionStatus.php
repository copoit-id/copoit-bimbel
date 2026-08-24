<?php

namespace App\Console\Commands;

use App\Models\EssayCorrectionJob;
use App\Models\UserAnswerDetail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckAiCorrectionStatus extends Command
{
    protected $signature = 'essay:check-ai-status {--job-id= : Specific job ID to check}';
    protected $description = 'Check AI correction status and process callbacks for queued jobs';

    public function handle(): int
    {
        $query = EssayCorrectionJob::whereIn('status', ['queued', 'processing']);
        
        if ($this->option('job-id')) {
            $query->where('id', $this->option('job-id'));
        }
        
        $jobs = $query->get();
        
        if ($jobs->isEmpty()) {
            $this->info('No queued/processing jobs found.');
            return 0;
        }
        
        $aiServiceUrl = rtrim(config('services.ai_similarity.url'), '/');
        
        foreach ($jobs as $job) {
            $this->info("Checking job {$job->id}, AI Job ID: {$job->ai_job_id}");
            
            if (!$job->ai_job_id) {
                $this->warn("  No AI Job ID, skipping...");
                continue;
            }
            
            try {
                // Cek status ke AI service
                $response = Http::timeout(10)->get("{$aiServiceUrl}/api/v1/similarity/job/{$job->ai_job_id}");
                
                if ($response->failed()) {
                    $this->error("  Failed to check status: " . $response->body());
                    continue;
                }
                
                $data = $response->json();
                $status = $data['status'] ?? 'UNKNOWN';
                
                $this->info("  AI Status: {$status}");
                
                if ($status === 'COMPLETED') {
                    $this->processCompletedJob($job, $data);
                } elseif ($status === 'FAILED') {
                    $job->update([
                        'status' => 'failed',
                        'error_message' => $data['error'] ?? 'AI processing failed',
                        'completed_at' => Carbon::now(),
                    ]);
                    $this->info("  Marked as failed");
                } else {
                    $this->info("  Still processing...");
                }
                
            } catch (\Exception $e) {
                $this->error("  Error: " . $e->getMessage());
                Log::error("Check AI status failed for job {$job->id}: " . $e->getMessage());
            }
        }
        
        return 0;
    }
    
    private function processCompletedJob(EssayCorrectionJob $job, array $data): void
    {
        $threshold = $job->threshold ?: config('services.ai_similarity.threshold', 0.6);
        $results = $data['results'] ?? [];
        
        $correctCount = 0;
        $incorrectCount = 0;
        $totalSimilarity = 0;
        
        foreach ($results as $result) {
            $detailId = $result['question_id'];
            $similarity = $result['similarity'];
            $isCorrect = $similarity >= $threshold;
            
            $detail = UserAnswerDetail::find($detailId);
            if (!$detail) continue;
            
            $question = $detail->question;
            if (!$question) continue;
            
            // Hitung skor
            $scoreObtained = $question->calculateEssayScore($similarity);
            
            if ($isCorrect) {
                $correctCount++;
            } else {
                $incorrectCount++;
            }
            $totalSimilarity += $similarity;
            
            // Update detail
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
            $answerMeta['pending_review'] = false;
            $answerMeta['reviewed_at'] = Carbon::now('Asia/Jakarta')->toIso8601String();
            $answerMeta['auto_corrected'] = true;
            $answerMeta['ai_similarity'] = $similarity;
            $answerMeta['ai_job_id'] = $job->ai_job_id;
            $answerMeta['ai_method'] = $data['method'] ?? 'semantic';
            $answerMeta['score_obtained'] = $scoreObtained;
            
            $detail->update([
                'is_correct' => $isCorrect,
                'answer_json' => $answerMeta,
            ]);
            
            // Recalculate stats
            if ($detail->userAnswer) {
                $this->recalculateSubtestStats($detail->userAnswer);
            }
        }
        
        $processedCount = count($results);
        $avgSimilarity = $processedCount > 0 ? ($totalSimilarity / $processedCount) : 0;
        
        $job->update([
            'status' => 'completed',
            'processed_essays' => $processedCount,
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'total_similarity_score' => $avgSimilarity * 100,
            'completed_at' => Carbon::now(),
        ]);
        
        $this->info("  Completed! Processed: {$processedCount}, Correct: {$correctCount}");
    }
    
    private function recalculateSubtestStats($userAnswer): void
    {
        $details = \App\Models\UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->with('question')
            ->get();
        
        $correct = 0;
        $wrong = 0;
        $score = 0;
        
        foreach ($details as $d) {
            $meta = is_array($d->answer_json) ? $d->answer_json : [];
            if (!empty($meta['pending_review'])) continue;
            
            if ($d->is_correct) {
                $correct++;
            } else {
                $wrong++;
            }
            
            if (isset($meta['score_obtained'])) {
                $score += (float) $meta['score_obtained'];
            } elseif ($d->is_correct) {
                $score += 1;
            }
        }
        
        $total = max(1, $details->count());
        $percentage = ($score / $total) * 100;
        
        $userAnswer->update([
            'correct_answers' => $correct,
            'wrong_answers' => $wrong,
            'score' => $percentage,
        ]);
    }
}
