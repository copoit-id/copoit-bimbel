<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class TesKoran extends Model
{
    protected $fillable = [
        'name',
        'test_type',
        'logic_test_type',
        'direction',
        'number_type',
        'operation_type',
        'column_duration_seconds',
        'duration_minutes',
        'columns_count',
        'rows_count',
        'price',
        'is_for_sale',
        'is_displayed',
        'is_active',
        'access_duration_value',
        'access_duration_unit',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_for_sale' => 'boolean',
        'is_displayed' => 'boolean',
        'column_duration_seconds' => 'integer',
        'duration_minutes' => 'integer',
        'columns_count' => 'integer',
        'rows_count' => 'integer',
        'price' => 'decimal:0',
        'logic_test_type' => 'string',
        'access_duration_value' => 'integer',
    ];

    public function detailPackages(): MorphMany
    {
        return $this->morphMany(DetailPackage::class, 'detailable');
    }

    public function packages(): BelongsToMany
    {
        return $this->morphToMany(Package::class, 'detailable', 'detail_packages', 'detailable_id', 'package_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(TesKoranResult::class);
    }

    public function sheets(): HasMany
    {
        return $this->hasMany(TesKoranSheet::class)->orderBy('sheet_order');
    }

    public function individualPurchases(): MorphMany
    {
        return $this->morphMany(IndividualPurchase::class, 'purchasable');
    }

    public function generateColumns(int $count): array
    {
        $columns = [];
        for ($i = 0; $i < $count; $i++) {
            $column = [];
            for ($j = 0; $j < $this->rows_count; $j++) {
                $column[] = rand(...$this->numberRange());
            }
            $columns[] = $column;
        }
        return $columns;
    }

    public function sheetConfigs(): Collection
    {
        $sheets = $this->relationLoaded('sheets') ? $this->sheets : $this->sheets()->get();

        if ($sheets->isNotEmpty()) {
            return $sheets->values()->map(fn (TesKoranSheet $sheet, int $index) => [
                'id' => $sheet->id,
                'sheet_order' => (int) ($sheet->sheet_order ?: ($index + 1)),
                'name' => $sheet->name ?: 'Lembar ' . ($index + 1),
                'number_type' => $sheet->number_type ?: 'satuan',
                'operation_type' => $sheet->operation_type ?: 'addition',
                'column_duration_seconds' => (int) ($sheet->column_duration_seconds ?: 60),
                'columns_count' => (int) ($sheet->columns_count ?: 30),
                'rows_count' => (int) ($sheet->rows_count ?: 10),
            ]);
        }

        return collect([[
            'id' => null,
            'sheet_order' => 1,
            'name' => 'Lembar 1',
            'number_type' => $this->number_type ?: 'satuan',
            'operation_type' => $this->operation_type ?: 'addition',
            'column_duration_seconds' => (int) ($this->column_duration_seconds ?: 60),
            'columns_count' => (int) ($this->columns_count ?: 30),
            'rows_count' => (int) ($this->rows_count ?: 10),
        ]]);
    }

    public function generateColumnsForSheet(array $sheet): array
    {
        $columns = [];
        for ($i = 0; $i < (int) $sheet['columns_count']; $i++) {
            $column = [];
            for ($j = 0; $j < (int) $sheet['rows_count']; $j++) {
                $column[] = rand(...$this->numberRangeFor($sheet['number_type'] ?? 'satuan'));
            }
            $columns[] = $column;
        }
        return $columns;
    }

    public function numberRangeFor(string $numberType): array
    {
        return match ($numberType) {
            'puluhan' => [10, 99],
            'ratusan' => [100, 999],
            default => [1, 9],
        };
    }

    public function numberRange(): array
    {
        return match ($this->number_type) {
            'puluhan' => [10, 99],
            'ratusan' => [100, 999],
            default => [1, 9],
        };
    }

    public function operationLabel(): string
    {
        return $this->operationLabelFor($this->operation_type ?: 'addition');
    }

    public function operationLabelFor(string $operationType): string
    {
        return match ($operationType) {
            'subtraction' => 'Pengurangan',
            'division' => 'Pembagian',
            default => 'Penjumlahan',
        };
    }

    public function calculateExpectedAnswer(int|float $firstNumber, int|float $secondNumber): string
    {
        return $this->calculateExpectedAnswerFor($firstNumber, $secondNumber, $this->operation_type ?: 'addition');
    }

    public function calculateExpectedAnswerFor(int|float $firstNumber, int|float $secondNumber, string $operationType): string
    {
        $result = match ($operationType) {
            'subtraction' => abs($firstNumber - $secondNumber),
            'division' => $this->calculateDivisionResult($firstNumber, $secondNumber),
            default => $firstNumber + $secondNumber,
        };

        return $this->lastDigit($result);
    }

    public function answerMaxLength(): int
    {
        return 1;
    }

    public function normalizeAnswer(int|float|string $answer): string
    {
        return $this->lastDigit($answer);
    }

    private function calculateDivisionResult(int|float $firstNumber, int|float $secondNumber): float|int
    {
        $dividend = max($firstNumber, $secondNumber);
        $divisor = max(1, min($firstNumber, $secondNumber));

        return $dividend / $divisor;
    }

    private function lastDigit(int|float|string $value): string
    {
        $numericValue = is_numeric($value)
            ? (int) abs(floor((float) $value))
            : (int) preg_replace('/\D/', '', (string) $value);

        return (string) ($numericValue % 10);
    }

    public static function calculateResult(array $answers, array $correctColumns): array
    {
        $totalCorrect = 0;
        $totalWrong = 0;
        $columnScores = [];

        foreach ($correctColumns as $colIndex => $column) {
            $colCorrect = 0;
            $colCount = min(count($answers[$colIndex] ?? []), count($column) - 1);

            for ($i = 0; $i < $colCount; $i++) {
                $userAnswer = $answers[$colIndex][$i] ?? null;
                $expected = $column[$i] + $column[$i + 1];

                if ($userAnswer !== null) {
                    $lastDigit = $expected > 9 ? (int) substr((string) $expected, -1) : $expected;
                    if ($userAnswer == $lastDigit) {
                        $totalCorrect++;
                        $colCorrect++;
                    } else {
                        $totalWrong++;
                    }
                }
            }

            $columnScores[] = $colCorrect;
        }

        return [
            'total_correct' => $totalCorrect,
            'total_wrong' => $totalWrong,
            'column_scores' => $columnScores,
        ];
    }

    public static function analyzeStability(array $columnScores): array
    {
        if (count($columnScores) < 3) {
            return ['status' => 'datar', 'score' => 50];
        }

        $half = floor(count($columnScores) / 2);
        $firstHalfAvg = array_sum(array_slice($columnScores, 0, $half)) / $half;
        $secondHalfAvg = array_sum(array_slice($columnScores, $half)) / (count($columnScores) - $half);

        $diff = $secondHalfAvg - $firstHalfAvg;

        if ($diff > 1) {
            return ['status' => 'meningkat', 'score' => min(100, 50 + ($diff * 10))];
        } elseif ($diff < -1) {
            return ['status' => 'menurun', 'score' => max(0, 50 + ($diff * 10))];
        }

        return ['status' => 'datar', 'score' => 70];
    }

    public function canUserAccess(int $userId): bool
    {
        $hasPackageAccess = $this->packages()
            ->whereHas('userAccess', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('end_date')->orWhere('end_date', '>', now());
                    });
            })
            ->exists();

        if ($hasPackageAccess) {
            return true;
        }

        return $this->hasUserPurchased($userId);
    }

    public function hasUserPurchased(int $userId): bool
    {
        return IndividualPurchase::where('user_id', $userId)
            ->where('purchasable_type', self::class)
            ->where('purchasable_id', $this->id)
            ->where('status', IndividualPurchase::STATUS_APPROVED)
            ->where(function ($query) {
                $query->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->exists();
    }

    public function hasPendingPurchase(int $userId): bool
    {
        return IndividualPurchase::where('user_id', $userId)
            ->where('purchasable_type', self::class)
            ->where('purchasable_id', $this->id)
            ->where('status', IndividualPurchase::STATUS_PENDING)
            ->exists();
    }

    public function accessiblePackageForUser(?int $userId): ?Package
    {
        if (!$userId) {
            return $this->packages()->first();
        }

        return $this->packages()
            ->whereHas('userAccess', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('end_date')->orWhere('end_date', '>', now());
                    });
            })
            ->first() ?: $this->packages()->first();
    }
}
