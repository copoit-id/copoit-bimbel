<?php

namespace Tests\Unit;

use App\Models\AiGatewayClient;
use App\Models\AiGatewayPlan;
use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayTransaction;
use App\Services\AiGatewayTokenService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class AiGatewayTokenServiceTest extends TestCase
{
    private AiGatewayClient $client;

    private AiGatewayPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        $this->client = AiGatewayClient::query()->create([
            'name' => 'Test Client',
            'slug' => 'test-client',
            'api_key_hash' => hash('sha256', 'test-gateway-key'),
            'is_active' => true,
        ]);
        $this->plan = AiGatewayPlan::query()->create([
            'name' => 'Paket AI',
            'slug' => 'paket-ai',
            'price' => 10000,
            'token_limit' => 10000,
            'chat_limit' => 0,
            'duration_days' => 30,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_add_tokens_to_active_paid_subscription(): void
    {
        $subscription = $this->activePaidSubscription('10', [
            'token_limit' => 10000,
            'tokens_used' => 2500,
        ]);
        $result = app(AiGatewayTokenService::class)->addTokens(
            $this->client,
            '10',
            5000,
            ['reason' => 'Bonus testing', 'actor_name' => 'Admin Test']
        );

        $this->assertSame(10000, $result['previous_limit']);
        $this->assertSame(15000, $result['new_limit']);
        $this->assertSame(15000, $subscription->fresh()->token_limit);
        $this->assertSame(2500, $subscription->fresh()->tokens_used);
        $this->assertDatabaseHas('ai_gateway_token_adjustments', [
            'ai_gateway_subscription_id' => $subscription->id,
            'external_user_id' => '10',
            'tokens_added' => 5000,
            'reason' => 'Bonus testing',
            'actor_name' => 'Admin Test',
        ]);
    }

    public function test_token_summary_only_counts_active_paid_subscriptions(): void
    {
        $this->activePaidSubscription('10', [
            'token_limit' => 12000,
            'tokens_used' => 2000,
        ]);
        AiGatewaySubscription::query()->create([
            'ai_gateway_client_id' => $this->client->id,
            'ai_gateway_plan_id' => $this->plan->id,
            'external_user_id' => '10',
            'status' => 'active',
            'ends_at' => now()->addMonth(),
            'token_limit' => 50000,
            'tokens_used' => 0,
        ]);

        $summary = app(AiGatewayTokenService::class)->summaries($this->client, [10])->get('10');

        $this->assertSame(12000, $summary['token_limit']);
        $this->assertSame(2000, $summary['tokens_used']);
        $this->assertSame(10000, $summary['remaining_tokens']);
    }

    public function test_add_tokens_is_rejected_when_user_has_no_active_package(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('belum memiliki paket AI aktif');

        app(AiGatewayTokenService::class)->addTokens($this->client, '99', 1000);
    }

    private function activePaidSubscription(string $externalUserId, array $overrides = []): AiGatewaySubscription
    {
        $subscription = AiGatewaySubscription::query()->create(array_merge([
            'ai_gateway_client_id' => $this->client->id,
            'ai_gateway_plan_id' => $this->plan->id,
            'external_user_id' => $externalUserId,
            'external_user_name' => 'Peserta Test',
            'external_user_email' => 'peserta@example.com',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'token_limit' => 10000,
            'tokens_used' => 0,
        ], $overrides));

        AiGatewayTransaction::query()->create([
            'ai_gateway_client_id' => $this->client->id,
            'ai_gateway_plan_id' => $this->plan->id,
            'ai_gateway_subscription_id' => $subscription->id,
            'external_id' => 'TEST-'.$subscription->id,
            'provider' => 'test',
            'amount' => $this->plan->price,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return $subscription;
    }

    private function createSchema(): void
    {
        Schema::create('ai_gateway_clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('base_url')->nullable();
            $table->string('api_key_hash', 64);
            $table->unsignedBigInteger('monthly_token_limit')->default(0);
            $table->unsignedBigInteger('free_token_limit')->default(0);
            $table->unsignedInteger('free_chat_limit')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_gateway_plans', function (Blueprint $table): void {
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
        Schema::create('ai_gateway_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ai_gateway_client_id');
            $table->unsignedBigInteger('ai_gateway_plan_id');
            $table->unsignedBigInteger('token_limit')->default(0);
            $table->unsignedInteger('chat_limit')->default(0);
            $table->string('external_user_id', 120)->nullable();
            $table->string('external_user_name')->nullable();
            $table->string('external_user_email')->nullable();
            $table->string('free_claim_key', 64)->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('tokens_used')->default(0);
            $table->unsignedInteger('chats_used')->default(0);
            $table->timestamps();
        });
        Schema::create('ai_gateway_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ai_gateway_client_id');
            $table->unsignedBigInteger('ai_gateway_plan_id');
            $table->unsignedBigInteger('ai_gateway_subscription_id')->nullable();
            $table->string('external_id')->unique();
            $table->string('provider');
            $table->string('provider_invoice_id')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('status');
            $table->json('details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_gateway_token_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ai_gateway_client_id');
            $table->unsignedBigInteger('ai_gateway_subscription_id')->nullable();
            $table->string('external_user_id', 120);
            $table->unsignedBigInteger('tokens_added');
            $table->unsignedBigInteger('previous_token_limit');
            $table->unsignedBigInteger('new_token_limit');
            $table->string('reason');
            $table->string('actor_user_id', 120)->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->string('origin_base_url', 2048)->nullable();
            $table->timestamps();
        });
    }
}
