<?php

namespace Tests\Unit;

use App\Http\Controllers\superadmin\GeneralSettingController;
use App\Models\AiModelPricing;
use App\Models\ClientProfile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class SuperAdminGeneralSettingControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('ai_model_pricings');
        Schema::create('ai_model_pricings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('model', 120);
            $table->decimal('input_per_million_usd', 16, 6);
            $table->decimal('output_per_million_usd', 16, 6);
            $table->decimal('usd_to_idr', 16, 4);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['provider', 'model']);
        });
        AiModelPricing::insert([
            ['provider' => 'openai', 'model' => 'gpt-4o-mini', 'input_per_million_usd' => 0.15, 'output_per_million_usd' => 0.60, 'usd_to_idr' => 16000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['provider' => 'openai', 'model' => 'gpt-4o', 'input_per_million_usd' => 2.50, 'output_per_million_usd' => 10.00, 'usd_to_idr' => 16000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['provider' => 'openai', 'model' => 'gpt-5.4-mini', 'input_per_million_usd' => 0.75, 'output_per_million_usd' => 4.50, 'usd_to_idr' => 16000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        Cache::flush();
    }

    public function test_it_loads_only_supported_text_gpt_models_from_openai(): void
    {
        Cache::flush();
        Http::fake([
            'api.openai.test/v1/models' => Http::response([
                'data' => [
                    ['id' => 'gpt-4o-mini'],
                    ['id' => 'gpt-4o'],
                    ['id' => 'gpt-5.4-mini'],
                    ['id' => 'gpt-4.1-mini'],
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

        $controller = app(GeneralSettingController::class);
        $availableModelsMethod = new ReflectionMethod(GeneralSettingController::class, 'availableOpenAiDiscussionModels');
        $openAiModels = $availableModelsMethod->invoke($controller, $profile);
        $method = new ReflectionMethod(GeneralSettingController::class, 'aiDiscussionModels');
        $models = $method->invoke($controller, $openAiModels);

        $this->assertArrayHasKey('gpt-4o', $models);
        $this->assertArrayHasKey('gpt-4o-mini', $models);
        $this->assertArrayHasKey('gpt-5.4-mini', $models);
        $this->assertArrayNotHasKey('gpt-4.1-mini', $models);
        $this->assertArrayNotHasKey('gpt-4o-audio-preview', $models);
        $this->assertArrayNotHasKey('gpt-realtime', $models);
        $this->assertArrayHasKey('gemini-2.5-flash', $models);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openai.test/v1/models'
            && $request->header('Authorization')[0] === 'Bearer test-openai-key');
    }
}
