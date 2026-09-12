<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bill_invoices')) {
            return;
        }

        if (! Schema::hasColumn('bill_invoices', 'billing_source')) {
            Schema::table('bill_invoices', function (Blueprint $table): void {
                $table->string('billing_source', 20)->default('manual')->after('recurring_bill_id');
                $table->index(['billing_source', 'period_start']);
            });
        }

        DB::table('bill_invoices')
            ->where('payment_scope_key', 'like', 'tutor-package:%')
            ->update(['billing_source' => 'schedule']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('bill_invoices') || ! Schema::hasColumn('bill_invoices', 'billing_source')) {
            return;
        }

        Schema::table('bill_invoices', function (Blueprint $table): void {
            $table->dropIndex(['billing_source', 'period_start']);
            $table->dropColumn('billing_source');
        });
    }
};
