<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 0)->default(0);
            $table->integer('duration_days')->default(30)->comment('0 = lifetime');
            $table->boolean('is_active')->default(true);
            
            // Limits
            $table->integer('max_packages')->default(0)->comment('-1 = unlimited, 0 = disable');
            $table->integer('max_users')->default(0)->comment('-1 = unlimited, 0 = disable');
            $table->integer('max_question_banks')->default(0)->comment('-1 = unlimited, 0 = disable');
            
            // Essay AI Feature
            $table->boolean('essay_ai_enabled')->default(false);
            $table->integer('essay_ai_monthly_limit')->default(0)->comment('0 = unlimited');
            
            // Flags
            $table->boolean('is_default')->default(false)->comment('Auto assign to new client');
            $table->boolean('is_trial')->default(false)->comment('This is a trial plan');
            $table->integer('trial_duration_days')->default(14)->comment('For trial plans');
            
            // JSON for future features
            $table->json('features_json')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index('slug');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
