<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('material_categories') || ! Schema::hasColumn('material_categories', 'code')) {
            return;
        }

        $usedCodes = DB::table('material_categories')
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->pluck('code')
            ->mapWithKeys(fn ($code) => [(string) $code => true])
            ->all();

        DB::table('material_categories')
            ->where(function ($query) {
                $query->whereNull('code')->orWhere('code', '');
            })
            ->orderBy('category_id')
            ->get(['category_id', 'name'])
            ->each(function ($category) use (&$usedCodes) {
                $baseCode = Str::of((string) $category->name)->slug('_')->lower()->toString() ?: 'kategori';
                $code = $baseCode;
                $suffix = 2;

                while (isset($usedCodes[$code])) {
                    $code = "{$baseCode}_{$suffix}";
                    $suffix++;
                }

                $usedCodes[$code] = true;

                DB::table('material_categories')
                    ->where('category_id', $category->category_id)
                    ->update([
                        'code' => $code,
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        // Generated codes are intentionally kept because tryouts can reference them.
    }
};
