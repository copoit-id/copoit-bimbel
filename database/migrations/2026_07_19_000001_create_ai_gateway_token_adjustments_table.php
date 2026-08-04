<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_gateway_token_adjustments')) {
            return;
        }

        Schema::create('ai_gateway_token_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_gateway_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_gateway_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_user_id', 120);
            $table->unsignedBigInteger('tokens_added');
            $table->unsignedBigInteger('previous_token_limit');
            $table->unsignedBigInteger('new_token_limit');
            $table->string('reason', 255);
            $table->string('actor_user_id', 120)->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->string('origin_base_url', 2048)->nullable();
            $table->timestamps();
            $table->index(
                ['ai_gateway_client_id', 'external_user_id', 'created_at'],
                'aigw_token_adjustments_user_created_idx'
            );
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_gateway_token_adjustments')) {
            Schema::drop('ai_gateway_token_adjustments');
        }
    }
};
