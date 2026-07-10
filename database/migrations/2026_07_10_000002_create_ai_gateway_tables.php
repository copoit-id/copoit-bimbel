<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_gateway_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('api_key_hash', 64);
            $table->unsignedBigInteger('monthly_token_limit')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_gateway_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_gateway_client_id')->constrained()->cascadeOnDelete();
            $table->string('external_user_id', 120)->nullable();
            $table->string('model', 120);
            $table->string('provider', 30);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->string('question_reference', 120)->nullable();
            $table->timestamps();
            $table->index(['ai_gateway_client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_gateway_usage_logs');
        Schema::dropIfExists('ai_gateway_clients');
    }
};
