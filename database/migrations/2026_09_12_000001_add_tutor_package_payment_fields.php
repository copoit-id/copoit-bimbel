<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table): void {
                if (! Schema::hasColumn('packages', 'tutor_payment_frequency')) {
                    $table->string('tutor_payment_frequency', 20)->default('none')->after('price')->index();
                }
            });
        }

        if (Schema::hasTable('bill_invoices')) {
            Schema::table('bill_invoices', function (Blueprint $table): void {
                if (! Schema::hasColumn('bill_invoices', 'package_id')) {
                    $table->unsignedBigInteger('package_id')->nullable()->after('recurring_bill_id')->index();
                }
                if (! Schema::hasColumn('bill_invoices', 'study_group_id')) {
                    $table->foreignId('study_group_id')->nullable()->after('user_id')->constrained('study_groups')->nullOnDelete();
                }
                if (! Schema::hasColumn('bill_invoices', 'class_session_id')) {
                    $table->foreignId('class_session_id')->nullable()->after('study_group_id')->constrained('class_sessions')->nullOnDelete();
                }
                if (! Schema::hasColumn('bill_invoices', 'payment_scope_key')) {
                    $table->string('payment_scope_key', 120)->nullable()->after('invoice_number')->unique();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bill_invoices')) {
            Schema::table('bill_invoices', function (Blueprint $table): void {
                if (Schema::hasColumn('bill_invoices', 'payment_scope_key')) {
                    $table->dropUnique(['payment_scope_key']);
                    $table->dropColumn('payment_scope_key');
                }
                if (Schema::hasColumn('bill_invoices', 'class_session_id')) {
                    $table->dropConstrainedForeignId('class_session_id');
                }
                if (Schema::hasColumn('bill_invoices', 'study_group_id')) {
                    $table->dropConstrainedForeignId('study_group_id');
                }
                if (Schema::hasColumn('bill_invoices', 'package_id')) {
                    $table->dropIndex(['package_id']);
                    $table->dropColumn('package_id');
                }
            });
        }

        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table): void {
                if (Schema::hasColumn('packages', 'tutor_payment_frequency')) {
                    $table->dropIndex(['tutor_payment_frequency']);
                    $table->dropColumn('tutor_payment_frequency');
                }
            });
        }
    }
};
