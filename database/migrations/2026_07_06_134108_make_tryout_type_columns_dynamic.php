<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tryouts') && Schema::hasColumn('tryouts', 'type_tryout')) {
            DB::statement('ALTER TABLE tryouts MODIFY type_tryout VARCHAR(100) NOT NULL');
        }

        if (Schema::hasTable('tryout_details') && Schema::hasColumn('tryout_details', 'type_subtest')) {
            DB::statement('ALTER TABLE tryout_details MODIFY type_subtest VARCHAR(100) NOT NULL');
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('tryouts')
            && Schema::hasColumn('tryouts', 'type_tryout')
            && $this->hasOnlyValues('tryouts', 'type_tryout', $this->tryoutTypeValues())
        ) {
            DB::statement($this->modifyEnumSql('tryouts', 'type_tryout', $this->tryoutTypeValues()));
        }

        if (
            Schema::hasTable('tryout_details')
            && Schema::hasColumn('tryout_details', 'type_subtest')
            && $this->hasOnlyValues('tryout_details', 'type_subtest', $this->subtestTypeValues())
        ) {
            DB::statement($this->modifyEnumSql('tryout_details', 'type_subtest', $this->subtestTypeValues()));
        }
    }

    private function hasOnlyValues(string $table, string $column, array $allowedValues): bool
    {
        return ! DB::table($table)
            ->whereNotIn($column, $allowedValues)
            ->exists();
    }

    private function modifyEnumSql(string $table, string $column, array $values): string
    {
        $quotedValues = collect($values)
            ->map(fn (string $value) => "'".str_replace("'", "''", $value)."'")
            ->implode(',');

        return "ALTER TABLE {$table} MODIFY {$column} ENUM({$quotedValues}) NOT NULL";
    }

    private function tryoutTypeValues(): array
    {
        return [
            'tiu',
            'twk',
            'tkp',
            'skd_full',
            'general',
            'tpa',
            'tbi',
            'tob',
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
            'utbk_penalaran_matematika',
        ];
    }

    private function subtestTypeValues(): array
    {
        return [
            'twk',
            'tiu',
            'tkp',
            'general',
            'tpa',
            'tbi',
            'tob',
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
            'penalaran_matematika',
        ];
    }
};
