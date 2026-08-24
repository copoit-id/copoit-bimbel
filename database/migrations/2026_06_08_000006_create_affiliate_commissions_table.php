<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('affiliate_commissions')) {
            Schema::create('affiliate_commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('affiliate_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('payment_id')->unique()->constrained('payments', 'payment_id')->cascadeOnDelete();
                $table->foreignId('package_id')->constrained('packages', 'package_id')->cascadeOnDelete();
                $table->string('commission_type', 20);
                $table->decimal('commission_value', 12, 0)->default(0);
                $table->decimal('base_amount', 12, 0)->default(0);
                $table->decimal('commission_amount', 12, 0)->default(0);
                $table->string('status', 20)->default('pending');
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
    }
};
