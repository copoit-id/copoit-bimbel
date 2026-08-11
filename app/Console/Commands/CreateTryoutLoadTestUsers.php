<?php

namespace App\Console\Commands;

use App\Models\Package;
use App\Models\Tryout;
use App\Models\User;
use App\Models\UserPackageAcces;
use App\Models\UserTryoutAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateTryoutLoadTestUsers extends Command
{
    protected $signature = 'test:create-tryout-users
        {--count=324 : Number of unique participant accounts to create (1-2000)}
        {--tryout= : Required tryout ID to grant access to}
        {--package=free : Use free for direct tryout access, or provide a package ID}
        {--batch= : Identifier used to tag every test account and its CSV file}
        {--domain=loadtest.invalid : Email domain reserved for the test accounts}
        {--force : Create the real accounts without an interactive confirmation}';

    protected $description = 'Create tagged accounts and a credentials CSV for a real tryout load test';

    public function handle(): int
    {
        $count = filter_var($this->option('count'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 2000],
        ]);
        $tryoutId = filter_var($this->option('tryout'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($count === false) {
            $this->error('The --count option must be an integer between 1 and 2000.');

            return self::FAILURE;
        }

        if ($tryoutId === false) {
            $this->error('Provide a valid tryout ID, for example: --tryout=8');

            return self::FAILURE;
        }

        $tryout = Tryout::withoutGlobalScopes()->find($tryoutId);
        if (! $tryout) {
            $this->error("Tryout {$tryoutId} was not found.");

            return self::FAILURE;
        }

        $packageOption = strtolower(trim((string) $this->option('package')));
        $package = null;
        if ($packageOption !== 'free') {
            if (! ctype_digit($packageOption) || (int) $packageOption < 1) {
                $this->error('The --package option must be free or a positive package ID.');

                return self::FAILURE;
            }

            $package = Package::find((int) $packageOption);
            if (! $package) {
                $this->error("Package {$packageOption} was not found.");

                return self::FAILURE;
            }
        }

        try {
            $batch = $this->batchName((string) $this->option('batch'), (int) $tryoutId);
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $domain = strtolower(trim((string) $this->option('domain')));
        if (! filter_var('loadtest@'.$domain, FILTER_VALIDATE_EMAIL)) {
            $this->error('The --domain option must be a valid email domain.');

            return self::FAILURE;
        }

        $csvPath = storage_path("app/load-tests/{$batch}/users.csv");
        if (File::exists($csvPath)) {
            $this->error("Batch {$batch} already has a CSV file at {$csvPath}. Choose another --batch value.");

            return self::FAILURE;
        }

        $sequenceLength = strlen((string) $count);
        $emails = collect(range(1, $count))
            ->map(fn (int $number): string => "{$batch}-".str_pad((string) $number, $sequenceLength, '0', STR_PAD_LEFT)."@{$domain}");
        $existingEmail = User::whereIn('email', $emails)->value('email');
        if ($existingEmail) {
            $this->error("The generated email already exists: {$existingEmail}. Choose another --batch value.");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            "Create {$count} real test accounts for tryout {$tryoutId} with batch {$batch}?",
            false
        )) {
            $this->warn('No accounts were created.');

            return self::SUCCESS;
        }

        $rows = [];
        DB::transaction(function () use ($count, $sequenceLength, $batch, $domain, $package, $tryout, &$rows): void {
            $this->withProgressBar(range(1, $count), function (int $number) use (
                $sequenceLength,
                &$rows,
                $batch,
                $domain,
                $package,
                $tryout
            ): void {
                $sequence = str_pad((string) $number, $sequenceLength, '0', STR_PAD_LEFT);
                $email = "{$batch}-{$sequence}@{$domain}";
                $username = Str::limit(str_replace('-', '_', "{$batch}_{$sequence}"), 100, '');
                $password = Str::random(22);
                $user = User::create([
                    'name' => "Load Test {$batch} {$sequence}",
                    'username' => $username,
                    'email' => $email,
                    'email_verified_at' => now(),
                    'password' => Hash::make($password),
                    'role' => 'user',
                    'status' => 'aktif',
                ]);

                if ($package) {
                    UserPackageAcces::create([
                        'user_id' => $user->id,
                        'package_id' => $package->package_id,
                        'start_date' => now(),
                        'end_date' => null,
                        'status' => 'active',
                        'payment_amount' => 0,
                        'payment_status' => 'free',
                        'notes' => "Load test batch {$batch}",
                    ]);
                } else {
                    UserTryoutAccess::create([
                        'user_id' => $user->id,
                        'tryout_id' => $tryout->tryout_id,
                        'access_type' => 'free',
                        'access_source' => 'direct',
                        'status' => 'not_started',
                        'started_at' => null,
                        'completed_at' => null,
                        'progress_percentage' => 0,
                        'expires_at' => null,
                    ]);
                }

                $rows[] = [$email, $password];
            });
        });

        $this->newLine(2);
        File::ensureDirectoryExists(dirname($csvPath), 0700, true);

        $handle = fopen($csvPath, 'wb');
        if ($handle === false) {
            $this->error("Accounts were created, but the credentials CSV could not be written: {$csvPath}");

            return self::FAILURE;
        }

        fputcsv($handle, ['email', 'password']);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
        chmod($csvPath, 0600);

        $this->info("Created {$count} test accounts for tryout {$tryoutId}.");
        $this->line("Batch: {$batch}");
        $this->line("Credentials CSV: {$csvPath}");
        $this->warn('Keep this CSV private. It contains real account passwords and is required by k6.');

        return self::SUCCESS;
    }

    private function batchName(string $value, int $tryoutId): string
    {
        $batch = Str::slug($value);
        if ($batch === '') {
            $batch = 'loadtest-t'.$tryoutId.'-'.now()->format('Ymd-His');
        }

        if (strlen($batch) > 40) {
            throw new \InvalidArgumentException('The --batch value may contain at most 40 URL-safe characters.');
        }

        return $batch;
    }
}
