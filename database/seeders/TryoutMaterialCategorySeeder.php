<?php

namespace Database\Seeders;

use App\Models\MaterialCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TryoutMaterialCategorySeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('material_categories') || ! Schema::hasColumn('material_categories', 'code')) {
            return;
        }

        $groups = [
            ['code' => 'skd_full', 'name' => 'SKD Full', 'description' => 'TWK, TIU, dan TKP.', 'order_number' => 10],
            ['code' => 'utbk_full', 'name' => 'UTBK TPS Full', 'description' => 'Semua subtest UTBK TPS.', 'order_number' => 20],
            ['code' => 'certification', 'name' => 'Certification Full (TOEFL ITP)', 'description' => 'Listening, Writing, dan Reading.', 'order_number' => 30],
            ['code' => 'pppk_full', 'name' => 'PPPK Full', 'description' => 'Teknis, Sosial Kultural, dan Interview.', 'order_number' => 40],
            ['code' => 'computer', 'name' => 'Computer Full', 'description' => 'Word, Excel, dan PowerPoint.', 'order_number' => 50],
        ];

        foreach ($groups as $group) {
            MaterialCategory::updateOrCreate(
                ['code' => $group['code']],
                [
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'order_number' => $group['order_number'],
                    'is_active' => true,
                ]
            );
        }

        $parentIds = MaterialCategory::query()
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
            MaterialCategory::updateOrCreate(
                ['code' => $category['code']],
                [
                    'parent_id' => $category['parent'] ? ($parentIds[$category['parent']] ?? null) : null,
                    'name' => $category['name'],
                    'description' => 'Default kategori tryout dari sistem lama.',
                    'order_number' => $category['order_number'],
                    'is_active' => true,
                ]
            );
        }
    }
}
