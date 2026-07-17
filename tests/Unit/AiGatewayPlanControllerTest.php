<?php

namespace Tests\Unit;

use App\Http\Controllers\superadmin\AiGatewayPlanController;
use App\Models\AiGatewayPlan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class AiGatewayPlanControllerTest extends TestCase
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
            $table->unsignedInteger('duration_days');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('ai_gateway_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ai_gateway_plan_id');
        });
        Schema::create('ai_gateway_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ai_gateway_plan_id');
        });
    }

    public function test_chat_limit_zero_is_valid_for_unlimited_chat(): void
    {
        $request = Request::create('/super-admin/ai-gateway-plans', 'POST', [
            'name' => 'Paket Token',
            'price' => 25000,
            'token_limit' => 50000,
            'chat_limit' => 0,
            'duration_days' => 30,
        ]);
        $method = new ReflectionMethod(AiGatewayPlanController::class, 'validated');

        $validated = $method->invoke(app(AiGatewayPlanController::class), $request);

        $this->assertSame(0, $validated['chat_limit']);
    }

    public function test_used_plan_is_archived_instead_of_deleted(): void
    {
        $plan = $this->createPlan();
        DB::table('ai_gateway_subscriptions')->insert(['ai_gateway_plan_id' => $plan->id]);

        app(AiGatewayPlanController::class)->destroy($plan);

        $this->assertDatabaseHas('ai_gateway_plans', [
            'id' => $plan->id,
            'is_active' => false,
        ]);
    }

    public function test_unused_plan_can_be_deleted(): void
    {
        $plan = $this->createPlan();

        app(AiGatewayPlanController::class)->destroy($plan);

        $this->assertDatabaseMissing('ai_gateway_plans', ['id' => $plan->id]);
    }

    private function createPlan(): AiGatewayPlan
    {
        return AiGatewayPlan::create([
            'name' => 'Paket Token',
            'slug' => 'paket-token',
            'price' => 25000,
            'token_limit' => 50000,
            'chat_limit' => 0,
            'duration_days' => 30,
            'is_active' => true,
        ]);
    }
}
