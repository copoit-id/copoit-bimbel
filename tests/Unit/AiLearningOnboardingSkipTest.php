<?php

namespace Tests\Unit;

use App\Http\Controllers\user\AiGatewaySubscriptionController;
use App\Http\Controllers\user\AiLearningToolController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AiLearningOnboardingSkipTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('ai_learning_artifacts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('tool');
            $table->timestamps();
        });
        Schema::create('ai_discussion_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('user_message')->nullable();
            $table->timestamps();
        });
    }

    public function test_returning_user_can_skip_tutorial_for_a_new_subscription_cycle(): void
    {
        DB::table('ai_learning_artifacts')->insert([
            'user_id' => 10,
            'tool' => 'note',
            'created_at' => now()->subMonth(),
            'updated_at' => now()->subMonth(),
        ]);
        $gatewayController = $this->gatewayControllerWithSubscription(55, true);
        $request = $this->requestForUser(10, 55);

        $response = app(AiLearningToolController::class)->skipOnboarding($request, $gatewayController);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(55, $request->session()->get('ai_learning_onboarding_skipped_subscription_id'));
    }

    public function test_first_time_user_cannot_skip_tutorial(): void
    {
        DB::table('ai_learning_artifacts')->insert([
            'user_id' => 10,
            'tool' => 'note',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $gatewayController = $this->gatewayControllerWithSubscription(55, false);
        $request = $this->requestForUser(10, 55);

        $response = app(AiLearningToolController::class)->skipOnboarding($request, $gatewayController);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(
            'Tutorial wajib diselesaikan untuk penggunaan AI pertama.',
            $response->getData(true)['message'],
        );
        $this->assertNull($request->session()->get('ai_learning_onboarding_skipped_subscription_id'));
    }

    private function gatewayControllerWithSubscription(int $subscriptionId, bool $hasInactivePackageHistory): AiGatewaySubscriptionController
    {
        $controller = Mockery::mock(AiGatewaySubscriptionController::class);
        $controller->shouldReceive('dashboardData')->once()->withArgs(
            fn (Request $request, bool $includeUsageLogs): bool => ! $includeUsageLogs,
        )->andReturn([
            'subscriptions' => [[
                'id' => $subscriptionId,
                'status' => 'active',
                'starts_at' => now()->subMinute()->toISOString(),
            ]],
            'hasInactivePackageHistory' => $hasInactivePackageHistory,
        ]);

        return $controller;
    }

    private function requestForUser(int $userId, int $subscriptionId): Request
    {
        $request = Request::create('/user/ai-learning-tools/onboarding/skip', 'POST', [
            'subscription_id' => $subscriptionId,
        ]);
        $request->setUserResolver(fn () => (object) ['id' => $userId]);
        $request->setLaravelSession(app('session.store'));

        return $request;
    }
}
