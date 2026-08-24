<?php

namespace App\Http\Controllers\tutor;

use App\Http\Controllers\Controller;
use App\Services\TutorProfilePhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class TutorProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $tentor = $request->user()->tentorProfile()
            ->withCount('visibleReviews')
            ->withAvg('visibleReviews', 'rating')
            ->firstOrFail();
        $reviews = $tentor->visibleReviews()
            ->with('user:id,name')
            ->latest()
            ->paginate(10, ['*'], 'reviews_page');
        $reviewCountsByRating = $tentor->visibleReviews()
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        return view('tutor.profile.edit', compact(
            'tentor',
            'reviews',
            'reviewCountsByRating'
        ));
    }

    public function update(
        Request $request,
        TutorProfilePhotoService $photoService
    ): RedirectResponse {
        $validated = $request->validate([
            'profile_photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'remove_photo' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:30'],
            'expertise' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'education' => ['nullable', 'string', 'max:2000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'experience' => ['nullable', 'string', 'max:4000'],
            'certifications' => ['nullable', 'string', 'max:3000'],
            'teaching_method' => ['nullable', 'string', 'max:2000'],
        ]);
        $tentor = $request->user()->tentorProfile()->firstOrFail();
        $oldPhotoPath = $tentor->profile_photo_path;
        $newPhotoPath = null;

        if ($request->hasFile('profile_photo')) {
            $newPhotoPath = $photoService->store($validated['profile_photo']);
        }

        try {
            DB::transaction(function () use (
                $request,
                $tentor,
                $validated,
                $newPhotoPath
            ): void {
                $profilePhotoPath = $tentor->profile_photo_path;
                if ($newPhotoPath) {
                    $profilePhotoPath = $newPhotoPath;
                } elseif ($request->boolean('remove_photo')) {
                    $profilePhotoPath = null;
                }

                $tentor->update([
                    'profile_photo_path' => $profilePhotoPath,
                    'phone' => $validated['phone'] ?? null,
                    'expertise' => $validated['expertise'] ?? null,
                    'bio' => $validated['bio'] ?? null,
                    'education' => $validated['education'] ?? null,
                    'experience_years' => $validated['experience_years'] ?? null,
                    'experience' => $validated['experience'] ?? null,
                    'certifications' => $validated['certifications'] ?? null,
                    'teaching_method' => $validated['teaching_method'] ?? null,
                ]);
            });
        } catch (Throwable $exception) {
            $photoService->delete($newPhotoPath);
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Profil Tutor gagal disimpan. Silakan coba kembali.');
        }

        if (($newPhotoPath || $request->boolean('remove_photo'))
            && $oldPhotoPath !== $newPhotoPath) {
            $photoService->delete($oldPhotoPath);
        }

        return redirect()
            ->route('tutor.profile.edit')
            ->with('success', 'Profil Tutor berhasil diperbarui.');
    }
}
