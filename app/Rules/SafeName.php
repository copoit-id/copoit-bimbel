<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class SafeName implements Rule
{
    public function passes($attribute, $value): bool
    {
        return !User::containsUrlLike((string) $value);
    }

    public function message(): string
    {
        return 'Nama tidak boleh mengandung URL atau domain.';
    }
}
