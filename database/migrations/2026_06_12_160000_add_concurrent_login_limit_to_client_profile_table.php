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
            if (!Schema::hasColumn('client_profile', 'concurrent_login_limit')) {
                $column = $table->unsignedSmallInteger('concurrent_login_limit')->default(1);

                if (Schema::hasColumn('client_profile', 'contact_whatsapp_button_text')) {
                    $column->after('contact_whatsapp_button_text');
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            if (Schema::hasColumn('client_profile', 'concurrent_login_limit')) {
                $table->dropColumn('concurrent_login_limit');
            }
        });
    }
};
