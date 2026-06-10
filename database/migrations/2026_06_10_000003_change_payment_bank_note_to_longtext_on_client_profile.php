<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_profile') && Schema::hasColumn('client_profile', 'payment_bank_note')) {
            DB::statement('ALTER TABLE client_profile MODIFY payment_bank_note LONGTEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('client_profile') && Schema::hasColumn('client_profile', 'payment_bank_note')) {
            DB::statement('ALTER TABLE client_profile MODIFY payment_bank_note VARCHAR(255) NULL');
        }
    }
};
