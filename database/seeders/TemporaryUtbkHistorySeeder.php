<?php

namespace Database\Seeders;

use App\Models\DetailPackage;
use App\Models\Package;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Models\User;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use App\Models\UserPackageAcces;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TemporaryUtbkHistorySeeder extends Seeder
{
    private const USER_EMAIL = 'user@bimbelhub.com';
    private const PACKAGE_NAME = '[TEMP] Paket UTBK History Demo';
    private const TRYOUT_NAME = '[TEMP] Tryout UTBK History Demo';

    public function run(): void
    {
        DB::transaction(function () {
            $this->cleanupExistingData();

            $user = $this->resolveUser();
            $package = $this->createPackage();
            $tryout = $this->createTryout();

            DetailPackage::create([
                'package_id' => $package->package_id,
                'detailable_type' => Tryout::class,
                'detailable_id' => $tryout->tryout_id,
                'order' => 1,
            ]);

            $this->grantPackageAccess($user, $package);

            $details = $this->createUtbkDetails($tryout);
            $questionsByDetail = $this->createQuestions($details);

            foreach ([650, 730, 810, 875, 690] as $index => $score) {
                $this->createCompletedAttempt($user, $tryout, $details, $questionsByDetail, $score, $index);
            }
        });
    }

    private function cleanupExistingData(): void
    {
        Tryout::where('name', self::TRYOUT_NAME)->get()->each->delete();
        Package::where('name', self::PACKAGE_NAME)->get()->each->delete();
    }

    private function resolveUser(): User
    {
        return User::firstOrCreate(
            ['email' => self::USER_EMAIL],
            [
                'name' => 'User BimbelHub',
                'username' => 'user_bimbelhub',
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'aktif',
                'email_verified_at' => now(),
            ]
        );
    }

    private function createPackage(): Package
    {
        return Package::create([
            'name' => self::PACKAGE_NAME,
            'price' => 0,
            'type_package' => 'tryout',
            'type_price' => 'paid',
            'status' => 'active',
            'description' => 'Seeder sementara untuk menampilkan history UTBK dengan nilai variatif.',
            'features' => "Tryout UTBK\nHistory nilai 650, 730, 810, 875, 690",
        ]);
    }

    private function createTryout(): Tryout
    {
        $now = Carbon::now('Asia/Jakarta');
        $data = [
            'name' => self::TRYOUT_NAME,
            'description' => 'Tryout UTBK sementara dengan data history pengerjaan dummy.',
            'type_tryout' => 'utbk_full',
            'is_certification' => false,
            'is_toefl' => false,
            'is_irt' => true,
            'start_date' => $now->copy()->subMonth(),
            'end_date' => $now->copy()->addMonth(),
            'results_release_at' => $now->copy()->subDay(),
            'results_released_at' => $now->copy()->subDay(),
            'is_active' => true,
        ];

        $optionalColumns = [
            'assessment_type' => 'standard',
            'section_break_duration' => 0,
            'answer_persistence_mode' => 'client_side',
            'subtest_display_mode' => 'combined',
            'is_for_sale' => false,
            'is_displayed' => true,
            'price' => 0,
            'enable_anti_copy' => false,
            'enable_tab_switch_detection' => false,
            'enable_webcam_check' => false,
            'enable_screen_check' => false,
        ];

        foreach ($optionalColumns as $column => $value) {
            if (Schema::hasColumn('tryouts', $column)) {
                $data[$column] = $value;
            }
        }

        return Tryout::create($data);
    }

    /**
     * @return \Illuminate\Support\Collection<int, TryoutDetail>
     */
    private function createUtbkDetails(Tryout $tryout)
    {
        $utbkSubtests = [
            'utbk_penalaran_umum' => ['duration' => 30, 'passing_score' => 500],
            'utbk_pengetahuan_umum' => ['duration' => 25, 'passing_score' => 500],
            'utbk_pengetahuan_kuantitatif' => ['duration' => 25, 'passing_score' => 500],
            'utbk_pemahaman_bacaan_menulis' => ['duration' => 25, 'passing_score' => 500],
            'utbk_literasi_bahasa_indonesia' => ['duration' => 30, 'passing_score' => 500],
            'utbk_literasi_bahasa_inggris' => ['duration' => 30, 'passing_score' => 500],
            'utbk_penalaran_matematika' => ['duration' => 30, 'passing_score' => 500],
        ];

        $legacyCompatibleSubtests = [
            'general' => ['duration' => 30, 'passing_score' => 500],
            'tiu' => ['duration' => 25, 'passing_score' => 500],
            'twk' => ['duration' => 25, 'passing_score' => 500],
            'tkp' => ['duration' => 25, 'passing_score' => 500],
            'reading' => ['duration' => 30, 'passing_score' => 500],
            'writing' => ['duration' => 30, 'passing_score' => 500],
            'listening' => ['duration' => 30, 'passing_score' => 500],
        ];

        $allowedTypes = $this->allowedEnumValues('tryout_details', 'type_subtest');
        $subtests = collect($utbkSubtests)
            ->keys()
            ->every(fn (string $type) => in_array($type, $allowedTypes, true))
            ? $utbkSubtests
            : $legacyCompatibleSubtests;

        return collect($subtests)->map(function (array $config, string $type) use ($tryout) {
            $data = [
                'tryout_id' => $tryout->tryout_id,
                'type_subtest' => $type,
                'duration' => $config['duration'],
                'passing_score' => $config['passing_score'],
            ];

            if (Schema::hasColumn('tryout_details', 'passing_type')) {
                $data['passing_type'] = 'score';
            }

            return TryoutDetail::create($data);
        })->values();
    }

    private function allowedEnumValues(string $table, string $column): array
    {
        $database = DB::getDatabaseName();
        $columnType = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->value('COLUMN_TYPE');

        if (!is_string($columnType) || !str_starts_with($columnType, 'enum(')) {
            return [];
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $columnType, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $value) => stripcslashes($value))
            ->all();
    }

    private function createQuestions($details): array
    {
        $questionsByDetail = [];

        foreach ($details as $detail) {
            $questionsByDetail[$detail->tryout_detail_id] = collect();

            for ($number = 1; $number <= 8; $number++) {
                $question = Question::create($this->questionData($detail, $number));

                $correctPosition = (($number - 1) % 4) + 1;
                for ($position = 1; $position <= 4; $position++) {
                    $questionsByDetail[$detail->tryout_detail_id]->push([
                        'question' => $question,
                        'option' => QuestionOption::create([
                            'question_id' => $question->question_id,
                            'option_text' => 'Opsi ' . chr(64 + $position) . ' untuk soal ' . $number,
                            'weight' => $position === $correctPosition ? 1 : 0,
                            'is_correct' => $position === $correctPosition,
                        ]),
                    ]);
                }
            }
        }

        return $questionsByDetail;
    }

    private function questionData(TryoutDetail $detail, int $number): array
    {
        $data = [
            'tryout_detail_id' => $detail->tryout_detail_id,
            'question_type' => 'multiple_choice',
            'question_text' => sprintf(
                'Soal demo %s nomor %d. Pilih jawaban yang paling tepat.',
                Str::headline(str_replace('utbk_', '', $detail->type_subtest)),
                $number
            ),
            'explanation' => 'Pembahasan dummy untuk kebutuhan seed history UTBK sementara.',
            'default_weight' => 1,
            'custom_score' => 'no',
        ];

        if (Schema::hasColumn('questions', 'metadata')) {
            $data['metadata'] = null;
        }

        return $data;
    }

    private function grantPackageAccess(User $user, Package $package): void
    {
        UserPackageAcces::updateOrCreate(
            [
                'user_id' => $user->id,
                'package_id' => $package->package_id,
            ],
            [
                'start_date' => Carbon::now('Asia/Jakarta')->subDay(),
                'end_date' => Carbon::now('Asia/Jakarta')->addMonths(3),
                'status' => 'active',
                'payment_amount' => 0,
                'payment_status' => 'free',
                'notes' => 'Akses dibuat oleh TemporaryUtbkHistorySeeder.',
            ]
        );
    }

    private function createCompletedAttempt(User $user, Tryout $tryout, $details, array $questionsByDetail, int $totalScore, int $attemptIndex): void
    {
        $attemptToken = 'TEMP-UTBK-' . $totalScore . '-' . Str::uuid();
        $startedAt = Carbon::now('Asia/Jakarta')
            ->subDays(10 - $attemptIndex)
            ->setTime(9 + $attemptIndex, 0);
        $finishedAt = $startedAt->copy()->addMinutes(165 + ($attemptIndex * 3));

        foreach ($details as $detailIndex => $detail) {
            $rows = $questionsByDetail[$detail->tryout_detail_id];
            $questionGroups = $rows->groupBy(fn ($row) => $row['question']->question_id);
            $correctTarget = min(8, max(5, 5 + $attemptIndex + ($detailIndex % 2)));
            $correctCount = 0;
            $answer = UserAnswer::create([
                'user_id' => $user->id,
                'tryout_id' => $tryout->tryout_id,
                'tryout_detail_id' => $detail->tryout_detail_id,
                'attempt_token' => $attemptToken,
                'started_at' => $startedAt->copy()->addMinutes($detailIndex * 20),
                'finished_at' => $finishedAt,
                'score' => $this->subtestScore($totalScore, $detailIndex),
                'utbk_total_score' => $totalScore,
                'correct_answers' => 0,
                'status' => 'completed',
            ]);

            foreach ($questionGroups->values() as $questionIndex => $options) {
                $shouldBeCorrect = $questionIndex < $correctTarget;
                $selected = $shouldBeCorrect
                    ? $options->first(fn ($row) => $row['option']->is_correct)
                    : $options->first(fn ($row) => !$row['option']->is_correct);

                if (!$selected) {
                    continue;
                }

                if ($shouldBeCorrect) {
                    $correctCount++;
                }

                UserAnswerDetail::create([
                    'user_answer_id' => $answer->user_answer_id,
                    'question_id' => $selected['question']->question_id,
                    'question_option_id' => $selected['option']->question_option_id,
                    'is_correct' => $shouldBeCorrect,
                    'answered_at' => $startedAt->copy()->addMinutes(($detailIndex * 20) + $questionIndex + 1),
                ]);
            }

            $answer->forceFill([
                'correct_answers' => $correctCount,
                'wrong_answers' => 8 - $correctCount,
                'unanswered' => 0,
                'is_passed' => true,
                'created_at' => $startedAt,
                'updated_at' => $finishedAt,
            ])->save();
        }
    }

    private function subtestScore(int $totalScore, int $detailIndex): float
    {
        $offsets = [-24, 18, -12, 9, 27, -18, 0];

        return max(500, min(1000, $totalScore + ($offsets[$detailIndex] ?? 0)));
    }
}
