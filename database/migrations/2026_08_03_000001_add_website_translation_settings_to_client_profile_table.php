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

        Schema::table('client_profile', function (Blueprint $table): void {
            if (! Schema::hasColumn('client_profile', 'website_translation_enabled')) {
                $table->boolean('website_translation_enabled')->default(true);
            }

            if (! Schema::hasColumn('client_profile', 'website_translation_locales')) {
                $table->json('website_translation_locales')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            if (Schema::hasColumn('client_profile', 'website_translation_locales')) {
                $table->dropColumn('website_translation_locales');
            }

            if (Schema::hasColumn('client_profile', 'website_translation_enabled')) {
                $table->dropColumn('website_translation_enabled');
            }
        });
    }
};
