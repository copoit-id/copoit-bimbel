<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Services\ActivityLogger;
use App\Models\ParticipantDestinationCategory;
use App\Rules\SafeName;
use App\Services\ParticipantDestinationSelectionService;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $destinationCategories = ParticipantDestinationCategory::query()
            ->root()
            ->active()
            ->with(['activeChildren'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('user.pages.profile.index', compact('user', 'destinationCategories'));
    }

    public function update(Request $request, ParticipantDestinationSelectionService $destinationSelectionService)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255', new SafeName()],
            'phone' => ['nullable', 'string', 'regex:/^62[0-9]{8,14}$/'],
            'date_of_birth' => 'nullable|date|before:today',
            'education_level' => ['nullable', 'string', 'max:100'],
            'origin_institution' => ['nullable', 'string', 'max:255'],
        ]);
        $destinationPayload = $destinationSelectionService->validate(
            $request,
            $destinationSelectionService->isRequired()
        );
        $secondDestinationPayload = $destinationSelectionService->validateSecond($request);

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'education_level' => $request->education_level,
            'origin_institution' => $request->origin_institution,
            ...$destinationPayload,
            ...$secondDestinationPayload,
        ]);

        ActivityLogger::log('profile_updated', 'success', $user, [], $request);

        return redirect()->route('user.profile.index')
            ->with('success', 'Profile berhasil diperbarui');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password saat ini tidak sesuai',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        ActivityLogger::log('password_changed', 'success', $user, [], $request);

        return redirect()->route('user.profile.index')
            ->with('success', 'Password berhasil diperbarui');
    }
}
