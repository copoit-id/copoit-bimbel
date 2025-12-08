<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE tryout_details
            MODIFY type_subtest ENUM(
                'twk',
                'tiu',
                'tkp',
                'general',
                'listening',
                'reading',
                'writing',
                'teknis',
                'social culture',
                'management',
                'interview',
                'word',
                'excel',
                'ppt',
                'penalaran_umum',
                'pengetahuan_umum',
                'pengetahuan_kuantitatif',
                'pemahaman_bacaan_menulis',
                'literasi_bahasa_indonesia',
                'literasi_bahasa_inggris',
                'penalaran_matematika'
            ) NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE tryout_details
            MODIFY type_subtest ENUM(
                'twk',
                'tiu',
                'tkp',
                'general',
                'listening',
                'reading',
                'writing',
                'teknis',
                'social culture',
                'management',
                'interview',
                'word',
                'excel',
                'ppt'
            ) NOT NULL
        ");
    }
};
