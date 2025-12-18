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
        ]);

        return view('admin.pages.settings.index', [
            'profile' => $profile,
            'branding' => $branding,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nama_bimbel' => ['required', 'string', 'max:255'],
            'warna_primary' => ['required', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'warna_secondary' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'logo' => ['nullable', 'mimes:png,jpg,jpeg,svg', 'max:4096'],
            'favicon' => ['nullable', 'mimes:ico,png,jpg,jpeg', 'max:2048'],
        ], [
            'warna_primary.regex' => 'Warna utama harus berupa kode hex valid.',
            'warna_secondary.regex' => 'Warna sekunder harus berupa kode hex valid.',
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
        $validated['enable_certificate_management'] = $request->has('enable_certificate_management')
            ? $request->boolean('enable_certificate_management')
            : ($profile->enable_certificate_management ?? true);
        $validated['header_primary_color'] = $request->boolean('header_primary_color');
        $validated['sidebar_primary_color'] = $request->boolean('sidebar_primary_color');
        $validated['enable_utbk_types'] = $request->has('enable_utbk_types')
            ? $request->boolean('enable_utbk_types')
            : ($profile->enable_utbk_types ?? true);

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
