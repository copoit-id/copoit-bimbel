<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            if (! Schema::hasColumn('client_profile', 'dashboard_announcement_title')) {
                $table->string('dashboard_announcement_title')->nullable()->after('payment_bank_note');
            }

            if (! Schema::hasColumn('client_profile', 'dashboard_announcement_message')) {
                $table->text('dashboard_announcement_message')->nullable()->after('dashboard_announcement_title');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            if (Schema::hasColumn('client_profile', 'dashboard_announcement_message')) {
                $table->dropColumn('dashboard_announcement_message');
            }

            if (Schema::hasColumn('client_profile', 'dashboard_announcement_title')) {
                $table->dropColumn('dashboard_announcement_title');
            }
        });
    }
};
