<?php

namespace Tests\Unit;

use App\Http\Controllers\admin\AiQuestionGeneratorBillingController;
use App\Models\AiGatewayPlan;
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

    public function test_summary_reads_an_active_ai_learning_subscription_from_gateway(): void
    {
        config()->set('services.ai_gateway.url', 'https://gateway.test/api/ai-gateway/discussion');
        config()->set('services.ai_gateway.key', 'test-key');
        Http::fake([
            'https://gateway.test/api/ai-gateway/subscription*' => Http::response([
                'subscriptions' => [[
                    'token_limit' => 100000,
                    'tokens_used' => 25000,
                    'scope' => AiGatewayPlan::SCOPE_LEARNING_TOOLS,
                    'plan' => ['name' => 'Pembahasan S'],
                ]],
            ]),
        ]);
        $user = new User;
        $user->forceFill(['id' => 42, 'name' => 'Admin Test', 'email' => 'admin@example.test']);

        $summary = app(AdminQuestionGeneratorQuotaService::class)->summary($user);

        $this->assertSame('Pembahasan S', $summary['plan_name']);
        $this->assertSame(75000, $summary['remaining_tokens']);
        $this->assertSame('60–75', $summary['remaining_question_estimate']['label']);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/ai-gateway/subscription')
            && str_contains($request->url(), 'scope=admin_question_generator')
            && str_contains($request->url(), 'include_all_scopes=1')
            && str_contains($request->url(), 'external_user_id=42'));
    }

    public function test_admin_generator_checkout_sends_the_admin_generator_scope(): void
    {
        config()->set('services.ai_gateway.url', 'https://gateway.test/api/ai-gateway/discussion');
        config()->set('services.ai_gateway.key', 'test-key');
        Http::fake([
            'https://gateway.test/api/ai-gateway/checkout' => Http::response([
                'activated' => true,
                'already_claimed' => false,
            ]),
        ]);
        $user = new User;
        $user->forceFill(['id' => 42, 'name' => 'Admin Test', 'email' => 'admin@example.test']);
        $request = \Illuminate\Http\Request::create('/admin/ai-question-generator/quota/checkout', 'POST', [
            'plan_id' => 7,
        ]);
        $request->setUserResolver(fn (): User => $user);

        app(AiQuestionGeneratorBillingController::class)->checkout($request);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://gateway.test/api/ai-gateway/checkout'
            && $request->data()['scope'] === AiGatewayPlan::SCOPE_ADMIN_QUESTION_GENERATOR
            && $request->data()['external_user_id'] === '42');
    }
}
