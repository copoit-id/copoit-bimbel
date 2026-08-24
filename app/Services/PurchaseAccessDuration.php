<?php

namespace App\Services;

use Carbon\Carbon;

class PurchaseAccessDuration
{
    public const UNITS = ['forever', 'day', 'week', 'month', 'year'];

    public static function expiresAt(object $item, ?Carbon $start = null): ?Carbon
    {
        $unit = (string) ($item->access_duration_unit ?? 'forever');
        $value = (int) ($item->access_duration_value ?? 0);

        if ($unit === 'forever' || $value <= 0) {
            return null;
        }

        $start ??= Carbon::now();

        return match ($unit) {
            'day' => $start->copy()->addDays($value),
            'week' => $start->copy()->addWeeks($value),
            'month' => $start->copy()->addMonthsNoOverflow($value),
            'year' => $start->copy()->addYearsNoOverflow($value),
            default => null,
        };
    }

    public static function normalizedValue(?string $unit, mixed $value): ?int
    {
        if (($unit ?: 'forever') === 'forever') {
            return null;
        }

        return max(1, (int) $value);
    }

    public static function normalizedUnit(?string $unit): string
    {
        return in_array($unit, self::UNITS, true) ? $unit : 'forever';
    }
}
