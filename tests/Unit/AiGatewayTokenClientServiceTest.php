<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AiGatewayTokenClientService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiGatewayTokenClientServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ai_gateway.url' => 'https://gateway.test/api/ai-gateway/discussion',
            'services.ai_gateway.key' => 'secret-client-key',
        ]);
    }

    public function test_summaries_are_loaded_from_remote_gateway_in_one_request(): void
    {
        Http::fake([
            'gateway.test/*' => Http::response([
                'summaries' => [
                    '10' => [
                        'token_limit' => 15000,
                        'tokens_used' => 3000,
                        'remaining_tokens' => 12000,
                    ],
                ],
            ]),
        ]);

        $summaries = app(AiGatewayTokenClientService::class)->summaries([10, 11]);

        $this->assertSame(12000, $summaries->get('10')['remaining_tokens']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://gateway.test/api/ai-gateway/subscription/token-summaries'
            && $request->hasHeader('X-AI-Gateway-Key', 'secret-client-key')
            && $request['external_user_ids'] === ['10', '11']);
    }

    public function test_add_tokens_is_sent_to_remote_gateway_with_admin_audit_data(): void
    {
        Http::fake([
            'gateway.test/*' => Http::response([
                'subscription_id' => 7,
                'previous_limit' => 10000,
                'new_limit' => 15000,
                'added_tokens' => 5000,
            ]),
        ]);
        $participant = (new User)->forceFill([
            'id' => 10,
            'name' => 'Peserta',
            'email' => 'peserta@example.com',
            'role' => 'user',
        ]);
        $admin = (new User)->forceFill([
            'id' => 2,
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $result = app(AiGatewayTokenClientService::class)->addTokens(
            $participant,
            $admin,
            5000,
            'Bonus event'
        );

        $this->assertSame(15000, $result['new_limit']);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://gateway.test/api/ai-gateway/subscription/tokens'
            && $request['external_user_id'] === '10'
            && $request['tokens'] === 5000
            && $request['reason'] === 'Bonus event'
            && $request['actor_email'] === 'admin@example.com');
    }
}
