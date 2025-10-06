<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\RecaptchaRule;
use App\Services\RecaptchaService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Http;

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
            return redirect()->back();
        }
        return view('auth.login', [
            'recaptcha_site_key' => $this->recaptchaService->getSiteKey(),
            'recaptcha_enabled' => $this->recaptchaService->isEnabled()
        ]);
    }

    public function authenticate(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];

        // Add reCAPTCHA validation if enabled
        if (config('services.recaptcha.enabled')) {
            $rules['g-recaptcha-response'] = 'required';
        }

        $request->validate($rules, [
            'g-recaptcha-response.required' => 'Verifikasi reCAPTCHA diperlukan.'
        ]);

        // Verify reCAPTCHA if enabled
        if (config('services.recaptcha.enabled')) {
            $recaptchaResponse = $request->input('g-recaptcha-response');
            if (!$this->verifyRecaptchaV3($recaptchaResponse, 'login')) {
                return back()->withErrors([
                    'email' => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.',
                ])->withInput($request->except('password'));
            }
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Redirect based on user role
            if ($user->role === 'admin') {
                return redirect()->intended(route('admin.dashboard'));
            } else {
                return redirect()->intended(route('user.dashboard.index'));
            }
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->withInput($request->except('password'));
    }

    public function showRegister()
    {
        return view('auth.register', [
            'recaptcha_site_key' => $this->recaptchaService->getSiteKey(),
            'recaptcha_enabled' => $this->recaptchaService->isEnabled()
        ]);
    }

    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'date_of_birth' => 'required|date|before:today',
        ];

        // Add reCAPTCHA validation if enabled
        if (config('services.recaptcha.enabled')) {
            $rules['g-recaptcha-response'] = 'required';
        }

        $validatedData = $request->validate($rules, [
            'g-recaptcha-response.required' => 'Verifikasi reCAPTCHA diperlukan.'
        ]);

        // Verify reCAPTCHA if enabled
        if (config('services.recaptcha.enabled')) {
            $recaptchaResponse = $request->input('g-recaptcha-response');
            if (!$this->verifyRecaptchaV3($recaptchaResponse, 'register')) {
                return back()->withErrors([
                    'email' => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.',
                ])->withInput($request->except('password', 'password_confirmation'));
            }
        }

        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'username' => strtolower(str_replace(' ', '', $validatedData['name'])),
                'password' => Hash::make($validatedData['password']),
                'date_of_birth' => $validatedData['date_of_birth'],
                'role' => 'user',
            ]);

            Auth::login($user);

            return redirect()->route('user.dashboard.index')
                ->with('success', 'Akun berhasil dibuat! Selamat datang di CPNS Academy.');
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
            'remoteip' => request()->ip()
        ]);

        $body = $verifyResponse->json();

        // For v3, check success and score
        if (!$body['success']) {
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

    public function logout(Request $request): RedirectResponse
    {
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
            'email' => 'required|email|exists:users,email'
        ], [
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'email.exists' => 'Email tidak terdaftar dalam sistem'
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate reset token
        $token = Str::random(64);

        // Store token in database (you might want to create a password_resets table)
        $user->update([
            'reset_token' => $token,
            'reset_token_expires' => Carbon::now()->addHour()
        ]);

        // Send email
        try {
            Mail::send('emails.reset-password', [
                'user' => $user,
                'token' => $token,
                'resetUrl' => route('password.reset', $token)
            ], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Reset Password - CPNS Academy');
            });

            return redirect()->back()->with('success', 'Link reset password telah dikirim ke email Anda');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengirim email. Silakan coba lagi.');
        }
    }

    public function showResetPassword($token)
    {
        $user = User::where('reset_token', $token)
            ->where('reset_token_expires', '>', Carbon::now())
            ->first();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Token reset password tidak valid atau sudah kadaluarsa');
        }

        return view('auth.reset-password', compact('token'));
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ], [
            'password.required' => 'Password harus diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok'
        ]);

        $user = User::where('reset_token', $request->token)
            ->where('reset_token_expires', '>', Carbon::now())
            ->first();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Token reset password tidak valid atau sudah kadaluarsa');
        }

        // Update password and clear reset token
        $user->update([
            'password' => Hash::make($request->password),
            'reset_token' => null,
            'reset_token_expires' => null
        ]);

        return redirect()->route('login')->with('success', 'Password berhasil direset. Silakan login dengan password baru');
    }
}