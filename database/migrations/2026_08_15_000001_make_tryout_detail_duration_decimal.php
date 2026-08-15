<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tryout_details') && Schema::hasColumn('tryout_details', 'duration')) {
            DB::statement('ALTER TABLE tryout_details MODIFY duration DECIMAL(8, 2) NOT NULL DEFAULT 60.00');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tryout_details') && Schema::hasColumn('tryout_details', 'duration')) {
            DB::statement('ALTER TABLE tryout_details MODIFY duration INTEGER NOT NULL DEFAULT 60');
        }
    }
};
