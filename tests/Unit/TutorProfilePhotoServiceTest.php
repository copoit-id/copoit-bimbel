<?php

namespace Tests\Unit;

use App\Services\TutorProfilePhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Tests\TestCase;

class TutorProfilePhotoServiceTest extends TestCase
{
    public function test_profile_photo_is_cropped_and_optimized_as_webp(): void
    {
        Storage::fake('public');
        $photo = UploadedFile::fake()->image('profile.png', 1200, 800);

        $path = app(TutorProfilePhotoService::class)->store($photo);
        $image = (new ImageManager(new Driver))
            ->read(Storage::disk('public')->path($path));

        Storage::disk('public')->assertExists($path);
        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame(640, $image->width());
        $this->assertSame(640, $image->height());
    }
}
