<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use App\Rules\SafeName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DemoRequestController extends Controller
{
    public function create(): View
    {
        return view('public.demo-request');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['phone' => $this->normalizeWhatsAppNumber($request->input('phone'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', new SafeName],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^628[0-9]{7,13}$/'],
            'origin_institution' => ['required', 'string', 'max:255'],
            'request_note' => ['nullable', 'string', 'max:5000'],
        ], [
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'phone.regex' => 'Masukkan nomor WhatsApp aktif, contoh 081234567890.',
        ]);

        $hasPendingRequest = DemoRequest::query()
            ->pending()
            ->where('email', $validated['email'])
            ->exists();
        if ($hasPendingRequest) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Pengajuan dengan email ini masih menunggu persetujuan.']);
        }

        DemoRequest::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'origin_institution' => $validated['origin_institution'],
            'request_note' => $this->plainRequestNote($validated['request_note'] ?? null),
            'status' => 'pending',
        ]);

        return redirect()->route('demo-requests.create')
            ->with('success', 'Pengajuan demo berhasil dikirim. Tim kami akan menghubungi Anda melalui WhatsApp.');
    }

    private function plainRequestNote(?string $note): ?string
    {
        $note = trim(strip_tags((string) $note));

        return $note === '' ? null : $note;
    }

    private function normalizeWhatsAppNumber(?string $phone): string
    {
        $normalized = preg_replace('/\D+/', '', (string) $phone) ?: '';

        if (str_starts_with($normalized, '0')) {
            return '62'.substr($normalized, 1);
        }

        if (str_starts_with($normalized, '8')) {
            return '62'.$normalized;
        }

        return $normalized;
    }
}
