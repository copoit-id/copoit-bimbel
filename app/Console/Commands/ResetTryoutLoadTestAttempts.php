<?php

namespace App\Console\Commands;

use App\Models\Tryout;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResetTryoutLoadTestAttempts extends Command
{
    protected $signature = 'test:reset-tryout-attempts
        {--batch= : Required batch identifier produced by test:create-tryout-users}
        {--tryout= : Required tryout ID whose attempts will be reset}
        {--domain=loadtest.invalid : Email domain used when creating the batch}
        {--force : Reset the tagged test attempts without confirmation}';

    protected $description = 'Reset one tryout for tagged load-test accounts while preserving accounts and credential CSV';

    public function handle(): int
    {
        $inputBatch = (string) $this->option('batch');
        $batch = Str::slug($inputBatch);
        $tryoutId = filter_var($this->option('tryout'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $domain = strtolower(trim((string) $this->option('domain')));

        if ($batch === '' || $batch !== $inputBatch || strlen($batch) > 40) {
            $this->error('Provide the exact, URL-safe --batch identifier shown by the create command.');

            return self::FAILURE;
        }

        if ($tryoutId === false || ! Tryout::withoutGlobalScopes()->whereKey($tryoutId)->exists()) {
            $this->error('Provide an existing positive --tryout ID.');

            return self::FAILURE;
        }

        if (! filter_var('loadtest@'.$domain, FILTER_VALIDATE_EMAIL)) {
            $this->error('The --domain option must be a valid email domain.');

            return self::FAILURE;
        }

        $userIds = User::query()
            ->where('email', 'like', $batch.'-%@'.$domain)
            ->pluck('id');

        if ($userIds->isEmpty()) {
            $this->error("No test accounts were found for batch {$batch}.");

            return self::FAILURE;
        }

        $answers = DB::table('user_answers')
            ->whereIn('user_id', $userIds)
            ->where('tryout_id', $tryoutId);
        $answerCount = $answers->count();
        $snapshotPaths = $this->snapshotPaths($userIds, (int) $tryoutId);

        $this->warn("This resets {$answerCount} tryout subtest records for {$userIds->count()} accounts in batch {$batch}. Accounts and credentials.csv are kept.");
        if (! $this->option('force') && ! $this->confirm('Continue?', false)) {
            $this->warn('Nothing was reset.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($userIds, $tryoutId): void {
            DB::table('user_answers')
                ->whereIn('user_id', $userIds)
                ->where('tryout_id', $tryoutId)
                ->delete();

            if (Schema::hasTable('user_tryout_access')) {
                DB::table('user_tryout_access')
                    ->whereIn('user_id', $userIds)
                    ->where('tryout_id', $tryoutId)
                    ->update([
                        'status' => 'not_started',
                        'started_at' => null,
                        'completed_at' => null,
                        'progress_percentage' => 0,
                        'updated_at' => now(),
                    ]);
            }

            if (Schema::hasTable('tryout_user_time_adjustments')) {
                DB::table('tryout_user_time_adjustments')
                    ->whereIn('user_id', $userIds)
                    ->where('tryout_id', $tryoutId)
                    ->delete();
            }

            if (Schema::hasTable('proctoring_snapshots')) {
                DB::table('proctoring_snapshots')
                    ->whereIn('user_id', $userIds)
                    ->where('tryout_id', $tryoutId)
                    ->delete();
            }

            if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                DB::table('sessions')->whereIn('user_id', $userIds)->delete();
            }
        });

        if ($snapshotPaths->isNotEmpty()) {
            Storage::disk('public')->delete($snapshotPaths->all());
        }

        $this->info("Reset batch {$batch} for tryout {$tryoutId}. The accounts and credential CSV were preserved.");

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $userIds
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function snapshotPaths($userIds, int $tryoutId)
    {
        if (! Schema::hasTable('proctoring_snapshots') || ! Schema::hasColumn('proctoring_snapshots', 'file_path')) {
            return collect();
        }

        return DB::table('proctoring_snapshots')
            ->whereIn('user_id', $userIds)
            ->where('tryout_id', $tryoutId)
            ->pluck('file_path')
            ->filter();
    }
}
