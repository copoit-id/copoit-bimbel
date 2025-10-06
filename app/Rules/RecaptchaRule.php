<?php

namespace App\Rules;

use App\Services\RecaptchaService;
use Illuminate\Contracts\Validation\Rule;

class RecaptchaRule implements Rule
{
    protected $recaptchaService;

    public function __construct()
    {
        $this->recaptchaService = app(RecaptchaService::class);
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value)
    {
        if (!$this->recaptchaService->isEnabled()) {
            return true; // Skip validation if reCAPTCHA is not configured
        }

        return $this->recaptchaService->verify($value);
    }

    /**
     * Get the validation error message.
     */
    public function message()
    {
        return 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.';
    }
}
