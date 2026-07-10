<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_gateway_plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->unsignedBigInteger('price');
            $t->unsignedBigInteger('token_limit')->default(0);
            $t->unsignedInteger('chat_limit')->default(0);
            $t->unsignedInteger('duration_days')->default(30);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('ai_gateway_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ai_gateway_client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('ai_gateway_plan_id')->constrained()->restrictOnDelete();
            $t->string('status')->default('pending');
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->unsignedBigInteger('tokens_used')->default(0);
            $t->unsignedInteger('chats_used')->default(0);
            $t->timestamps();
            $t->index(['ai_gateway_client_id', 'status']);
        });
        Schema::create('ai_gateway_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ai_gateway_client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('ai_gateway_plan_id')->constrained()->restrictOnDelete();
            $t->foreignId('ai_gateway_subscription_id')->nullable()->constrained()->nullOnDelete();
            $t->string('external_id')->unique();
            $t->string('provider')->default('xendit');
            $t->string('provider_invoice_id')->nullable();
            $t->unsignedBigInteger('amount');
            $t->string('status')->default('pending');
            $t->json('details')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_gateway_transactions');
        Schema::dropIfExists('ai_gateway_subscriptions');
        Schema::dropIfExists('ai_gateway_plans');
    }
};
