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
            if (!Schema::hasColumn('client_profile', 'footer_address')) {
                $table->text('footer_address')->nullable()->after('footer_links');
            }

            if (!Schema::hasColumn('client_profile', 'footer_phone')) {
                $table->string('footer_phone')->nullable()->after('footer_address');
            }

            if (!Schema::hasColumn('client_profile', 'footer_email')) {
                $table->string('footer_email')->nullable()->after('footer_phone');
            }

            if (!Schema::hasColumn('client_profile', 'footer_whatsapp')) {
                $table->string('footer_whatsapp')->nullable()->after('footer_email');
            }

            if (!Schema::hasColumn('client_profile', 'footer_facebook')) {
                $table->string('footer_facebook')->nullable()->after('footer_whatsapp');
            }

            if (!Schema::hasColumn('client_profile', 'footer_instagram')) {
                $table->string('footer_instagram')->nullable()->after('footer_facebook');
            }

            if (!Schema::hasColumn('client_profile', 'footer_twitter')) {
                $table->string('footer_twitter')->nullable()->after('footer_instagram');
            }

            if (!Schema::hasColumn('client_profile', 'footer_youtube')) {
                $table->string('footer_youtube')->nullable()->after('footer_twitter');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            $columns = [
                'footer_youtube',
                'footer_twitter',
                'footer_instagram',
                'footer_facebook',
                'footer_whatsapp',
                'footer_email',
                'footer_phone',
                'footer_address',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('client_profile', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
