<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('discounts')) {
            Schema::create('discounts', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->string('discount_type', 20)->default('percent');
                $table->decimal('discount_value', 12, 0)->default(0);
                $table->decimal('max_discount_amount', 12, 0)->nullable();
                $table->decimal('min_purchase_amount', 12, 0)->default(0);
                $table->unsignedInteger('usage_limit')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->unsignedInteger('per_user_limit')->nullable()->default(1);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_public')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
