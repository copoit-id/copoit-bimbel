<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdminDataResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataResetController extends Controller
{
    public function index(): View
    {
        return view('super-admin.data-reset.index', ['categories' => SuperAdminDataResetService::CATEGORIES]);
    }

    public function destroy(Request $request, SuperAdminDataResetService $dataReset): RedirectResponse
    {
        $validated = $request->validate([
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['string', 'in:'.implode(',', array_keys(SuperAdminDataResetService::CATEGORIES))],
            'confirmation' => ['required', 'in:RESET DATA'],
        ]);

        $deleted = $dataReset->reset($validated['categories']);

        return back()->with('success', 'Reset selesai: '.implode(', ', $deleted).'.');
    }
}
