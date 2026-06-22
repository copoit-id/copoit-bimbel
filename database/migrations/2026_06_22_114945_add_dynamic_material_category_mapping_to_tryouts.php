<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('material_categories') && ! Schema::hasColumn('material_categories', 'code')) {
            Schema::table('material_categories', function (Blueprint $table) {
                $table->string('code')->nullable()->after('name');
                $table->unique('code', 'material_categories_code_unique');
            });
        }

        if (Schema::hasTable('tryouts')) {
            Schema::table('tryouts', function (Blueprint $table) {
                if (! Schema::hasColumn('tryouts', 'material_category_id')) {
                    $table->unsignedBigInteger('material_category_id')->nullable()->after('type_tryout');
                    $table->index('material_category_id', 'tryouts_material_category_id_index');
                }

                if (! Schema::hasColumn('tryouts', 'scoring_method')) {
                    $table->string('scoring_method', 30)->default('normal')->after('is_irt');
                }
            });
        }

        if (Schema::hasTable('tryout_details') && ! Schema::hasColumn('tryout_details', 'material_category_id')) {
            Schema::table('tryout_details', function (Blueprint $table) {
                $table->unsignedBigInteger('material_category_id')->nullable()->after('type_subtest');
                $table->index('material_category_id', 'tryout_details_material_category_id_index');
            });
        }

        $this->seedDefaultCategories();
        $this->backfillTryoutMappings();
    }

    public function down(): void
    {
        if (Schema::hasTable('tryout_details') && Schema::hasColumn('tryout_details', 'material_category_id')) {
            Schema::table('tryout_details', function (Blueprint $table) {
                $table->dropIndex('tryout_details_material_category_id_index');
                $table->dropColumn('material_category_id');
            });
        }

        if (Schema::hasTable('tryouts')) {
            Schema::table('tryouts', function (Blueprint $table) {
                if (Schema::hasColumn('tryouts', 'material_category_id')) {
                    $table->dropIndex('tryouts_material_category_id_index');
                    $table->dropColumn('material_category_id');
                }

                if (Schema::hasColumn('tryouts', 'scoring_method')) {
                    $table->dropColumn('scoring_method');
                }
            });
        }

        if (Schema::hasTable('material_categories') && Schema::hasColumn('material_categories', 'code')) {
            Schema::table('material_categories', function (Blueprint $table) {
                $table->dropUnique('material_categories_code_unique');
                $table->dropColumn('code');
            });
        }
    }

    private function seedDefaultCategories(): void
    {
        if (! Schema::hasTable('material_categories') || ! Schema::hasColumn('material_categories', 'code')) {
            return;
        }

        $now = now();
        $groups = [
            ['code' => 'skd_full', 'name' => 'SKD Full', 'description' => 'TWK, TIU, dan TKP.', 'order_number' => 10],
            ['code' => 'utbk_full', 'name' => 'UTBK TPS Full', 'description' => 'Semua subtest UTBK TPS.', 'order_number' => 20],
            ['code' => 'certification', 'name' => 'Certification Full (TOEFL ITP)', 'description' => 'Listening, Writing, dan Reading.', 'order_number' => 30],
            ['code' => 'pppk_full', 'name' => 'PPPK Full', 'description' => 'Teknis, Sosial Kultural, dan Interview.', 'order_number' => 40],
            ['code' => 'computer', 'name' => 'Computer Full', 'description' => 'Word, Excel, dan PowerPoint.', 'order_number' => 50],
        ];

        foreach ($groups as $group) {
            DB::table('material_categories')->updateOrInsert(
                ['code' => $group['code']],
                [
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'order_number' => $group['order_number'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $parentIds = DB::table('material_categories')
            ->whereIn('code', collect($groups)->pluck('code')->all())
            ->pluck('category_id', 'code');

        $categories = [
            ['code' => 'twk', 'name' => 'Tes Wawasan Kebangsaan', 'parent' => 'skd_full', 'order_number' => 11],
            ['code' => 'tiu', 'name' => 'Tes Intelegensi Umum', 'parent' => 'skd_full', 'order_number' => 12],
            ['code' => 'tkp', 'name' => 'Tes Karakteristik Pribadi', 'parent' => 'skd_full', 'order_number' => 13],
            ['code' => 'tpa', 'name' => 'TPA', 'parent' => null, 'order_number' => 14],
            ['code' => 'tbi', 'name' => 'TBI', 'parent' => null, 'order_number' => 15],
            ['code' => 'general', 'name' => 'General', 'parent' => null, 'order_number' => 16],
            ['code' => 'listening', 'name' => 'Listening Comprehension', 'parent' => 'certification', 'order_number' => 31],
            ['code' => 'writing', 'name' => 'Structure & Written Expression', 'parent' => 'certification', 'order_number' => 32],
            ['code' => 'reading', 'name' => 'Reading Comprehension', 'parent' => 'certification', 'order_number' => 33],
            ['code' => 'teknis', 'name' => 'Kompetensi Teknis', 'parent' => 'pppk_full', 'order_number' => 41],
            ['code' => 'social culture', 'name' => 'Sosial Kultural', 'parent' => 'pppk_full', 'order_number' => 42],
            ['code' => 'management', 'name' => 'Manajerial', 'parent' => 'pppk_full', 'order_number' => 43],
            ['code' => 'interview', 'name' => 'Interview', 'parent' => 'pppk_full', 'order_number' => 44],
            ['code' => 'word', 'name' => 'Microsoft Word', 'parent' => 'computer', 'order_number' => 51],
            ['code' => 'excel', 'name' => 'Microsoft Excel', 'parent' => 'computer', 'order_number' => 52],
            ['code' => 'ppt', 'name' => 'Microsoft PowerPoint', 'parent' => 'computer', 'order_number' => 53],
            ['code' => 'utbk_section', 'name' => 'UTBK Section', 'parent' => 'utbk_full', 'order_number' => 60],
            ['code' => 'penalaran_umum', 'name' => 'Penalaran Umum', 'parent' => 'utbk_full', 'order_number' => 61],
            ['code' => 'pengetahuan_umum', 'name' => 'Pengetahuan & Pemahaman Umum', 'parent' => 'utbk_full', 'order_number' => 62],
            ['code' => 'pengetahuan_kuantitatif', 'name' => 'Pengetahuan Kuantitatif', 'parent' => 'utbk_full', 'order_number' => 63],
            ['code' => 'pemahaman_bacaan_menulis', 'name' => 'Pemahaman Bacaan & Menulis', 'parent' => 'utbk_full', 'order_number' => 64],
            ['code' => 'literasi_bahasa_indonesia', 'name' => 'Literasi Bahasa Indonesia', 'parent' => 'utbk_full', 'order_number' => 65],
            ['code' => 'literasi_bahasa_inggris', 'name' => 'Literasi Bahasa Inggris', 'parent' => 'utbk_full', 'order_number' => 66],
            ['code' => 'penalaran_matematika', 'name' => 'Penalaran Matematika', 'parent' => 'utbk_full', 'order_number' => 67],
            ['code' => 'utbk_penalaran_umum', 'name' => 'UTBK - Penalaran Umum', 'parent' => 'utbk_full', 'order_number' => 71],
            ['code' => 'utbk_pengetahuan_umum', 'name' => 'UTBK - Pengetahuan & Pemahaman Umum', 'parent' => 'utbk_full', 'order_number' => 72],
            ['code' => 'utbk_pengetahuan_kuantitatif', 'name' => 'UTBK - Pengetahuan Kuantitatif', 'parent' => 'utbk_full', 'order_number' => 73],
            ['code' => 'utbk_pemahaman_bacaan_menulis', 'name' => 'UTBK - Pemahaman Bacaan & Menulis', 'parent' => 'utbk_full', 'order_number' => 74],
            ['code' => 'utbk_literasi_bahasa_indonesia', 'name' => 'UTBK - Literasi Bahasa Indonesia', 'parent' => 'utbk_full', 'order_number' => 75],
            ['code' => 'utbk_literasi_bahasa_inggris', 'name' => 'UTBK - Literasi Bahasa Inggris', 'parent' => 'utbk_full', 'order_number' => 76],
            ['code' => 'utbk_penalaran_matematika', 'name' => 'UTBK - Penalaran Matematika', 'parent' => 'utbk_full', 'order_number' => 77],
        ];

        foreach ($categories as $category) {
            DB::table('material_categories')->updateOrInsert(
                ['code' => $category['code']],
                [
                    'parent_id' => $category['parent'] ? ($parentIds[$category['parent']] ?? null) : null,
                    'name' => $category['name'],
                    'description' => 'Default kategori tryout dari sistem lama.',
                    'order_number' => $category['order_number'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    private function backfillTryoutMappings(): void
    {
        if (
            Schema::hasTable('tryouts')
            && Schema::hasColumn('tryouts', 'material_category_id')
            && Schema::hasColumn('tryouts', 'type_tryout')
        ) {
            DB::statement("
                UPDATE tryouts
                INNER JOIN material_categories ON material_categories.code = tryouts.type_tryout
                SET tryouts.material_category_id = material_categories.category_id
                WHERE tryouts.material_category_id IS NULL
            ");

            if (Schema::hasColumn('tryouts', 'scoring_method')) {
                DB::table('tryouts')
                    ->where('is_irt', true)
                    ->update(['scoring_method' => 'irt']);
            }
        }

        if (
            Schema::hasTable('tryout_details')
            && Schema::hasColumn('tryout_details', 'material_category_id')
            && Schema::hasColumn('tryout_details', 'type_subtest')
        ) {
            DB::statement("
                UPDATE tryout_details
                INNER JOIN material_categories ON material_categories.code = tryout_details.type_subtest
                SET tryout_details.material_category_id = material_categories.category_id
                WHERE tryout_details.material_category_id IS NULL
            ");
        }
    }
};
