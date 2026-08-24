<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class SafeName implements Rule
{
    public function passes($attribute, $value): bool
    {
        $name = trim((string) $value);

        // Must not be URL-like (spam protection)
        return !User::containsUrlLike($name);
    }

    public function message(): string
    {
        return 'Nama tidak boleh mengandung URL atau link.';
    }
}
