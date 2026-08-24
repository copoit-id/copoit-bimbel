<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('affiliate_settings')) {
            Schema::create('affiliate_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('is_active')->default(true);
                $table->string('commission_type', 20)->default('percent');
                $table->decimal('commission_value', 12, 0)->default(10);
                $table->boolean('invitee_discount_enabled')->default(false);
                $table->string('invitee_discount_type', 20)->default('percent');
                $table->decimal('invitee_discount_value', 12, 0)->default(0);
                $table->decimal('invitee_max_discount_amount', 12, 0)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_settings');
    }
};
