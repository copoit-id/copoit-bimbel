<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_tryout_accesses', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('tryout_id');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('status', 32)->default('active')->after('end_date');
            $table->string('payment_status', 32)->nullable()->after('status');
            $table->decimal('payment_amount', 12, 2)->unsigned()->default(0)->after('payment_status');
            $table->text('notes')->nullable()->after('payment_amount');
            $table->unsignedBigInteger('created_by')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('user_tryout_accesses', function (Blueprint $table) {
            $table->dropColumn([
                'start_date',
                'end_date',
                'status',
                'payment_status',
                'payment_amount',
                'notes',
                'created_by',
            ]);
        });
    }
};
