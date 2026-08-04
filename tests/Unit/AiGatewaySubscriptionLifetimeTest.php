<?php

namespace Tests\Unit;

use App\Http\Controllers\superadmin\AiUsageController;
use App\Models\AiGatewayPlan;
use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayTransaction;
use App\Services\AiGatewaySubscriptionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiGatewaySubscriptionLifetimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('ai_gateway_transactions');
        Schema::dropIfExists('ai_gateway_subscriptions');
        Schema::dropIfExists('ai_gateway_plans');

        Schema::create('ai_gateway_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('token_limit');
            $table->unsignedInteger('chat_limit')->default(0);
            $table->unsignedInteger('duration_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('ai_gateway_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ai_gateway_client_id');
            $table->unsignedBigInteger('ai_gateway_plan_id');
            $table->string('external_user_id');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('token_limit')->default(0);
            $table->unsignedInteger('chat_limit')->default(0);
            $table->unsignedBigInteger('tokens_used')->default(0);
            $table->unsignedInteger('chats_used')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_gateway_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ai_gateway_client_id');
            $table->unsignedBigInteger('ai_gateway_plan_id');
            $table->unsignedBigInteger('ai_gateway_subscription_id');
            $table->string('provider')->default('ipaymu');
            $table->string('status')->default('pending');
            $table->json('details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_lifetime_plan_activates_with_null_expiration(): void
    {
        $plan = $this->createLifetimePlan();
        $subscription = $this->createPendingSubscription($plan);
        $transaction = $this->createTransaction($plan, $subscription);

        app(AiGatewaySubscriptionService::class)->activateTransaction($transaction);

        $subscription->refresh();
        $transaction->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertNull($subscription->ends_at);
        $this->assertSame('paid', $transaction->status);
        $this->assertSame('provider', data_get($transaction->details, 'confirmation_source'));
    }

    public function test_not_expired_scope_includes_null_expiration_and_excludes_past_expiration(): void
    {
        $plan = $this->createLifetimePlan();
        $lifetime = $this->createPendingSubscription($plan, ['status' => 'active', 'ends_at' => null]);
        $future = $this->createPendingSubscription($plan, ['status' => 'active', 'ends_at' => now()->addDay()]);
        $past = $this->createPendingSubscription($plan, ['status' => 'active', 'ends_at' => now()->subDay()]);

        $ids = AiGatewaySubscription::query()->notExpired()->pluck('id');

        $this->assertTrue($ids->contains($lifetime->id));
        $this->assertTrue($ids->contains($future->id));
        $this->assertFalse($ids->contains($past->id));
    }

    public function test_unverified_unused_legacy_ipaymu_payment_can_be_reset_to_pending(): void
    {
        $plan = $this->createLifetimePlan();
        $subscription = $this->createPendingSubscription($plan, [
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
        $transaction = $this->createTransaction($plan, $subscription);
        $transaction->update(['status' => 'paid', 'paid_at' => now(), 'details' => []]);

        app(AiUsageController::class)->resetUnverifiedGatewayPayment($transaction);

        $transaction->refresh();
        $subscription->refresh();
        $this->assertSame('pending', $transaction->status);
        $this->assertNull($transaction->paid_at);
        $this->assertSame('pending', $subscription->status);
        $this->assertNull($subscription->starts_at);
        $this->assertNull($subscription->ends_at);
        $this->assertNotNull(data_get($transaction->details, 'reset_to_pending_at'));
    }

    public function test_manual_reconciliation_activates_paid_pending_subscription_only_once(): void
    {
        $plan = $this->createLifetimePlan();
        $subscription = $this->createPendingSubscription($plan);
        $transaction = $this->createTransaction($plan, $subscription);
        $transaction->update(['status' => 'paid', 'paid_at' => now(), 'details' => []]);
        $service = app(AiGatewaySubscriptionService::class);

        $service->reconcilePaidTransaction($transaction, [
            'source' => 'manual_super_admin',
            'reason' => 'Pembayaran sudah diverifikasi.',
        ]);
        $firstTokenLimit = $subscription->fresh()->token_limit;
        $service->reconcilePaidTransaction($transaction->fresh(), [
            'source' => 'manual_super_admin',
            'reason' => 'Percobaan rekonsiliasi ulang.',
        ]);

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame($firstTokenLimit, $subscription->fresh()->token_limit);
        $this->assertSame(
            'manual_super_admin',
            data_get($transaction->fresh()->details, 'subscription_reconciliation.source'),
        );
    }

    private function createLifetimePlan(): AiGatewayPlan
    {
        return AiGatewayPlan::create([
            'name' => 'Paket Lifetime',
            'slug' => 'paket-lifetime',
            'price' => 25000,
            'token_limit' => 50000,
            'chat_limit' => 0,
            'duration_days' => 0,
            'is_active' => true,
        ]);
    }

    private function createPendingSubscription(AiGatewayPlan $plan, array $overrides = []): AiGatewaySubscription
    {
        return AiGatewaySubscription::create(array_replace([
            'ai_gateway_client_id' => 1,
            'ai_gateway_plan_id' => $plan->id,
            'external_user_id' => '123',
            'status' => 'pending',
            'token_limit' => $plan->token_limit,
            'chat_limit' => $plan->chat_limit,
        ], $overrides));
    }

    private function createTransaction(AiGatewayPlan $plan, AiGatewaySubscription $subscription): AiGatewayTransaction
    {
        return AiGatewayTransaction::create([
            'ai_gateway_client_id' => 1,
            'ai_gateway_plan_id' => $plan->id,
            'ai_gateway_subscription_id' => $subscription->id,
            'provider' => 'ipaymu',
            'status' => 'pending',
        ]);
    }
}
