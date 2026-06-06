<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('participant_destination_categories')
            || !Schema::hasTable('material_categories')
        ) {
            return;
        }

        if (DB::table('participant_destination_categories')->exists()) {
            return;
        }

        $now = now();
        $idMap = [];

        $roots = DB::table('material_categories')
            ->whereNull('parent_id')
            ->orderBy('order_number')
            ->orderBy('name')
            ->get();

        foreach ($roots as $root) {
            $idMap[$root->category_id] = DB::table('participant_destination_categories')->insertGetId([
                'parent_id' => null,
                'name' => $root->name,
                'slug' => $this->uniqueSlug($root->name, null),
                'is_active' => (bool) $root->is_active,
                'sort_order' => (int) $root->order_number,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $children = DB::table('material_categories')
            ->whereNotNull('parent_id')
            ->orderBy('order_number')
            ->orderBy('name')
            ->get();

        foreach ($children as $child) {
            $parentId = $idMap[$child->parent_id] ?? null;
            if (!$parentId) {
                continue;
            }

            DB::table('participant_destination_categories')->insert([
                'parent_id' => $parentId,
                'name' => $child->name,
                'slug' => $this->uniqueSlug($child->name, $parentId),
                'is_active' => (bool) $child->is_active,
                'sort_order' => (int) $child->order_number,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        //
    }

    private function uniqueSlug(string $name, ?int $parentId): string
    {
        $baseSlug = Str::slug($name) ?: 'kategori';
        $slug = $baseSlug;
        $counter = 2;

        while (
            DB::table('participant_destination_categories')
                ->where('parent_id', $parentId)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
};
