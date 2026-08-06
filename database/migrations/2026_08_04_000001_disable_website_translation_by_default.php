<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_profile') || ! Schema::hasColumn('client_profile', 'website_translation_enabled')) {
            return;
        }

        DB::table('client_profile')->update(['website_translation_enabled' => false]);
    }

    public function down(): void
    {
        // Pengaturan ini bersifat opt-in; jangan aktifkan kembali secara otomatis.
    }
};
