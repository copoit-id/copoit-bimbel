<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(100) NOT NULL DEFAULT 'user'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','user','super_admin','admin_demo') NOT NULL DEFAULT 'user'");
    }
};
