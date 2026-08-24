<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('general_pages')) {
            return;
        }

        foreach (['landing', 'statistik-ptn', 'artikel'] as $pageKey) {
            $exists = DB::table('general_pages')->where('page_key', $pageKey)->exists();

            if ($exists) {
                DB::table('general_pages')
                    ->where('page_key', $pageKey)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('general_pages')->insert([
                'page_key' => $pageKey,
                'template_key' => 'default',
                'is_active' => false,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
