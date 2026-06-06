<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function index()
    {
        $profile = ClientProfile::query()->first();

        $branding = config('client.branding', [
            'name' => config('app.name'),
            'logo_url' => asset('img/logo/logo-copoit.png'),
            'favicon_url' => asset('img/logo/logo-copoit.png'),
            'primary_color' => '#1C3259',
            'secondary_color' => '#F3F3F3',
            'header_primary_color' => false,
            'sidebar_primary_color' => false,
            'utbk_enabled' => true,
            'payment_mode' => 'gateway',
            'payment_bank_name' => null,
            'payment_account_number' => null,
            'payment_account_holder' => null,
            'payment_bank_note' => null,
            'payment_gateway' => 'xendit',
            'payment_gateway_mode' => 'sandbox',
            'xendit_secret_key' => null,
            'xendit_webhook_token' => null,
            'midtrans_server_key' => null,
            'midtrans_client_key' => null,
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_encryption' => null,
            'smtp_email' => null,
            'smtp_app_password' => null,
            'smtp_notification_email' => null,
        ]);

        return view('admin.pages.settings.index', [
            'profile' => $profile,
            'branding' => $branding,
        ]);
    }

    public function update(Request $request)
    {
        $profile = ClientProfile::query()->first() ?? new ClientProfile();

        $rules = [
            'nama_bimbel' => ['required', 'string', 'max:255'],
            'warna_primary' => ['required', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'warna_secondary' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'logo' => ['nullable', 'mimes:png,jpg,jpeg,svg,webp', 'max:5120'],
            'favicon' => ['nullable', 'mimes:ico,png,jpg,jpeg,svg,webp', 'max:4096'],
            'payment_mode' => ['required', 'in:gateway,manual'],
            'payment_bank_name' => ['nullable', 'string', 'max:255'],
            'payment_account_number' => ['nullable', 'string', 'max:100'],
            'payment_account_holder' => ['nullable', 'string', 'max:255'],
            'payment_bank_note' => ['nullable', 'string', 'max:255'],
            'payment_gateway' => ['nullable', 'in:xendit,midtrans'],
            'payment_gateway_mode' => ['nullable', 'in:sandbox,production'],
            'xendit_secret_key' => ['nullable', 'string', 'max:255'],
            'xendit_webhook_token' => ['nullable', 'string', 'max:255'],
            'midtrans_server_key' => ['nullable', 'string', 'max:255'],
            'midtrans_client_key' => ['nullable', 'string', 'max:255'],
            'smtp_email' => ['nullable', 'email', 'max:255'],
            'smtp_app_password' => ['nullable', 'string', 'max:255'],
            'smtp_notification_email' => ['nullable', 'email', 'max:255'],
        ];

        if ($request->input('payment_mode') === 'manual') {
            $rules['payment_bank_name'] = ['required', 'string', 'max:255'];
            $rules['payment_account_number'] = ['required', 'string', 'max:100'];
            $rules['payment_account_holder'] = ['required', 'string', 'max:255'];
        }
        if ($request->input('payment_mode') === 'gateway') {
            $rules['payment_gateway'] = ['required', 'in:xendit,midtrans'];
            $rules['payment_gateway_mode'] = ['required', 'in:sandbox,production'];
        }

        $validated = $request->validate($rules, [
            'warna_primary.regex' => 'Warna utama harus berupa kode hex valid.',
            'warna_secondary.regex' => 'Warna sekunder harus berupa kode hex valid.',
            'payment_bank_name.required' => 'Nama bank wajib diisi untuk pembayaran manual.',
            'payment_account_number.required' => 'Nomor rekening wajib diisi untuk pembayaran manual.',
            'payment_account_holder.required' => 'Nama pemilik rekening wajib diisi untuk pembayaran manual.',
        ]);

        $smtpHost = $profile->smtp_host ?: config('mail.mailers.smtp.host', 'smtp.gmail.com');
        $smtpPort = $profile->smtp_port ?: (int) config('mail.mailers.smtp.port', 587);
        $smtpEncryption = $profile->smtp_encryption ?: (config('mail.mailers.smtp.scheme') ?? 'tls');
        $smtpEmail = $validated['smtp_email'] ?? $profile->smtp_email;

        $newPassword = trim((string) ($validated['smtp_app_password'] ?? ''));
        try {
            $existingSmtpPassword = $profile->smtp_app_password ?? null;
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            $existingSmtpPassword = null;
        }
        $smtpPassword = $newPassword !== '' ? $newPassword : $existingSmtpPassword;

        // Jika user mengosongkan email & password SMTP, hapus semua konfigurasi SMTP.
        $shouldClearSmtp = empty($validated['smtp_email'] ?? null) && $newPassword === '';
        if ($shouldClearSmtp) {
            $validated['smtp_email'] = null;
            $validated['smtp_app_password'] = null;
            $validated['smtp_notification_email'] = null;
            $validated['smtp_host'] = null;
            $validated['smtp_port'] = null;
            $validated['smtp_encryption'] = null;
        } elseif ($newPassword === '') {
            unset($validated['smtp_app_password']);
        }

        $sensitiveKeys = [
            'xendit_secret_key',
            'xendit_webhook_token',
            'midtrans_server_key',
            'midtrans_client_key',
        ];

        foreach ($sensitiveKeys as $key) {
            $newValue = trim((string) ($validated[$key] ?? ''));
            if ($newValue === '') {
                unset($validated[$key]);
                continue;
            }

            try {
                $existingValue = (string) ($profile->{$key} ?? '');
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                $existingValue = '';
            }

            if ($existingValue !== '' && hash_equals($existingValue, $newValue)) {
                unset($validated[$key]);
            } else {
                $validated[$key] = $newValue;
            }
        }

        $sensitiveChanged = false;
        foreach ($sensitiveKeys as $key) {
            if (array_key_exists($key, $validated)) {
                $sensitiveChanged = true;
                break;
            }
        }

        if ($sensitiveChanged) {
            $request->validate([
                'admin_password' => ['required', 'string'],
            ], [
                'admin_password.required' => 'Password admin wajib diisi untuk mengubah kredensial pembayaran.',
            ]);

            $user = $request->user();
            if (!$user || !Hash::check((string) $request->input('admin_password'), $user->password)) {
                return back()
                    ->withErrors(['admin_password' => 'Password admin tidak valid.'])
                    ->withInput($request->except([
                        'admin_password',
                        'smtp_app_password',
                        ...$sensitiveKeys,
                    ]))
                    ->with('active_tab', $request->input('settings_tab', 'payment'));
            }
        }

        // SMTP wajib lengkap hanya jika memang dikonfigurasi/diaktifkan.
        $smtpConfigured = !empty($profile->smtp_email) || !empty($existingSmtpPassword);
        $smtpRequested = $request->filled('smtp_email')
            || $request->filled('smtp_app_password')
            || $request->filled('smtp_notification_email');
        $shouldValidateSmtp = $smtpConfigured || $smtpRequested;

        if ($shouldValidateSmtp && (!$smtpEmail || !$smtpPassword)) {
            return back()
                ->withErrors([
                    'smtp_email' => 'Konfigurasi SMTP belum lengkap. Isi email SMTP dan sandi aplikasi.',
                ])
                ->withInput($request->except('smtp_app_password'));
        }

        if ($shouldValidateSmtp && !$shouldClearSmtp) {
            $validated['smtp_host'] = $smtpHost;
            $validated['smtp_port'] = $smtpPort;
            $validated['smtp_encryption'] = $smtpEncryption;
        }

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->storeBrandingImage(
                $request->file('logo'),
                $profile->logo ?? null,
                'brand-logo'
            );
        } else {
            unset($validated['logo']);
        }

        if ($request->hasFile('favicon')) {
            $validated['favicon'] = $this->storeBrandingImage(
                $request->file('favicon'),
                $profile->favicon ?? null,
                'brand-favicon'
            );
        } else {
            unset($validated['favicon']);
        }

        $validated['warna_secondary'] = $validated['warna_secondary'] ?? '#F3F3F3';
        $validated['enable_certificate_management'] = false;
        $validated['header_primary_color'] = $request->boolean('header_primary_color');
        $validated['sidebar_primary_color'] = $request->boolean('sidebar_primary_color');
        $validated['enable_utbk_types'] = false;
        $validated['payment_mode'] = $validated['payment_mode'] ?? 'gateway';
        $validated['payment_gateway'] = $validated['payment_gateway']
            ?? ($profile->payment_gateway ?? 'xendit');
        $validated['payment_gateway_mode'] = $validated['payment_gateway_mode']
            ?? ($profile->payment_gateway_mode ?? 'sandbox');
        if (array_key_exists('smtp_notification_email', $validated) && $validated['smtp_notification_email'] === null) {
            // User sengaja mengosongkan field ini
            $validated['smtp_notification_email'] = null;
        } else {
            $validated['smtp_notification_email'] = $validated['smtp_notification_email']
                ?? ($validated['smtp_email'] ?? $profile->smtp_notification_email);
        }

        $changedFields = [];
        foreach ($validated as $key => $value) {
            if (in_array($key, $sensitiveKeys, true)) {
                $changedFields[] = $key . ':updated';
                continue;
            }
            if ($key === 'smtp_app_password') {
                $changedFields[] = 'smtp_app_password:updated';
                continue;
            }
            $original = $profile->exists ? $profile->getOriginal($key) : null;
            if ((string) $original !== (string) $value) {
                $changedFields[] = $key;
            }
        }

        $profile->fill($validated);

        if (empty($profile->logo)) {
            $profile->logo = 'img/logo/logo-copoit.png';
        }

        try {
            $profile->save();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal menyimpan pengaturan', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['general' => 'Gagal menyimpan pengaturan: ' . $e->getMessage()])
                ->withInput($request->except(['admin_password', 'smtp_app_password']))
                ->with('active_tab', $request->input('settings_tab', 'identity'));
        }

        ActivityLogger::log('settings_updated', 'success', $request->user(), [
            'changes' => $changedFields,
        ], $request);

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Pengaturan berhasil diperbarui.')
            ->with('active_tab', $request->input('settings_tab', 'identity'));
    }

    private function storeBrandingImage($file, ?string $existingPath = null, string $prefix = 'brand'): string
    {
        $directory = public_path('logo');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');
        $filename = $prefix . '-' . now()->format('YmdHis') . '.' . $extension;

        $relativePath = 'logo/' . $filename;

        $this->deleteBrandingImage($existingPath);

        $file->move($directory, $filename);

        return $relativePath;
    }

    private function deleteBrandingImage(?string $path): void
    {
        if (!$path) {
            return;
        }

        $normalized = ltrim($path, '/');

        if (Str::startsWith($normalized, 'storage/')) {
            $relativePath = Str::after($normalized, 'storage/');
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }

            return;
        }

        if (Str::startsWith($normalized, 'logo/')) {
            $fullPath = public_path($normalized);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }
}
