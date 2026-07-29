<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\GroupBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudyGroupBookingController extends Controller
{
    public function store(Request $request, GroupBookingService $service): RedirectResponse
    {
        $validated = $request->validate([
            'package_id' => ['required', 'integer', 'exists:packages,package_id'],
            'participant_count' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
        $package = Package::query()
            ->active()
            ->where('is_displayed', true)
            ->findOrFail($validated['package_id']);
        $group = $service->create(
            $package,
            $request->user(),
            (int) $validated['participant_count']
        );

        return redirect()
            ->route('user.booking.index')
            ->with('success', "Pengajuan rombel dibuat. Bagikan kode {$group->invite_code} kepada anggota.");
    }

    public function join(Request $request, GroupBookingService $service): RedirectResponse
    {
        $validated = $request->validate([
            'invite_code' => ['required', 'string', 'max:12'],
        ]);
        $group = $service->join($request->user(), $validated['invite_code']);

        return redirect()
            ->route('user.booking.index')
            ->with('success', "Berhasil bergabung ke rombel {$group->invite_code}. Menunggu persetujuan admin.");
    }
}
