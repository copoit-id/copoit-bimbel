<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'partial', 'success', 'failed', 'expired') NOT NULL DEFAULT 'pending'");
        }

        if (! Schema::hasTable('payment_installments')) {
            Schema::create('payment_installments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payment_id')->constrained('payments', 'payment_id')->cascadeOnDelete();
                $table->string('receipt_number')->unique();
                $table->decimal('amount', 12, 0);
                $table->string('payment_method', 50)->default('manual');
                $table->text('notes')->nullable();
                $table->timestamp('paid_at')->index();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['payment_id', 'paid_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payment_installments')) {
            Schema::dropIfExists('payment_installments');
        }

        if (Schema::hasTable('payments') && DB::getDriverName() === 'mysql') {
            DB::table('payments')->where('status', 'partial')->update(['status' => 'pending']);
            DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'success', 'failed', 'expired') NOT NULL DEFAULT 'pending'");
        }
    }
};
