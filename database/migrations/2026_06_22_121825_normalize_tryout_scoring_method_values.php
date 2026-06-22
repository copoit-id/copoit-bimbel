<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tryouts') || ! Schema::hasColumn('tryouts', 'scoring_method')) {
            return;
        }

        DB::table('tryouts')->update(['scoring_method' => 'normal']);

        DB::table('tryouts')
            ->where(function ($query) {
                $query->where('is_toefl', true)
                    ->orWhere('type_tryout', 'certification');
            })
            ->update([
                'scoring_method' => 'toefl_itp',
                'is_toefl' => true,
                'is_irt' => false,
            ]);

        DB::table('tryouts')
            ->where('is_irt', true)
            ->update([
                'scoring_method' => 'irt_utbk',
                'is_toefl' => false,
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tryouts') || ! Schema::hasColumn('tryouts', 'scoring_method')) {
            return;
        }

        DB::table('tryouts')
            ->where('scoring_method', 'irt_utbk')
            ->update(['scoring_method' => 'irt']);

        DB::table('tryouts')
            ->where('scoring_method', 'toefl_itp')
            ->update(['scoring_method' => 'normal']);
    }
};
