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
            if (!Schema::hasColumn('client_profile', 'contact_whatsapp_number')) {
                $column = $table->string('contact_whatsapp_number', 32)->nullable();

                if (Schema::hasColumn('client_profile', 'smtp_notification_email')) {
                    $column->after('smtp_notification_email');
                }
            }

            if (!Schema::hasColumn('client_profile', 'contact_whatsapp_button_text')) {
                $table->string('contact_whatsapp_button_text', 80)->nullable()->after('contact_whatsapp_number');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            foreach (['contact_whatsapp_button_text', 'contact_whatsapp_number'] as $column) {
                if (Schema::hasColumn('client_profile', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
