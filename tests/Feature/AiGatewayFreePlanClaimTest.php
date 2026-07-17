<?php

namespace Tests\Feature;

use App\Http\Controllers\superadmin\AiUsageController;
use App\Models\AiGatewayClient;
use App\Models\AiGatewayPlan;
use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayTransaction;
use App\Models\AiGatewayUsageLog;
use App\Models\AiModelPricing;
use App\Services\AiDiscussionService;
use App\Services\AiGatewayCostService;
use App\Services\AiGatewayPaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AiGatewayFreePlanClaimTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createAiGatewaySchema();
    }

    public function test_active_free_plan_is_exposed_by_plans_endpoint(): void
    {
        $plan = $this->freePlan();

        $this->getJson('/api/ai-gateway/plans')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $plan->id,
                'price' => 0,
                'is_free' => true,
            ]);
    }

    public function test_free_plan_claim_activates_immediately_without_payment_provider(): void
    {
        [$client, $key] = $this->gatewayClient();
        $plan = $this->freePlan(['duration_days' => 0]);
        Http::fake();

        $response = $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/checkout', [
                'plan_id' => $plan->id,
                'external_user_id' => 'participant-123',
                'customer_name' => 'Peserta Gratis',
                'customer_email' => 'peserta@example.com',
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'activated' => true,
                'claimed' => true,
                'already_claimed' => false,
                'invoice_url' => null,
            ]);

        Http::assertNothingSent();
        $this->assertDatabaseHas('ai_gateway_subscriptions', [
            'ai_gateway_client_id' => $client->id,
            'ai_gateway_plan_id' => $plan->id,
            'external_user_id' => 'participant-123',
            'status' => 'active',
            'ends_at' => null,
        ]);
        $this->assertDatabaseHas('ai_gateway_transactions', [
            'ai_gateway_client_id' => $client->id,
            'ai_gateway_plan_id' => $plan->id,
            'provider' => 'free_claim',
            'amount' => 0,
            'status' => 'paid',
        ]);
    }

    public function test_same_participant_can_only_claim_same_free_plan_once(): void
    {
        [$client, $key] = $this->gatewayClient();
        $plan = $this->freePlan();
        $payload = [
            'plan_id' => $plan->id,
            'external_user_id' => 'participant-123',
            'customer_name' => 'Peserta Gratis',
        ];

        $first = $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/checkout', $payload)
            ->assertOk()
            ->json();
        $second = $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/checkout', $payload);

        $second->assertOk()->assertJson([
            'activated' => true,
            'claimed' => false,
            'already_claimed' => true,
            'external_id' => $first['external_id'],
        ]);
        $this->assertSame(1, AiGatewaySubscription::query()->where('ai_gateway_client_id', $client->id)->count());
        $this->assertSame(1, AiGatewayTransaction::query()->where('ai_gateway_client_id', $client->id)->count());
    }

    public function test_different_participants_can_claim_the_same_free_plan(): void
    {
        [$client, $key] = $this->gatewayClient();
        $plan = $this->freePlan();

        foreach (['participant-1', 'participant-2'] as $externalUserId) {
            $this->withHeader('X-AI-Gateway-Key', $key)
                ->postJson('/api/ai-gateway/checkout', [
                    'plan_id' => $plan->id,
                    'external_user_id' => $externalUserId,
                ])
                ->assertOk()
                ->assertJson(['activated' => true, 'claimed' => true]);
        }

        $this->assertSame(2, AiGatewaySubscription::query()->where('ai_gateway_client_id', $client->id)->count());
    }

    public function test_subscription_status_reports_claimed_free_plan_ids(): void
    {
        [, $key] = $this->gatewayClient();
        $plan = $this->freePlan();
        $payload = [
            'plan_id' => $plan->id,
            'external_user_id' => 'participant-123',
        ];

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/checkout', $payload)
            ->assertOk();

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->getJson('/api/ai-gateway/subscription?external_user_id=participant-123')
            ->assertOk()
            ->assertJsonPath('subscription.ai_gateway_plan_id', $plan->id)
            ->assertJsonPath('claimed_free_plan_ids.0', $plan->id);
    }

    public function test_paid_plan_still_creates_pending_payment_invoice(): void
    {
        [$client, $key] = $this->gatewayClient();
        $plan = $this->freePlan([
            'name' => 'Pembahasan AI Pro',
            'slug' => 'pembahasan-ai-pro',
            'price' => 25000,
        ]);
        $paymentService = Mockery::mock(AiGatewayPaymentService::class);
        $paymentService->shouldReceive('createCheckout')->once()->andReturn([
            'success' => true,
            'provider' => 'xendit',
            'provider_id' => 'invoice-123',
            'url' => 'https://checkout.xendit.test/invoice-123',
            'details' => ['invoice_url' => 'https://checkout.xendit.test/invoice-123'],
        ]);
        $this->app->instance(AiGatewayPaymentService::class, $paymentService);

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/checkout', [
                'plan_id' => $plan->id,
                'external_user_id' => 'participant-123',
                'customer_name' => 'Peserta Pro',
                'customer_email' => 'pro@example.com',
            ])
            ->assertOk()
            ->assertJson([
                'activated' => false,
                'claimed' => false,
                'invoice_url' => 'https://checkout.xendit.test/invoice-123',
            ]);

        $this->assertDatabaseHas('ai_gateway_subscriptions', [
            'ai_gateway_client_id' => $client->id,
            'external_user_id' => 'participant-123',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('ai_gateway_transactions', [
            'provider' => 'xendit',
            'amount' => 25000,
            'status' => 'pending',
        ]);
    }

    public function test_discussion_rejects_participant_without_active_subscription(): void
    {
        [, $key] = $this->gatewayClient();

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/discussion', $this->discussionPayload('participant-without-plan'))
            ->assertForbidden()
            ->assertJsonPath('message', 'Paket Diskusi AI belum aktif. Silakan beli atau klaim paket AI terlebih dahulu.');
    }

    public function test_one_participant_claim_does_not_grant_access_to_another_participant(): void
    {
        [, $key] = $this->gatewayClient();
        $plan = $this->freePlan();

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/checkout', [
                'plan_id' => $plan->id,
                'external_user_id' => 'participant-owner',
            ])
            ->assertOk();

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/discussion', $this->discussionPayload('participant-other'))
            ->assertForbidden()
            ->assertJsonPath('message', 'Paket Diskusi AI belum aktif. Silakan beli atau klaim paket AI terlebih dahulu.');
    }

    public function test_claimed_participant_can_chat_and_usage_is_recorded(): void
    {
        [, $key] = $this->gatewayClient();
        $plan = $this->freePlan();
        AiModelPricing::query()->create([
            'provider' => 'test-provider',
            'model' => 'test-model',
            'input_per_million_usd' => 1,
            'output_per_million_usd' => 2,
            'usd_to_idr' => 16000,
            'is_active' => true,
        ]);

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/checkout', [
                'plan_id' => $plan->id,
                'external_user_id' => 'participant-123',
            ])
            ->assertOk();

        $ai = Mockery::mock(AiDiscussionService::class);
        $ai->shouldReceive('chat')->once()->andReturn([
            'message' => 'Jawaban AI',
            'model' => 'test-model',
            'provider' => 'test-provider',
            'usage' => ['input' => 100, 'output' => 50, 'total' => 150],
            'response_time_ms' => 20,
        ]);
        $this->app->instance(AiDiscussionService::class, $ai);

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/discussion', $this->discussionPayload('participant-123'))
            ->assertOk()
            ->assertJsonPath('quota.type', 'free_package')
            ->assertJsonPath('quota.tokens_used', 150)
            ->assertJsonPath('quota.chats_used', 1);

        $this->assertDatabaseHas('ai_gateway_subscriptions', [
            'external_user_id' => 'participant-123',
            'tokens_used' => 150,
            'chats_used' => 1,
        ]);
        $usage = AiGatewayUsageLog::query()->where('external_user_id', 'participant-123')->sole();
        $this->assertSame(150, $usage->total_tokens);
        $this->assertSame(1.6, (float) $usage->input_cost_idr);
        $this->assertSame(1.6, (float) $usage->output_cost_idr);

        $paymentPage = app(AiUsageController::class)->gatewayPayments(
            Request::create('/super-admin/ai-gateway-payments'),
            app(AiGatewayCostService::class),
        );
        $financialSummary = $paymentPage->getData()['financialSummary'];
        $this->assertSame(0, $financialSummary['gross_income']);
        $this->assertSame(3, $financialSummary['api_expense']);
        $this->assertSame(-3, $financialSummary['net_income']);
    }

    private function gatewayClient(): array
    {
        $key = 'aigw_test_key';
        $client = AiGatewayClient::query()->create([
            'name' => 'CPNS Academy',
            'slug' => 'cpns-academy',
            'api_key_hash' => hash('sha256', $key),
            'monthly_token_limit' => 0,
            'is_active' => true,
        ]);

        return [$client, $key];
    }

    private function freePlan(array $overrides = []): AiGatewayPlan
    {
        return AiGatewayPlan::query()->create(array_merge([
            'name' => 'Pembahasan AI Gratis',
            'slug' => 'pembahasan-ai-gratis',
            'price' => 0,
            'token_limit' => 10000,
            'chat_limit' => 0,
            'duration_days' => 30,
            'is_active' => true,
        ], $overrides));
    }

    private function discussionPayload(string $externalUserId): array
    {
        return [
            'message' => 'Tolong jelaskan soal ini.',
            'external_user_id' => $externalUserId,
            'question_reference' => 'question-1',
            'context' => [
                'question_text' => 'Contoh isi soal',
                'options' => [],
            ],
        ];
    }

    private function createAiGatewaySchema(): void
    {
        Schema::create('ai_gateway_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('api_key_hash', 64);
            $table->unsignedBigInteger('monthly_token_limit')->default(0);
            $table->unsignedBigInteger('free_token_limit')->default(0);
            $table->unsignedInteger('free_chat_limit')->default(0);
            $table->string('base_url', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_gateway_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('token_limit')->default(0);
            $table->unsignedInteger('chat_limit')->default(0);
            $table->unsignedInteger('duration_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('ai_gateway_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_gateway_client_id');
            $table->unsignedBigInteger('ai_gateway_plan_id');
            $table->unsignedBigInteger('token_limit')->default(0);
            $table->unsignedInteger('chat_limit')->default(0);
            $table->string('external_user_id', 120)->nullable();
            $table->string('external_user_name')->nullable();
            $table->string('external_user_email')->nullable();
            $table->string('free_claim_key', 64)->nullable()->unique();
            $table->string('status')->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('tokens_used')->default(0);
            $table->unsignedInteger('chats_used')->default(0);
            $table->timestamps();
        });
        Schema::create('ai_gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_gateway_client_id');
            $table->unsignedBigInteger('ai_gateway_plan_id');
            $table->unsignedBigInteger('ai_gateway_subscription_id')->nullable();
            $table->string('external_id')->unique();
            $table->string('provider')->default('xendit');
            $table->string('provider_invoice_id')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('pending');
            $table->json('details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_gateway_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_gateway_client_id');
            $table->string('external_user_id', 120)->nullable();
            $table->string('external_user_name')->nullable();
            $table->string('external_user_email')->nullable();
            $table->string('model', 120);
            $table->string('provider', 30);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->decimal('input_per_million_usd', 16, 6)->nullable();
            $table->decimal('output_per_million_usd', 16, 6)->nullable();
            $table->decimal('usd_to_idr', 16, 4)->nullable();
            $table->decimal('input_cost_idr', 20, 6)->nullable();
            $table->decimal('output_cost_idr', 20, 6)->nullable();
            $table->string('question_reference', 120)->nullable();
            $table->string('origin_base_url', 2048)->nullable();
            $table->timestamps();
        });
        Schema::create('ai_gateway_user_trials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_gateway_client_id');
            $table->string('external_user_id', 120);
            $table->string('external_user_name')->nullable();
            $table->string('external_user_email')->nullable();
            $table->unsignedBigInteger('tokens_used')->default(0);
            $table->unsignedInteger('chats_used')->default(0);
            $table->timestamps();
            $table->unique(['ai_gateway_client_id', 'external_user_id']);
        });
        Schema::create('ai_model_pricings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('model', 120);
            $table->decimal('input_per_million_usd', 16, 6)->default(0);
            $table->decimal('output_per_million_usd', 16, 6)->default(0);
            $table->decimal('usd_to_idr', 16, 4)->default(16000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['provider', 'model']);
        });
    }
}
