<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tryouts') && Schema::hasColumn('tryouts', 'type_tryout')) {
            DB::statement("ALTER TABLE tryouts MODIFY type_tryout ENUM('tiu','twk','tkp','skd_full','general','tpa','tbi','certification','listening','reading','writing','pppk_full','teknis','social culture','management','interview','word','excel','ppt','computer','utbk_full','utbk_section','utbk_penalaran_umum','utbk_pengetahuan_umum','utbk_pengetahuan_kuantitatif','utbk_pemahaman_bacaan_menulis','utbk_literasi_bahasa_indonesia','utbk_literasi_bahasa_inggris','utbk_penalaran_matematika') NOT NULL");
        }

        if (Schema::hasTable('tryout_details') && Schema::hasColumn('tryout_details', 'type_subtest')) {
            DB::statement("ALTER TABLE tryout_details MODIFY type_subtest ENUM('twk','tiu','tkp','general','tpa','tbi','listening','reading','writing','teknis','social culture','management','interview','word','excel','ppt','penalaran_umum','pengetahuan_umum','pengetahuan_kuantitatif','pemahaman_bacaan_menulis','literasi_bahasa_indonesia','literasi_bahasa_inggris','penalaran_matematika') NOT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tryouts') && Schema::hasColumn('tryouts', 'type_tryout')) {
            DB::statement("ALTER TABLE tryouts MODIFY type_tryout ENUM('tiu','twk','tkp','skd_full','general','certification','listening','reading','writing','pppk_full','teknis','social culture','management','interview','word','excel','ppt','computer','utbk_full','utbk_section','utbk_penalaran_umum','utbk_pengetahuan_umum','utbk_pengetahuan_kuantitatif','utbk_pemahaman_bacaan_menulis','utbk_literasi_bahasa_indonesia','utbk_literasi_bahasa_inggris','utbk_penalaran_matematika') NOT NULL");
        }

        if (Schema::hasTable('tryout_details') && Schema::hasColumn('tryout_details', 'type_subtest')) {
            DB::statement("ALTER TABLE tryout_details MODIFY type_subtest ENUM('twk','tiu','tkp','general','listening','reading','writing','teknis','social culture','management','interview','word','excel','ppt','penalaran_umum','pengetahuan_umum','pengetahuan_kuantitatif','pemahaman_bacaan_menulis','literasi_bahasa_indonesia','literasi_bahasa_inggris','penalaran_matematika') NOT NULL");
        }
    }
};
