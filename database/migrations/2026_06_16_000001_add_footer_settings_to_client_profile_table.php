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
            if (!Schema::hasColumn('client_profile', 'footer_enabled')) {
                $column = $table->boolean('footer_enabled')->default(true);

                if (Schema::hasColumn('client_profile', 'concurrent_login_limit')) {
                    $column->after('concurrent_login_limit');
                }
            }

            if (!Schema::hasColumn('client_profile', 'footer_description')) {
                $table->text('footer_description')->nullable()->after('footer_enabled');
            }

            if (!Schema::hasColumn('client_profile', 'footer_copyright')) {
                $table->string('footer_copyright')->nullable()->after('footer_description');
            }

            if (!Schema::hasColumn('client_profile', 'footer_links')) {
                $table->json('footer_links')->nullable()->after('footer_copyright');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            foreach (['footer_links', 'footer_copyright', 'footer_description', 'footer_enabled'] as $column) {
                if (Schema::hasColumn('client_profile', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
