<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TesKoran extends Model
{
    protected $fillable = [
        'name',
        'test_type',
        'direction',
        'duration_minutes',
        'columns_count',
        'rows_count',
        'price',
        'is_for_sale',
        'is_displayed',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_for_sale' => 'boolean',
        'is_displayed' => 'boolean',
        'duration_minutes' => 'integer',
        'columns_count' => 'integer',
        'rows_count' => 'integer',
        'price' => 'decimal:0',
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

    public function generateColumns(int $count): array
    {
        $columns = [];
        for ($i = 0; $i < $count; $i++) {
            $column = [];
            for ($j = 0; $j < $this->rows_count; $j++) {
                $column[] = rand(1, 9);
            }
            $columns[] = $column;
        }
        return $columns;
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
