<?php

namespace Tests\Unit;

use App\Http\Controllers\superadmin\GeneralSettingController;
use App\Models\ClientProfile;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class SuperAdminGeneralSettingControllerTest extends TestCase
{
    public function test_it_loads_only_supported_text_gpt_models_from_openai(): void
    {
        Cache::flush();
        Http::fake([
            'api.openai.test/v1/models' => Http::response([
                'data' => [
                    ['id' => 'gpt-4o-mini'],
                    ['id' => 'gpt-4o'],
                    ['id' => 'gpt-4o-audio-preview'],
                    ['id' => 'gpt-realtime'],
                    ['id' => 'text-embedding-3-small'],
                ],
            ]),
        ]);
        $profile = new ClientProfile();
        $profile->ai_discussion_settings = [
            'providers' => [
                'openai' => [
                    'api_key' => 'test-openai-key',
                    'base_url' => 'https://api.openai.test/v1',
                ],
            ],
        ];

        $method = new ReflectionMethod(GeneralSettingController::class, 'aiDiscussionModels');
        $models = $method->invoke(app(GeneralSettingController::class), $profile);

        $this->assertArrayHasKey('gpt-4o', $models);
        $this->assertArrayHasKey('gpt-4o-mini', $models);
        $this->assertArrayNotHasKey('gpt-4o-audio-preview', $models);
        $this->assertArrayNotHasKey('gpt-realtime', $models);
        $this->assertArrayHasKey('gemini-2.5-flash', $models);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openai.test/v1/models'
            && $request->header('Authorization')[0] === 'Bearer test-openai-key');
    }
}
