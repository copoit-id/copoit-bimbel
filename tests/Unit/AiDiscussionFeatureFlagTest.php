<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureClientFeatureEnabled;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class AiDiscussionFeatureFlagTest extends TestCase
{
    public function test_ai_discussion_is_unavailable_when_super_admin_disables_it(): void
    {
        config([
            'client.branding.ai_discussion_feature_enabled' => false,
            'client.branding.ai_discussion_settings' => ['enabled' => false],
        ]);

        $this->expectException(NotFoundHttpException::class);

        app(EnsureClientFeatureEnabled::class)->handle(
            Request::create('/user/ai-learning-tools'),
            fn () => response('allowed'),
            'ai-discussion',
        );
    }

    public function test_ai_discussion_requires_the_saved_ai_setting_to_be_enabled(): void
    {
        config([
            'client.branding.ai_discussion_feature_enabled' => true,
            'client.branding.ai_discussion_settings' => ['enabled' => false],
        ]);

        $this->expectException(NotFoundHttpException::class);

        app(EnsureClientFeatureEnabled::class)->handle(
            Request::create('/user/ai-learning-tools'),
            fn () => response('allowed'),
            'ai-discussion',
        );
    }

    public function test_ai_discussion_is_available_when_both_super_admin_settings_are_enabled(): void
    {
        config([
            'client.branding.ai_discussion_feature_enabled' => true,
            'client.branding.ai_discussion_settings' => ['enabled' => true],
        ]);

        $response = app(EnsureClientFeatureEnabled::class)->handle(
            Request::create('/user/ai-learning-tools'),
            fn () => response('allowed'),
            'ai-discussion',
        );

        $this->assertSame('allowed', $response->getContent());
    }
}
