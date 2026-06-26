<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tryout_details') || ! Schema::hasColumn('tryout_details', 'type_subtest')) {
            return;
        }

        DB::statement('ALTER TABLE tryout_details MODIFY type_subtest VARCHAR(100) NOT NULL');
    }

    public function down(): void
    {
        // No-op: this migration intentionally widens ENUM to VARCHAR so dynamic
        // material category codes can be used as subtest codes safely.
    }
};
