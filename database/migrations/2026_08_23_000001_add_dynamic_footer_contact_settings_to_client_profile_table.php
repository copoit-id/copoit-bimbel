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
            if (! Schema::hasColumn('client_profile', 'footer_contacts')) {
                $table->json('footer_contacts')->nullable()->after('footer_address');
            }

            if (! Schema::hasColumn('client_profile', 'footer_socials')) {
                $table->json('footer_socials')->nullable()->after('footer_contacts');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            foreach (['footer_socials', 'footer_contacts'] as $column) {
                if (Schema::hasColumn('client_profile', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
