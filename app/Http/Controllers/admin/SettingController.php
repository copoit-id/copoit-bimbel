<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use Illuminate\Http\Request;
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
        ]);

        return view('admin.pages.settings.index', [
            'profile' => $profile,
            'branding' => $branding,
        ]);
    }

    public function update(Request $request)
    {
        $rules = [
            'nama_bimbel' => ['required', 'string', 'max:255'],
            'warna_primary' => ['required', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'warna_secondary' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'logo' => ['nullable', 'mimes:png,jpg,jpeg,svg,webp', 'max:5120'],
            'favicon' => ['nullable', 'mimes:ico,png,jpg,jpeg,svg,webp', 'max:4096'],
            'payment_mode' => ['required', 'in:gateway,manual'],
            'allow_register' => ['nullable', 'boolean'],
            'payment_bank_name' => ['nullable', 'string', 'max:255'],
            'payment_account_number' => ['nullable', 'string', 'max:100'],
            'payment_account_holder' => ['nullable', 'string', 'max:255'],
            'payment_bank_note' => ['nullable', 'string', 'max:255'],
        ];

        if ($request->input('payment_mode') === 'manual') {
            $rules['payment_bank_name'] = ['required', 'string', 'max:255'];
            $rules['payment_account_number'] = ['required', 'string', 'max:100'];
            $rules['payment_account_holder'] = ['required', 'string', 'max:255'];
        }

        $validated = $request->validate($rules, [
            'warna_primary.regex' => 'Warna utama harus berupa kode hex valid.',
            'warna_secondary.regex' => 'Warna sekunder harus berupa kode hex valid.',
            'payment_bank_name.required' => 'Nama bank wajib diisi untuk pembayaran manual.',
            'payment_account_number.required' => 'Nomor rekening wajib diisi untuk pembayaran manual.',
            'payment_account_holder.required' => 'Nama pemilik rekening wajib diisi untuk pembayaran manual.',
        ]);

        $profile = ClientProfile::query()->first() ?? new ClientProfile();

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
        $validated['allow_register'] = $request->boolean('allow_register');

        $profile->fill($validated);

        if (empty($profile->logo)) {
            $profile->logo = 'img/logo/logo-copoit.png';
        }

        $profile->save();

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Pengaturan branding berhasil diperbarui.');
    }

    private function storeBrandingImage($file, ?string $existingPath = null, string $prefix = 'brand'): string
    {
        $directory = public_path('logo');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');
        $filename = $prefix . '.' . $extension;

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
