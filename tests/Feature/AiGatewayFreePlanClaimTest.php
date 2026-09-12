<?php

namespace Tests\Feature;

use App\Http\Controllers\superadmin\AiUsageController;
use App\Http\Controllers\superadmin\AiGatewayPlanController;
use App\Models\AiGatewayClient;
use App\Models\AiGatewayPlan;
use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayTransaction;
use App\Models\AiGatewayUsageLog;
use App\Models\AiModelPricing;
use App\Models\ClientProfile;
use App\Services\AiDiscussionService;
use App\Services\AiGatewayCostService;
use App\Services\AiGatewayPaymentService;
use App\Services\AiGatewaySubscriptionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ])
            ->assertJsonPath('subscription_id', fn ($id): bool => (int) $id > 0)
            ->assertJsonPath('activated_subscription_id', fn ($id): bool => (int) $id > 0);

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

    public function test_archiving_claimed_plan_hides_it_from_new_purchases_without_revoking_existing_access(): void
    {
        [, $key] = $this->gatewayClient();
        $plan = $this->freePlan();

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/checkout', [
                'plan_id' => $plan->id,
                'external_user_id' => 'participant-archived-plan',
            ])
            ->assertOk()
            ->assertJson(['activated' => true]);

        app(AiGatewayPlanController::class)->destroy($plan);

        $this->assertDatabaseHas('ai_gateway_plans', [
            'id' => $plan->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('ai_gateway_subscriptions', [
            'ai_gateway_plan_id' => $plan->id,
            'external_user_id' => 'participant-archived-plan',
            'status' => 'active',
        ]);

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->getJson('/api/ai-gateway/plans')
            ->assertOk()
            ->assertJsonMissing(['id' => $plan->id]);

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/checkout', [
                'plan_id' => $plan->id,
                'external_user_id' => 'participant-new-purchase',
            ])
            ->assertNotFound();

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->getJson('/api/ai-gateway/subscription?external_user_id=participant-archived-plan')
            ->assertOk()
            ->assertJsonPath('subscription.ai_gateway_plan_id', $plan->id)
            ->assertJsonPath('subscription.status', 'active');
    }

    public function test_archived_plan_can_be_activated_again_without_changing_existing_subscription(): void
    {
        [, $key] = $this->gatewayClient();
        $plan = $this->freePlan();

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/checkout', [
                'plan_id' => $plan->id,
                'external_user_id' => 'participant-reactivated-plan',
            ])
            ->assertOk();

        app(AiGatewayPlanController::class)->destroy($plan);

        app(AiGatewayPlanController::class)->activate($plan->fresh());

        $this->assertDatabaseHas('ai_gateway_plans', [
            'id' => $plan->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('ai_gateway_subscriptions', [
            'ai_gateway_plan_id' => $plan->id,
            'external_user_id' => 'participant-reactivated-plan',
            'status' => 'active',
        ]);
    }

    public function test_free_plan_claim_sends_configured_telegram_notification(): void
    {
        [$client, $key] = $this->gatewayClient();
        $plan = $this->freePlan();
        $this->enableTelegramNotifications();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
        ]);

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/checkout', [
                'plan_id' => $plan->id,
                'external_user_id' => 'participant-telegram-free',
                'customer_name' => 'Peserta Telegram',
                'customer_email' => 'telegram@example.com',
            ])
            ->assertOk()
            ->assertJson(['activated' => true, 'claimed' => true]);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === '-1001234567890'
            && str_contains((string) $request['text'], 'Paket AI Learning Gratis Diklaim')
            && str_contains((string) $request['text'], 'Peserta Telegram'));
        $this->assertStringNotContainsString(
            '123456789:test-bot-token',
            (string) DB::table('client_profile')->value('ai_gateway_telegram_settings'),
        );
        $this->assertDatabaseHas('ai_gateway_transactions', [
            'ai_gateway_client_id' => $client->id,
            'status' => 'paid',
        ]);
    }

    public function test_paid_activation_sends_telegram_notification_only_once(): void
    {
        [$client] = $this->gatewayClient();
        $plan = $this->freePlan([
            'name' => 'Pembahasan AI Pro',
            'slug' => 'pembahasan-ai-pro-telegram',
            'price' => 50000,
        ]);
        $this->enableTelegramNotifications();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 2]]),
        ]);
        $subscription = AiGatewaySubscription::query()->create([
            'ai_gateway_client_id' => $client->id,
            'ai_gateway_plan_id' => $plan->id,
            'token_limit' => $plan->token_limit,
            'chat_limit' => $plan->chat_limit,
            'external_user_id' => 'participant-telegram-paid',
            'external_user_name' => 'Peserta Berbayar',
            'external_user_email' => 'paid@example.com',
            'status' => 'pending',
        ]);
        $transaction = AiGatewayTransaction::query()->create([
            'ai_gateway_client_id' => $client->id,
            'ai_gateway_plan_id' => $plan->id,
            'ai_gateway_subscription_id' => $subscription->id,
            'external_id' => 'AIGW-PAID-TELEGRAM-1',
            'provider' => 'xendit',
            'amount' => $plan->price,
            'status' => 'pending',
            'details' => [],
        ]);

        $service = app(AiGatewaySubscriptionService::class);
        $service->activateTransaction($transaction);
        $service->activateTransaction($transaction->fresh());

        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => str_contains((string) $request['text'], 'Paket AI Learning Berhasil Dibayar')
            && str_contains((string) $request['text'], 'Rp 50.000'));
        $this->assertSame(
            'sent',
            data_get($transaction->fresh()->details, 'telegram_notification.status'),
        );
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
            ->assertJsonPath('claimed_free_plan_ids.0', $plan->id)
            ->assertJsonPath('has_inactive_package_history', false);
    }

    public function test_subscription_status_repairs_verified_paid_transaction_with_pending_subscription(): void
    {
        [$client, $key] = $this->gatewayClient();
        $plan = $this->freePlan([
            'name' => 'Pembahasan AI Rekonsiliasi',
            'slug' => 'pembahasan-ai-rekonsiliasi',
            'price' => 25000,
        ]);
        $subscription = AiGatewaySubscription::query()->create([
            'ai_gateway_client_id' => $client->id,
            'ai_gateway_plan_id' => $plan->id,
            'token_limit' => $plan->token_limit,
            'chat_limit' => $plan->chat_limit,
            'external_user_id' => 'participant-reconcile',
            'status' => 'pending',
        ]);
        $transaction = AiGatewayTransaction::query()->create([
            'ai_gateway_client_id' => $client->id,
            'ai_gateway_plan_id' => $plan->id,
            'ai_gateway_subscription_id' => $subscription->id,
            'external_id' => 'AIGW-RECONCILE-1',
            'provider' => 'xendit',
            'amount' => $plan->price,
            'status' => 'paid',
            'paid_at' => now(),
            'details' => [
                'confirmation_source' => 'provider',
                'activated_subscription_id' => $subscription->id,
            ],
        ]);

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->getJson('/api/ai-gateway/subscription?external_user_id=participant-reconcile')
            ->assertOk()
            ->assertJsonPath('subscription.id', $subscription->id)
            ->assertJsonPath('subscription.status', 'active')
            ->assertJsonPath('activations.0.external_id', 'AIGW-RECONCILE-1')
            ->assertJsonPath('activations.0.checkout_subscription_id', $subscription->id)
            ->assertJsonPath('activations.0.activated_subscription_id', $subscription->id);

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame(
            'gateway_status_auto',
            data_get($transaction->fresh()->details, 'subscription_reconciliation.source'),
        );
        $tokenLimit = $subscription->fresh()->token_limit;

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->getJson('/api/ai-gateway/subscription?external_user_id=participant-reconcile')
            ->assertOk();

        $this->assertSame($tokenLimit, $subscription->fresh()->token_limit);
    }

    public function test_subscription_status_does_not_auto_repair_unverified_legacy_payment(): void
    {
        [$client, $key] = $this->gatewayClient();
        $plan = $this->freePlan([
            'name' => 'Pembahasan AI Legacy',
            'slug' => 'pembahasan-ai-legacy',
            'price' => 25000,
        ]);
        $subscription = AiGatewaySubscription::query()->create([
            'ai_gateway_client_id' => $client->id,
            'ai_gateway_plan_id' => $plan->id,
            'token_limit' => $plan->token_limit,
            'chat_limit' => $plan->chat_limit,
            'external_user_id' => 'participant-unverified',
            'status' => 'pending',
        ]);
        AiGatewayTransaction::query()->create([
            'ai_gateway_client_id' => $client->id,
            'ai_gateway_plan_id' => $plan->id,
            'ai_gateway_subscription_id' => $subscription->id,
            'external_id' => 'AIGW-UNVERIFIED-1',
            'provider' => 'ipaymu',
            'amount' => $plan->price,
            'status' => 'paid',
            'paid_at' => now(),
            'details' => [],
        ]);

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->getJson('/api/ai-gateway/subscription?external_user_id=participant-unverified')
            ->assertOk()
            ->assertJsonPath('subscription', null);

        $this->assertSame('pending', $subscription->fresh()->status);
    }

    public function test_subscription_status_does_not_auto_repair_manual_confirmation_source(): void
    {
        [$client, $key] = $this->gatewayClient();
        $plan = $this->freePlan([
            'name' => 'Pembahasan AI Manual',
            'slug' => 'pembahasan-ai-manual',
            'price' => 25000,
        ]);
        $subscription = AiGatewaySubscription::query()->create([
            'ai_gateway_client_id' => $client->id,
            'ai_gateway_plan_id' => $plan->id,
            'token_limit' => $plan->token_limit,
            'chat_limit' => $plan->chat_limit,
            'external_user_id' => 'participant-manual-source',
            'status' => 'pending',
        ]);
        AiGatewayTransaction::query()->create([
            'ai_gateway_client_id' => $client->id,
            'ai_gateway_plan_id' => $plan->id,
            'ai_gateway_subscription_id' => $subscription->id,
            'external_id' => 'AIGW-MANUAL-SOURCE-1',
            'provider' => 'ipaymu',
            'amount' => $plan->price,
            'status' => 'paid',
            'paid_at' => now(),
            'details' => ['confirmation_source' => 'manual_admin'],
        ]);

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->getJson('/api/ai-gateway/subscription?external_user_id=participant-manual-source')
            ->assertOk()
            ->assertJsonPath('subscription', null);

        $this->assertSame('pending', $subscription->fresh()->status);
    }

    public function test_revoked_free_plan_loses_access_and_can_be_claimed_again_without_deleting_history(): void
    {
        [$client, $key] = $this->gatewayClient();
        $plan = $this->freePlan();
        $payload = [
            'plan_id' => $plan->id,
            'external_user_id' => 'participant-reset',
            'customer_name' => 'Peserta Reset',
        ];

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/checkout', $payload)
            ->assertOk()
            ->assertJson(['activated' => true, 'claimed' => true]);

        $subscription = AiGatewaySubscription::query()
            ->where('external_user_id', 'participant-reset')
            ->sole();
        $transaction = $subscription->transactions()->sole();

        app(AiGatewaySubscriptionService::class)->revokeSubscription($subscription, [
            'reason' => 'Reset paket untuk pengujian',
            'actor_user_id' => '1',
            'actor_name' => 'Super Admin',
            'actor_email' => 'superadmin@example.com',
        ]);

        $this->assertDatabaseHas('ai_gateway_subscriptions', [
            'id' => $subscription->id,
            'status' => 'revoked',
            'free_claim_key' => null,
            'revoked_reason' => 'Reset paket untuk pengujian',
            'revoked_by_name' => 'Super Admin',
        ]);
        $this->assertDatabaseHas('ai_gateway_transactions', [
            'id' => $transaction->id,
            'status' => 'paid',
        ]);
        $this->assertNotNull($subscription->fresh()->revoked_at);
        $this->assertSame(
            'Reset paket untuk pengujian',
            data_get($transaction->fresh()->details, 'access_revocation.reason'),
        );

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->getJson('/api/ai-gateway/subscription?external_user_id=participant-reset')
            ->assertOk()
            ->assertJsonPath('subscription', null)
            ->assertJsonPath('subscriptions', [])
            ->assertJsonPath('claimed_free_plan_ids', [])
            ->assertJsonPath('has_inactive_package_history', true);

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/discussion', $this->discussionPayload('participant-reset'))
            ->assertForbidden();

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/checkout', $payload)
            ->assertOk()
            ->assertJson([
                'activated' => true,
                'claimed' => true,
                'already_claimed' => false,
            ]);
        $this->withHeader('X-AI-Gateway-Key', $key)
            ->getJson('/api/ai-gateway/subscription?external_user_id=participant-reset')
            ->assertOk()
            ->assertJsonPath('has_inactive_package_history', true);

        $this->assertSame(2, AiGatewaySubscription::query()
            ->where('ai_gateway_client_id', $client->id)
            ->where('external_user_id', 'participant-reset')
            ->count());
        $this->assertSame(2, AiGatewayTransaction::query()
            ->where('ai_gateway_client_id', $client->id)
            ->where('status', 'paid')
            ->count());
    }

    public function test_revoking_paid_plan_keeps_income_history_and_usage_snapshot(): void
    {
        [$client, $key] = $this->gatewayClient();
        $plan = $this->freePlan([
            'name' => 'Pembahasan AI Pro',
            'slug' => 'pembahasan-ai-pro-revocation',
            'price' => 25000,
        ]);
        $subscription = AiGatewaySubscription::query()->create([
            'ai_gateway_client_id' => $client->id,
            'ai_gateway_plan_id' => $plan->id,
            'token_limit' => 10000,
            'chat_limit' => 0,
            'external_user_id' => 'participant-paid-reset',
            'external_user_name' => 'Peserta Berbayar',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'tokens_used' => 1250,
            'chats_used' => 2,
        ]);
        $transaction = AiGatewayTransaction::query()->create([
            'ai_gateway_client_id' => $client->id,
            'ai_gateway_plan_id' => $plan->id,
            'ai_gateway_subscription_id' => $subscription->id,
            'external_id' => 'AIGW-PAID-RESET-1',
            'provider' => 'xendit',
            'amount' => 25000,
            'status' => 'paid',
            'paid_at' => now(),
            'details' => ['activated_subscription_id' => $subscription->id],
        ]);

        app(AiGatewaySubscriptionService::class)->revokeSubscription($subscription, [
            'reason' => 'Refund ditangani di luar sistem',
        ]);

        $this->assertSame('revoked', $subscription->fresh()->status);
        $this->assertSame(10000, $subscription->fresh()->token_limit);
        $this->assertSame(1250, $subscription->fresh()->tokens_used);
        $this->assertSame('paid', $transaction->fresh()->status);
        $this->assertSame(25000, $transaction->fresh()->amount);

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->getJson('/api/ai-gateway/subscription?external_user_id=participant-paid-reset')
            ->assertOk()
            ->assertJsonPath('subscription', null);

        $paymentPage = app(AiUsageController::class)->gatewayPayments(
            Request::create('/super-admin/ai-gateway-payments'),
            app(AiGatewayCostService::class),
        );
        $this->assertSame(25000, (int) $paymentPage->getData()['summary']->paid_amount);
        $this->assertSame(25000, $paymentPage->getData()['financialSummary']['gross_income']);
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
        $paymentService->shouldReceive('synchronize')->once()->andReturnUsing(
            fn (AiGatewayTransaction $transaction): AiGatewayTransaction => $transaction,
        );
        $this->app->instance(AiGatewayPaymentService::class, $paymentService);

        $response = $this->withHeader('X-AI-Gateway-Key', $key)
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

        $subscription = AiGatewaySubscription::query()
            ->where('external_user_id', 'participant-123')
            ->sole();
        $response->assertJsonPath('subscription_id', $subscription->id);

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->getJson('/api/ai-gateway/subscription?external_user_id=participant-123')
            ->assertOk()
            ->assertJsonPath('pending_payment.subscription_id', $subscription->id)
            ->assertJsonPath('pending_payment.external_id', $response->json('external_id'));

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

    public function test_discussion_rejects_unknown_feature_identifier(): void
    {
        [, $key] = $this->gatewayClient();
        $payload = $this->discussionPayload('participant-123');
        $payload['feature'] = 'untrusted-feature';

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/discussion', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('feature');
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
        $ai->shouldReceive('chat')->once()->withArgs(
            fn (...$arguments) => ($arguments[4] ?? null) === 'learning_note'
                && data_get($arguments, '1.conversation_history.0.user_message') === 'Aku masih bingung di langkah pertama.',
        )->andReturn([
            'message' => 'Jawaban AI',
            'model' => 'test-model',
            'provider' => 'test-provider',
            'usage' => ['input' => 100, 'output' => 50, 'total' => 150],
            'response_time_ms' => 20,
        ]);
        $this->app->instance(AiDiscussionService::class, $ai);

        $payload = $this->discussionPayload('participant-123');
        $payload['feature'] = 'learning_note';

        $this->withHeader('X-AI-Gateway-Key', $key)
            ->postJson('/api/ai-gateway/discussion', $payload)
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
        $this->assertSame('learning_note', $usage->feature);
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
                'conversation_history' => [[
                    'user_message' => 'Aku masih bingung di langkah pertama.',
                    'assistant_message' => 'Mari kita mulai dari konsep dasarnya.',
                ]],
            ],
        ];
    }

    private function enableTelegramNotifications(): void
    {
        Schema::create('client_profile', function (Blueprint $table): void {
            $table->id();
            $table->longText('ai_gateway_telegram_settings')->nullable();
            $table->timestamps();
        });

        ClientProfile::query()->create([
            'ai_gateway_telegram_settings' => [
                'enabled' => true,
                'bot_token' => '123456789:test-bot-token',
                'chat_id' => '-1001234567890',
                'message_thread_id' => null,
                'notify_free' => true,
                'notify_paid' => true,
            ],
        ]);
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
            $table->string('scope', 40)->default('learning_tools');
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
            $table->string('scope', 40)->default('learning_tools');
            $table->unsignedBigInteger('token_limit')->default(0);
            $table->unsignedInteger('chat_limit')->default(0);
            $table->string('external_user_id', 120)->nullable();
            $table->string('external_user_name')->nullable();
            $table->string('external_user_email')->nullable();
            $table->string('free_claim_key', 64)->nullable()->unique();
            $table->string('status')->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->string('revoked_by_user_id', 120)->nullable();
            $table->string('revoked_by_name')->nullable();
            $table->string('revoked_by_email')->nullable();
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
            $table->string('feature', 40)->default('discussion');
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
