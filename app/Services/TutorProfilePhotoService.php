<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

class TutorProfilePhotoService
{
    public function store(UploadedFile $photo): string
    {
        try {
            $image = (new ImageManager(new Driver))
                ->read($photo->getRealPath())
                ->cover(640, 640);
            $path = 'tutor-profile-photos/'.Str::uuid().'.webp';

            Storage::disk('public')->put(
                $path,
                $image->toWebp(82)->toString()
            );

            return $path;
        } catch (Throwable $exception) {
            report($exception);

            return $photo->store('tutor-profile-photos', 'public');
        }
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
