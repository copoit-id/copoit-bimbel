<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Materials - only add if not exists
        if (!Schema::hasColumn('materials', 'price')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->decimal('price', 12, 0)->nullable()->after('order_number');
            });
        }
        if (!Schema::hasColumn('materials', 'is_for_sale')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->boolean('is_for_sale')->default(false)->after('price');
            });
        }

        // Tryouts - only add if not exists
        if (!Schema::hasColumn('tryouts', 'price')) {
            Schema::table('tryouts', function (Blueprint $table) {
                $table->decimal('price', 12, 0)->nullable()->after('end_date');
            });
        }
        if (!Schema::hasColumn('tryouts', 'is_for_sale')) {
            Schema::table('tryouts', function (Blueprint $table) {
                $table->boolean('is_for_sale')->default(false)->after('price');
            });
        }

        // Create individual_purchases table if not exists
        if (!Schema::hasTable('individual_purchases')) {
            Schema::create('individual_purchases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('purchasable_type'); // 'App\Models\Material' or 'App\Models\Tryout'
                $table->unsignedBigInteger('purchasable_id');
                $table->decimal('price', 12, 0);
                $table->decimal('admin_fee', 12, 0)->default(0);
                $table->decimal('total_amount', 12, 0);
                $table->string('payment_method')->default('manual');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->string('transaction_id')->nullable();
                $table->json('payment_details')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();

                $table->index(['user_id', 'purchasable_type', 'purchasable_id'], 'idx_user_purchasable');
                $table->index(['purchasable_type', 'purchasable_id'], 'idx_purchasable');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (Schema::hasColumn('materials', 'is_for_sale')) {
                $table->dropColumn(['price', 'is_for_sale']);
            }
        });

        Schema::table('tryouts', function (Blueprint $table) {
            if (Schema::hasColumn('tryouts', 'is_for_sale')) {
                $table->dropColumn(['price', 'is_for_sale']);
            }
        });

        Schema::dropIfExists('individual_purchases');
    }
};