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
        DB::statement("ALTER TABLE tryouts MODIFY type_tryout ENUM(
            'tiu',
            'twk',
            'tkp',
            'skd_full',
            'general',
            'certification',
            'listening',
            'reading',
            'writing',
            'pppk_full',
            'teknis',
            'social culture',
            'management',
            'interview',
            'word',
            'excel',
            'ppt',
            'computer',
            'utbk_full',
            'utbk_section',
            'utbk_penalaran_umum',
            'utbk_pengetahuan_umum',
            'utbk_pengetahuan_kuantitatif',
            'utbk_pemahaman_bacaan_menulis',
            'utbk_literasi_bahasa_indonesia',
            'utbk_literasi_bahasa_inggris',
            'utbk_penalaran_matematika'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tryouts MODIFY type_tryout ENUM(
            'tiu',
            'twk',
            'tkp',
            'skd_full',
            'general',
            'certification',
            'listening',
            'reading',
            'writing',
            'pppk_full',
            'teknis',
            'social culture',
            'management',
            'interview',
            'word',
            'excel',
            'ppt',
            'computer',
            'utbk_full',
            'utbk_section'
        ) NOT NULL");
    }
};
