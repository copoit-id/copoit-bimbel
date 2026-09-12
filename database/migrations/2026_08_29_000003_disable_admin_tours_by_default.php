<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_profile') || ! Schema::hasColumn('client_profile', 'admin_tours_enabled')) {
            return;
        }

        DB::table('client_profile')->update(['admin_tours_enabled' => false]);
    }

    public function down(): void
    {
        // Keep the safer default when rolling back; Super Admin can enable it.
    }
};
