<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            if (!Schema::hasColumn('client_profile', 'smtp_host')) {
                $table->string('smtp_host')->nullable()->after('payment_bank_note');
            }

            if (!Schema::hasColumn('client_profile', 'smtp_port')) {
                $table->unsignedInteger('smtp_port')->nullable()->after('smtp_host');
            }

            if (!Schema::hasColumn('client_profile', 'smtp_encryption')) {
                $table->string('smtp_encryption', 10)->nullable()->after('smtp_port');
            }

            if (!Schema::hasColumn('client_profile', 'smtp_email')) {
                $table->string('smtp_email')->nullable()->after('smtp_encryption');
            }

            if (!Schema::hasColumn('client_profile', 'smtp_app_password')) {
                $table->text('smtp_app_password')->nullable()->after('smtp_email');
            }

            if (!Schema::hasColumn('client_profile', 'smtp_notification_email')) {
                $table->string('smtp_notification_email')->nullable()->after('smtp_app_password');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            if (Schema::hasColumn('client_profile', 'smtp_notification_email')) {
                $table->dropColumn('smtp_notification_email');
            }

            if (Schema::hasColumn('client_profile', 'smtp_app_password')) {
                $table->dropColumn('smtp_app_password');
            }

            if (Schema::hasColumn('client_profile', 'smtp_email')) {
                $table->dropColumn('smtp_email');
            }

            if (Schema::hasColumn('client_profile', 'smtp_encryption')) {
                $table->dropColumn('smtp_encryption');
            }

            if (Schema::hasColumn('client_profile', 'smtp_port')) {
                $table->dropColumn('smtp_port');
            }

            if (Schema::hasColumn('client_profile', 'smtp_host')) {
                $table->dropColumn('smtp_host');
            }
        });
    }
};
