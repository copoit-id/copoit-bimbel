<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kecermatans')) {
            return;
        }

        if (Schema::hasColumn('kecermatans', 'is_active')) {
            DB::statement('ALTER TABLE kecermatans MODIFY is_active TINYINT(1) NOT NULL DEFAULT 0');
        }

        if (Schema::hasColumn('kecermatans', 'is_displayed')) {
            DB::statement('ALTER TABLE kecermatans MODIFY is_displayed TINYINT(1) NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('kecermatans')) {
            return;
        }

        if (Schema::hasColumn('kecermatans', 'is_displayed')) {
            DB::statement('ALTER TABLE kecermatans MODIFY is_displayed TINYINT(1) NOT NULL DEFAULT 1');
        }

        if (Schema::hasColumn('kecermatans', 'is_active')) {
            DB::statement('ALTER TABLE kecermatans MODIFY is_active TINYINT(1) NOT NULL DEFAULT 1');
        }
    }
};
