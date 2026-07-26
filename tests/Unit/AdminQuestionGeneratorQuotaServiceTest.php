<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AdminQuestionGeneratorQuotaService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminQuestionGeneratorQuotaServiceTest extends TestCase
{
    public function test_it_detects_an_unconfigured_gateway(): void
    {
        config()->set('services.ai_gateway.url', null);
        config()->set('services.ai_gateway.key', null);

        $this->assertFalse(app(AdminQuestionGeneratorQuotaService::class)->isConfigured());
    }

    public function test_summary_reads_the_active_generator_subscription_from_gateway(): void
    {
        config()->set('services.ai_gateway.url', 'https://gateway.test/api/ai-gateway/discussion');
        config()->set('services.ai_gateway.key', 'test-key');
        Http::fake([
            'https://gateway.test/api/ai-gateway/subscription*' => Http::response([
                'subscriptions' => [[
                    'token_limit' => 100000,
                    'tokens_used' => 25000,
                    'plan' => ['name' => 'Generator Soal S'],
                ]],
            ]),
        ]);
        $user = new User;
        $user->forceFill(['id' => 42, 'name' => 'Admin Test', 'email' => 'admin@example.test']);

        $summary = app(AdminQuestionGeneratorQuotaService::class)->summary($user);

        $this->assertSame('Generator Soal S', $summary['plan_name']);
        $this->assertSame(75000, $summary['remaining_tokens']);
        $this->assertSame('60–75', $summary['remaining_question_estimate']['label']);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/ai-gateway/subscription')
            && str_contains($request->url(), 'scope=admin_question_generator')
            && str_contains($request->url(), 'external_user_id=42'));
    }
}
