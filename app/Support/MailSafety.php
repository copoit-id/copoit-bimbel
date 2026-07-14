<?php

namespace App\Support;

use Illuminate\Support\Str;

final class MailSafety
{
    public static function email(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || preg_match('/[\r\n\x00]/', $value)) {
            return null;
        }

        $email = filter_var($value, FILTER_VALIDATE_EMAIL);

        return $email === false ? null : Str::lower($email);
    }

    public static function header(string $value, string $fallback, int $maxLength = 160): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
        $value = Str::squish($value);

        return Str::limit($value !== '' ? $value : $fallback, $maxLength, '');
    }
}
