<?php

namespace App\Http\Controllers;

use App\Models\ParticipantDestinationCategory;
use App\Models\User;
use App\Rules\SafeName;
use App\Services\ActivityLogger;
use App\Services\AffiliateService;
use App\Services\ConcurrentLoginService;
use App\Services\ParticipantDestinationSelectionService;
use App\Services\RecaptchaService;
use App\Support\MailSafety;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected $recaptchaService;

    public function __construct(RecaptchaService $recaptchaService)
    {
        $this->recaptchaService = $recaptchaService;
    }

    public function showLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isSuperAdmin()) {
                return redirect()->route('super-admin.admins.index');
            }
            if ($user->isTutor()) {
                return redirect()->route('tutor.schedule.index');
            }
            if ($user->isParent()) {
                return redirect()->route('parent.dashboard');
            }

            return $user->canAccessAdminPanel()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('user.dashboard.index');
        }

        return view('auth.login', [
            'recaptcha_site_key' => $this->recaptchaService->getSiteKey(),
            'recaptcha_enabled' => $this->recaptchaService->isEnabled(),
        ]);
    }

    public function authenticate(Request $request, ConcurrentLoginService $concurrentLoginService)
    {
        $throttleKey = $this->throttleKey($request);
        $lockUntil = Cache::get($this->lockKey($throttleKey));
        if ($lockUntil) {
            $remaining = max(0, Carbon::now()->diffInSeconds(Carbon::parse($lockUntil), false));
            ActivityLogger::log('login_locked', 'failed', null, [
                'email' => (string) $request->input('email'),
                'remaining_seconds' => $remaining,
            ], $request, (string) $request->input('email'));

            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan login. Coba lagi dalam '.$this->humanDuration($remaining).'.',
            ])->withInput($request->except('password'));
        }

        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];

        // Add reCAPTCHA validation if enabled
        if (config('services.recaptcha.enabled')) {
            $rules['g-recaptcha-response'] = 'required';
        }

        $request->validate($rules, [
            'g-recaptcha-response.required' => 'Verifikasi reCAPTCHA diperlukan.',
        ]);

        // Verify reCAPTCHA if enabled
        if (config('services.recaptcha.enabled')) {
            $recaptchaResponse = $request->input('g-recaptcha-response');
            if (! $this->verifyRecaptchaV3($recaptchaResponse, 'login')) {
                return back()->withErrors([
                    'email' => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.',
                ])->withInput($request->except('password'));
            }
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($this->attemptsKey($throttleKey));
            Cache::forget($this->lockKey($throttleKey));
            $request->session()->regenerate();

            $user = Auth::user();
            $concurrentLoginService->enforce($user, $request->session()->getId());
            ActivityLogger::log('login_success', 'success', $user, [], $request);

            // Redirect based on user role
            if ($user->isSuperAdmin()) {
                return redirect()->intended(route('super-admin.admins.index'));
            }
            if ($user->isTutor()) {
                return redirect()->route('tutor.schedule.index');
            }
            if ($user->isParent()) {
                return redirect()->intended(route('parent.dashboard'));
            }
            if ($user->canAccessAdminPanel()) {
                if ($user->role === 'admin_demo') {
                    if (! $user->admin_expires_at || Carbon::now('Asia/Jakarta')->gte($user->admin_expires_at)) {
                        Auth::logout();
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();

                        return back()->withErrors([
                            'email' => 'Akses admin demo sudah berakhir. Silakan hubungi super admin.',
                        ])->withInput($request->except('password'));
                    }
                }
                if ($user->role === 'admin' && $user->admin_expires_at && Carbon::now('Asia/Jakarta')->gte($user->admin_expires_at)) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors([
                        'email' => 'Akses admin Anda sudah berakhir. Silakan hubungi super admin.',
                    ])->withInput($request->except('password'));
                }

                return redirect()->intended(route('admin.dashboard'));
            } else {
                return redirect()->intended(route('user.dashboard.index'));
            }
        }

        $attemptKey = $this->attemptsKey($throttleKey);
        // RateLimiter increments atomically. Cache::get() followed by Cache::put()
        // allowed simultaneous failed requests to overwrite one another, effectively
        // bypassing the lockout counter during a brute-force burst.
        RateLimiter::hit($attemptKey, 24 * 60 * 60);
        $attempts = RateLimiter::attempts($attemptKey);

        $lockSeconds = $this->lockSecondsForAttempts($attempts);
        if ($lockSeconds > 0) {
            Cache::put($this->lockKey($throttleKey), Carbon::now()->addSeconds($lockSeconds), Carbon::now()->addSeconds($lockSeconds));
        }

        ActivityLogger::log('login_failed', 'failed', null, [
            'email' => (string) $request->input('email'),
            'attempts' => $attempts,
            'lock_seconds' => $lockSeconds,
        ], $request, (string) $request->input('email'));

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->withInput($request->except('password'));
    }

    public function showRegister(Request $request, AffiliateService $affiliateService)
    {
        $affiliateRefCode = strtoupper(trim((string) $request->query('ref', session('affiliate_ref_code', ''))));
        if ($affiliateRefCode !== '' && $affiliateService->referrerFromCode($affiliateRefCode)) {
            session(['affiliate_ref_code' => $affiliateRefCode]);
        }

        return view('auth.register', [
            'recaptcha_site_key' => $this->recaptchaService->getSiteKey(),
            'recaptcha_enabled' => $this->recaptchaService->isEnabled(),
            'destinationCategories' => $this->getDestinationCategories(),
            'affiliateRefCode' => session('affiliate_ref_code'),
        ]);
    }

    public function register(
        Request $request,
        AffiliateService $affiliateService,
        ConcurrentLoginService $concurrentLoginService,
        ParticipantDestinationSelectionService $destinationSelectionService
    ) {
        $rules = [
            'name' => ['required', 'string', 'max:255', new SafeName],
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'date_of_birth' => 'required|date|before:today',
            'phone' => ['required', 'string', 'regex:/^62[0-9]{8,14}$/'],
            'education_level' => ['nullable', 'string', 'max:100'],
            'origin_institution' => ['nullable', 'string', 'max:255'],
            'major_choice_1' => ['nullable', 'string', 'max:255'],
            'major_choice_2' => ['nullable', 'string', 'max:255'],
            'affiliate_ref_code' => ['nullable', 'string', 'max:32'],
        ];

        // Add reCAPTCHA validation if enabled
        if (config('services.recaptcha.enabled')) {
            $rules['g-recaptcha-response'] = 'required';
        }

        $validatedData = $request->validate($rules, [
            'g-recaptcha-response.required' => 'Verifikasi reCAPTCHA diperlukan.',
        ]);
        $destinationPayload = $destinationSelectionService->validate(
            $request,
            $destinationSelectionService->isRequired()
        );

        // Verify reCAPTCHA if enabled
        if (config('services.recaptcha.enabled')) {
            $recaptchaResponse = $request->input('g-recaptcha-response');
            if (! $this->verifyRecaptchaV3($recaptchaResponse, 'register')) {
                return back()->withErrors([
                    'email' => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.',
                ])->withInput($request->except('password', 'password_confirmation'));
            }
        }

        $brandName = $this->getBrandName();
        $referrer = $affiliateService->referrerFromCode($validatedData['affiliate_ref_code'] ?? session('affiliate_ref_code'));

        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'username' => strtolower(str_replace(' ', '', $validatedData['name'])),
                'password' => Hash::make($validatedData['password']),
                'date_of_birth' => $validatedData['date_of_birth'],
                'phone' => $validatedData['phone'],
                'education_level' => $validatedData['education_level'] ?? null,
                'origin_institution' => $validatedData['origin_institution'] ?? null,
                'major_choice_1' => $validatedData['major_choice_1'] ?? null,
                'major_choice_2' => $validatedData['major_choice_2'] ?? null,
                ...$destinationPayload,
                'referred_by_user_id' => $referrer?->id,
                'referred_at' => $referrer ? now() : null,
                'role' => 'user',
            ]);

            $affiliateService->ensureCode($user);
            session()->forget('affiliate_ref_code');

            $this->sendNewRegistrationNotification($user);

            ActivityLogger::log('register', 'success', $user, [], $request);

            Auth::login($user);
            $request->session()->regenerate();
            $concurrentLoginService->enforce($user, $request->session()->getId());

            return redirect()->route('user.dashboard.index')
                ->with('success', "Akun berhasil dibuat! Selamat datang di {$brandName}.");
        } catch (\Exception $e) {
            return back()->withErrors([
                'email' => 'Terjadi kesalahan saat membuat akun. Silakan coba lagi.',
            ])->withInput($request->except('password', 'password_confirmation'));
        }
    }

    /**
     * Verify reCAPTCHA v3 response
     */
    private function verifyRecaptchaV3($response, $action)
    {
        $secretKey = config('services.recaptcha.secret_key');

        $verifyResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => $response,
            'remoteip' => request()->ip(),
        ]);

        $body = $verifyResponse->json();

        // For v3, check success and score
        if (! $body['success']) {
            return false;
        }

        // Check if action matches
        if (isset($body['action']) && $body['action'] !== $action) {
            return false;
        }

        // Check score (v3 returns a score between 0.0 and 1.0)
        $minScore = config('services.recaptcha.min_score', 0.5);
        if (isset($body['score']) && $body['score'] < $minScore) {
            return false;
        }

        return true;
    }

    private function getDestinationCategories()
    {
        return ParticipantDestinationCategory::query()
            ->root()
            ->active()
            ->with(['activeChildren'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function logout(Request $request): RedirectResponse
    {
        ActivityLogger::log('logout', 'success', $request->user(), [], $request);
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda berhasil logout.');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
        ]);

        $email = MailSafety::email($request->input('email'));
        $user = $email ? User::where('email', $email)->first() : null;

        if (! $user || ! $email) {
            return redirect()->back()->with('success', 'Jika alamat email terdaftar, tautan reset password telah dikirim.');
        }

        $token = Str::random(64);
        $expiresAt = Carbon::now()->addHour();

        $user->update([
            'reset_token' => hash('sha256', $token),
            'reset_token_expires' => $expiresAt,
        ]);

        $recipient = MailSafety::email($user->email);
        $brandName = MailSafety::header($this->getBrandName(), 'Copoit Academy');

        if (! $recipient) {
            Log::warning('Password reset skipped because the stored recipient email is invalid.', [
                'user_id' => $user->getKey(),
            ]);

            return redirect()->back()->with('success', 'Jika alamat email terdaftar, tautan reset password telah dikirim.');
        }

        try {
            Mail::send('emails.reset-password', [
                'user' => $user,
                'token' => $token,
                'resetUrl' => route('password.reset', $token),
            ], function ($message) use ($recipient, $brandName) {
                $message->to($recipient);
                $message->subject("Reset Password - {$brandName}");
            });

            ActivityLogger::log('reset_requested', 'success', $user, [
                'expires_at' => $expiresAt->toDateTimeString(),
            ], $request);

            return redirect()->back()->with('success', 'Jika alamat email terdaftar, tautan reset password telah dikirim.');
        } catch (\Exception $e) {
            Log::warning('Failed to send password reset email.', [
                'user_id' => $user->getKey(),
                'mailer' => config('mail.default'),
                'smtp_host' => config('mail.mailers.smtp.host'),
                'smtp_port' => config('mail.mailers.smtp.port'),
                'smtp_scheme' => config('mail.mailers.smtp.scheme'),
                'smtp_encryption' => config('mail.mailers.smtp.encryption'),
                'error' => $e->getMessage(),
            ]);

            ActivityLogger::log('reset_requested', 'failed', $user, [
                'error' => $e->getMessage(),
            ], $request);

            return redirect()->back()->with('success', 'Jika alamat email terdaftar, tautan reset password telah dikirim.');
        }
    }

    public function showResetPassword($token)
    {
        $user = User::where('reset_token', hash('sha256', (string) $token))
            ->where('reset_token_expires', '>', Carbon::now())
            ->first();

        if (! $user) {
            ActivityLogger::log('reset_token_invalid', 'failed', null, [
                'token_hash' => sha1($token),
            ]);

            return redirect()->route('login')->with('error', 'Token reset password tidak valid atau sudah kadaluarsa');
        }

        return view('auth.reset-password', compact('token'));
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string', 'size:64'],
            'password' => 'required|min:8|confirmed',
        ], [
            'password.required' => 'Password harus diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        $user = User::where('reset_token', hash('sha256', (string) $request->token))
            ->where('reset_token_expires', '>', Carbon::now())
            ->first();

        if (! $user) {
            ActivityLogger::log('reset_failed', 'failed', null, [
                'token_hash' => sha1((string) $request->token),
            ], $request);

            return redirect()->route('login')->with('error', 'Token reset password tidak valid atau sudah kadaluarsa');
        }

        // Update password and clear reset token
        $user->update([
            'password' => Hash::make($request->password),
            'reset_token' => null,
            'reset_token_expires' => null,
        ]);

        ActivityLogger::log('reset_completed', 'success', $user, [], $request);

        return redirect()->route('login')->with('success', 'Password berhasil direset. Silakan login dengan password baru');
    }

    private function getBrandName(): string
    {
        return config('client.branding.name', 'Copoit Academy');
    }

    private function throttleKey(Request $request): string
    {
        return 'login:'.sha1(Str::lower(trim((string) $request->input('email'))));
    }

    private function lockKey(string $throttleKey): string
    {
        return $throttleKey.':lock';
    }

    private function attemptsKey(string $throttleKey): string
    {
        return $throttleKey.':attempts';
    }

    private function lockSecondsForAttempts(int $attempts): int
    {
        if ($attempts <= 5) {
            return 0;
        }

        return match ($attempts) {
            6 => 10 * 60,
            7 => 30 * 60,
            8 => 2 * 60 * 60,
            default => 24 * 60 * 60,
        };
    }

    private function humanDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);

        if ($seconds < 60) {
            return $seconds.' detik';
        }

        if ($seconds < 3600) {
            return (int) ceil($seconds / 60).' menit';
        }

        if ($seconds < 86400) {
            return (int) ceil($seconds / 3600).' jam';
        }

        return (int) ceil($seconds / 86400).' hari';
    }

    private function sendNewRegistrationNotification(User $newUser): void
    {
        $recipient = MailSafety::email(
            config('client.branding.smtp_notification_email')
                ?: config('client.branding.smtp_email')
        );

        if (! $recipient) {
            return;
        }

        $brandName = MailSafety::header($this->getBrandName(), 'Copoit Academy');
        $registeredAt = now()->timezone('Asia/Jakarta')->format('d-m-Y H:i');

        try {
            Mail::send('emails.new-registration-admin', [
                'newUser' => $newUser,
                'brandName' => $brandName,
                'registeredAt' => $registeredAt,
            ], function ($message) use ($recipient, $brandName) {
                $message->to($recipient);
                $message->subject("Pendaftar Baru - {$brandName}");
            });
        } catch (\Throwable $exception) {
            Log::warning('Failed to send new registration notification email.', [
                'email' => $recipient,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
