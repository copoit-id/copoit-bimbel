<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_plan_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_profile_id')->constrained('client_profile')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            
            // Status
            $table->enum('status', ['active', 'trial', 'expired', 'suspended'])->default('active');
            
            // Duration
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable()->comment('NULL = lifetime');
            
            // Essay AI Usage Tracking
            $table->integer('essay_ai_used_this_month')->default(0);
            $table->timestamp('essay_ai_reset_at')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['client_profile_id', 'status']);
            $table->index(['client_profile_id', 'plan_id']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_plan_subscriptions');
    }
};
