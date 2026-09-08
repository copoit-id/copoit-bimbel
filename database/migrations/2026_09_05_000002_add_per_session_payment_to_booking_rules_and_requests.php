<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('package_booking_rules') && ! Schema::hasColumn('package_booking_rules', 'payment_model')) {
            Schema::table('package_booking_rules', function (Blueprint $table): void {
                $table->string('payment_model', 20)->default('upfront')->after('group_pricing_mode');
            });
        }

        if (Schema::hasTable('schedule_booking_requests')) {
            Schema::table('schedule_booking_requests', function (Blueprint $table): void {
                if (! Schema::hasColumn('schedule_booking_requests', 'session_price')) {
                    $table->unsignedBigInteger('session_price')->nullable()->after('scheduled_end_at');
                }
                if (! Schema::hasColumn('schedule_booking_requests', 'tutor_payment_status')) {
                    $table->string('tutor_payment_status', 30)->default('not_required')->after('session_price');
                }
                if (! Schema::hasColumn('schedule_booking_requests', 'paid_to_tutor_at')) {
                    $table->timestamp('paid_to_tutor_at')->nullable()->after('tutor_payment_status');
                }
                if (! Schema::hasColumn('schedule_booking_requests', 'deposited_to_admin_at')) {
                    $table->timestamp('deposited_to_admin_at')->nullable()->after('paid_to_tutor_at');
                }
                if (! Schema::hasColumn('schedule_booking_requests', 'deposited_to_admin_by')) {
                    $table->foreignId('deposited_to_admin_by')->nullable()->constrained('users')->nullOnDelete()->after('deposited_to_admin_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('schedule_booking_requests')) {
            Schema::table('schedule_booking_requests', function (Blueprint $table): void {
                if (Schema::hasColumn('schedule_booking_requests', 'deposited_to_admin_by')) $table->dropConstrainedForeignId('deposited_to_admin_by');
                foreach (['deposited_to_admin_at', 'paid_to_tutor_at', 'tutor_payment_status', 'session_price'] as $column) if (Schema::hasColumn('schedule_booking_requests', $column)) $table->dropColumn($column);
            });
        }
        if (Schema::hasTable('package_booking_rules') && Schema::hasColumn('package_booking_rules', 'payment_model')) Schema::table('package_booking_rules', fn (Blueprint $table) => $table->dropColumn('payment_model'));
    }
};
