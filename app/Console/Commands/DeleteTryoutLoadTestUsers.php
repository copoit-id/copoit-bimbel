<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DeleteTryoutLoadTestUsers extends Command
{
    protected $signature = 'test:delete-tryout-users
        {--batch= : Required batch identifier produced by test:create-tryout-users}
        {--domain=loadtest.invalid : Email domain used when creating the batch}
        {--force : Permanently delete the tagged test accounts without confirmation}';

    protected $description = 'Permanently delete one tagged load-test account batch and its dependent test data';

    public function handle(): int
    {
        $inputBatch = (string) $this->option('batch');
        $batch = Str::slug($inputBatch);
        $domain = strtolower(trim((string) $this->option('domain')));

        if ($batch === '' || $batch !== $inputBatch || strlen($batch) > 40) {
            $this->error('Provide the exact, URL-safe --batch identifier shown by the create command.');

            return self::FAILURE;
        }

        if (! filter_var('loadtest@'.$domain, FILTER_VALIDATE_EMAIL)) {
            $this->error('The --domain option must be a valid email domain.');

            return self::FAILURE;
        }

        $batchDirectory = storage_path("app/load-tests/{$batch}");
        $users = User::query()
            ->where('email', 'like', $batch.'-%@'.$domain)
            ->get(['id', 'email']);

        if ($users->isEmpty()) {
            if (File::isDirectory($batchDirectory)) {
                File::deleteDirectory($batchDirectory);
                $this->info("Deleted credential export for empty test batch {$batch}.");

                return self::SUCCESS;
            }

            $this->warn("No accounts were found for batch {$batch}.");

            return self::SUCCESS;
        }

        $userIds = $users->pluck('id');
        $answerCount = Schema::hasTable('user_answers')
            ? DB::table('user_answers')->whereIn('user_id', $userIds)->count()
            : 0;

        $this->warn("This permanently deletes {$users->count()} test accounts and {$answerCount} tryout subtest records from batch {$batch}.");
        if (! $this->option('force') && ! $this->confirm('Continue?', false)) {
            $this->warn('Nothing was deleted.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($userIds): void {
            $this->deleteForUsers('sessions', $userIds);
            $this->deleteForUsers('activity_logs', $userIds);
            $this->deleteForUsers('feedback_submissions', $userIds);
            $this->deleteForUsers('tryout_user_time_adjustments', $userIds);
            $this->deleteForUsers('essay_ai_usage_logs', $userIds);
            $this->deleteForUsers('essay_correction_jobs', $userIds);

            User::whereIn('id', $userIds)->delete();
        });

        File::deleteDirectory($batchDirectory);

        $this->info("Deleted test batch {$batch}. User-answer, access, leaderboard, dependent data, and the credential CSV were deleted.");

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $userIds
     */
    private function deleteForUsers(string $table, $userIds): void
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
            DB::table($table)->whereIn('user_id', $userIds)->delete();
        }
    }
}
