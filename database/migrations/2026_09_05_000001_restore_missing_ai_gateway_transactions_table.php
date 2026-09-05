<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restore the billing transaction table when the original billing migration
     * is already recorded but its table is missing from an existing database.
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_gateway_transactions')) {
            return;
        }

        Schema::create('ai_gateway_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_gateway_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_gateway_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('ai_gateway_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id')->unique();
            $table->string('provider')->default('xendit');
            $table->string('provider_invoice_id')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('pending');
            $table->json('details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_gateway_transactions');
    }
};
