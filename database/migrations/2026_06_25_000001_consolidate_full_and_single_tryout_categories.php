<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UTBK_SINGLE_CATEGORY_MAP = [
        'utbk_penalaran_umum' => 'penalaran_umum',
        'utbk_pengetahuan_umum' => 'pengetahuan_umum',
        'utbk_pengetahuan_kuantitatif' => 'pengetahuan_kuantitatif',
        'utbk_pemahaman_bacaan_menulis' => 'pemahaman_bacaan_menulis',
        'utbk_literasi_bahasa_indonesia' => 'literasi_bahasa_indonesia',
        'utbk_literasi_bahasa_inggris' => 'literasi_bahasa_inggris',
        'utbk_penalaran_matematika' => 'penalaran_matematika',
    ];

    public function up(): void
    {
        if (
            ! Schema::hasTable('material_categories')
            || ! Schema::hasColumn('material_categories', 'parent_id')
            || ! Schema::hasColumn('material_categories', 'code')
        ) {
            return;
        }

        foreach (self::UTBK_SINGLE_CATEGORY_MAP as $duplicateCode => $canonicalCode) {
            $this->mergeCategoryByCode($duplicateCode, $canonicalCode);
        }

        $this->removeUnusedCategoryByCode('utbk_section');
        $this->mergeLegacySkdCategories();
    }

    public function down(): void
    {
        // Consolidated categories are intentionally not recreated.
    }

    private function mergeCategoryByCode(string $duplicateCode, string $canonicalCode): void
    {
        $duplicateId = DB::table('material_categories')
            ->where('code', $duplicateCode)
            ->value('category_id');
        $canonicalId = DB::table('material_categories')
            ->where('code', $canonicalCode)
            ->value('category_id');

        if (! $duplicateId || ! $canonicalId || $duplicateId === $canonicalId) {
            return;
        }

        $this->moveCategoryUsage((int) $duplicateId, (int) $canonicalId);
        DB::table('material_categories')->where('category_id', $duplicateId)->delete();
    }

    private function mergeLegacySkdCategories(): void
    {
        $canonicalRootId = DB::table('material_categories')
            ->where('code', 'skd_full')
            ->value('category_id');

        if (! $canonicalRootId) {
            return;
        }

        $legacyRoots = DB::table('material_categories')
            ->whereNull('code')
            ->whereRaw('LOWER(name) = ?', ['skd'])
            ->pluck('category_id');

        foreach ($legacyRoots as $legacyRootId) {
            $legacyChildren = DB::table('material_categories')
                ->where('parent_id', $legacyRootId)
                ->get(['category_id', 'name', 'code']);

            foreach ($legacyChildren as $legacyChild) {
                $canonicalCode = match (strtolower(trim((string) ($legacyChild->code ?: $legacyChild->name)))) {
                    'twk' => 'twk',
                    'tiu' => 'tiu',
                    'tkp' => 'tkp',
                    default => null,
                };

                if (! $canonicalCode) {
                    DB::table('material_categories')
                        ->where('category_id', $legacyChild->category_id)
                        ->update(['parent_id' => $canonicalRootId]);

                    continue;
                }

                $canonicalChildId = DB::table('material_categories')
                    ->where('code', $canonicalCode)
                    ->value('category_id');

                if ($canonicalChildId) {
                    $this->moveCategoryUsage((int) $legacyChild->category_id, (int) $canonicalChildId);
                    DB::table('material_categories')->where('category_id', $legacyChild->category_id)->delete();
                }
            }

            $this->moveCategoryUsage((int) $legacyRootId, (int) $canonicalRootId);
            DB::table('material_categories')->where('category_id', $legacyRootId)->delete();
        }
    }

    private function moveCategoryUsage(int $fromCategoryId, int $toCategoryId): void
    {
        if (Schema::hasTable('material_category_pivot')) {
            $materialIds = DB::table('material_category_pivot')
                ->where('category_id', $fromCategoryId)
                ->pluck('material_id');

            foreach ($materialIds as $materialId) {
                DB::table('material_category_pivot')->insertOrIgnore([
                    'material_id' => $materialId,
                    'category_id' => $toCategoryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('material_category_pivot')
                ->where('category_id', $fromCategoryId)
                ->delete();
        }

        if (Schema::hasTable('tryouts') && Schema::hasColumn('tryouts', 'material_category_id')) {
            DB::table('tryouts')
                ->where('material_category_id', $fromCategoryId)
                ->update(['material_category_id' => $toCategoryId]);
        }

        if (Schema::hasTable('tryout_details') && Schema::hasColumn('tryout_details', 'material_category_id')) {
            DB::table('tryout_details')
                ->where('material_category_id', $fromCategoryId)
                ->update(['material_category_id' => $toCategoryId]);
        }
    }

    private function removeUnusedCategoryByCode(string $code): void
    {
        $categoryId = DB::table('material_categories')
            ->where('code', $code)
            ->value('category_id');

        if (! $categoryId || $this->categoryIsUsed((int) $categoryId)) {
            return;
        }

        DB::table('material_categories')->where('category_id', $categoryId)->delete();
    }

    private function categoryIsUsed(int $categoryId): bool
    {
        return (Schema::hasTable('material_category_pivot')
                && DB::table('material_category_pivot')->where('category_id', $categoryId)->exists())
            || (Schema::hasTable('tryouts')
                && Schema::hasColumn('tryouts', 'material_category_id')
                && DB::table('tryouts')->where('material_category_id', $categoryId)->exists())
            || (Schema::hasTable('tryout_details')
                && Schema::hasColumn('tryout_details', 'material_category_id')
                && DB::table('tryout_details')->where('material_category_id', $categoryId)->exists());
    }
};
