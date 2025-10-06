<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RecaptchaService
{
    protected $secretKey;
    protected $siteKey;

    public function __construct()
    {
        $this->secretKey = config('services.recaptcha.secret_key');
        $this->siteKey = config('services.recaptcha.site_key');
    }

    /**
     * Verify reCAPTCHA response
     */
    public function verify($response, $ip = null)
    {
        if (empty($response)) {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $this->secretKey,
                'response' => $response,
                'remoteip' => $ip ?? request()->ip()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return isset($data['success']) && $data['success'] === true;
            }

            return false;
        } catch (\Exception $e) {
            // Log error if needed
            \Log::error('reCAPTCHA verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get site key for frontend
     */
    public function getSiteKey()
    {
        return $this->siteKey;
    }

    /**
     * Check if reCAPTCHA is enabled
     */
    public function isEnabled()
    {
        return !empty($this->siteKey) && !empty($this->secretKey);
    }
}
