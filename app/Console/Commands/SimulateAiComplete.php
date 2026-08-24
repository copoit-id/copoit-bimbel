<?php

namespace App\Console\Commands;

use App\Models\EssayCorrectionJob;
use App\Models\UserAnswerDetail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SimulateAiComplete extends Command
{
    protected $signature = 'essay:simulate-complete {--job-id= : Specific job ID} {--all : Complete all pending jobs}';
    protected $description = 'Simulate AI completion for pending jobs (local dev only)';

    public function handle(): int
    {
        $query = EssayCorrectionJob::whereIn('status', ['pending', 'queued', 'processing']);
        
        if ($this->option('job-id')) {
            $query->where('id', $this->option('job-id'));
        }
        
        $jobs = $query->get();
        
        if ($jobs->isEmpty()) {
            $this->info('No pending jobs found.');
            return 0;
        }
        
        $this->info("Found {$jobs->count()} pending job(s). Processing...");
        
        foreach ($jobs as $job) {
            $this->processJob($job);
        }
        
        $this->info('Done!');
        return 0;
    }
    
    private function processJob(EssayCorrectionJob $job): void
    {
        $this->info("Processing job {$job->id}...");
        
        // Cari essay yang pending untuk job ini
        $detail = UserAnswerDetail::whereHas('userAnswer', fn($q) => $q->where('user_answer_id', $job->user_answer_id))
            ->whereHas('question', fn($q) => $q->where('question_type', 'essay'))
            ->whereRaw("JSON_EXTRACT(answer_json, '$.pending_review') = true")
            ->with('question')
            ->first();
        
        if (!$detail) {
            $this->warn("  No pending detail found for job {$job->id}");
            $job->update(['status' => 'failed', 'error_message' => 'No pending essay found']);
            return;
        }
        
        // Simulate AI similarity (random 0.5 - 0.95)
        $similarity = round(mt_rand(50, 95) / 100, 2);
        $threshold = $job->threshold ?: 0.6;
        $isCorrect = $similarity >= $threshold;
        
        // Hitung skor
        $question = $detail->question;
        $scoreObtained = $question ? $question->calculateEssayScore($similarity) : ($isCorrect ? 1 : 0);
        
        // Update detail
        $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $answerMeta['pending_review'] = false;
        $answerMeta['reviewed_at'] = Carbon::now('Asia/Jakarta')->toIso8601String();
        $answerMeta['auto_corrected'] = true;
        $answerMeta['ai_similarity'] = $similarity;
        $answerMeta['ai_job_id'] = $job->ai_job_id;
        $answerMeta['ai_method'] = $job->method ?? 'semantic';
        $answerMeta['score_obtained'] = $scoreObtained;
        $answerMeta['ai_corrected_at'] = now()->toIso8601String();
        
        $detail->update([
            'is_correct' => $isCorrect,
            'answer_json' => $answerMeta,
        ]);
        
        // Recalculate stats
        if ($detail->userAnswer) {
            $this->recalculateStats($detail->userAnswer);
        }
        
        // Update job
        $job->update([
            'status' => 'completed',
            'processed_essays' => 1,
            'correct_count' => $isCorrect ? 1 : 0,
            'incorrect_count' => $isCorrect ? 0 : 1,
            'total_similarity_score' => $similarity * 100,
            'completed_at' => Carbon::now(),
        ]);
        
        $this->info("  ✓ Completed! Similarity: {$similarity}, Score: {$scoreObtained}, Correct: " . ($isCorrect ? 'Yes' : 'No'));
    }
    
    private function recalculateStats($userAnswer): void
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
