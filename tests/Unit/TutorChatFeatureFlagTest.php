<?php

namespace Tests\Unit;

use App\Models\ClassModel;
use App\Models\User;
use App\Services\TutorChatService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class TutorChatFeatureFlagTest extends TestCase
{
    public function test_chat_is_unavailable_when_the_feature_is_disabled(): void
    {
        config(['client.branding.tutor_chat_enabled' => false]);

        $this->expectException(NotFoundHttpException::class);

        app(TutorChatService::class)->openForStudent(
            new User(['role' => 'user']),
            new ClassModel(),
        );
    }
}
