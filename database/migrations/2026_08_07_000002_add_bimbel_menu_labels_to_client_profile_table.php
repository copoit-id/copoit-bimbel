<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            if (! Schema::hasColumn('client_profile', 'material_nav_label')) {
                $table->string('material_nav_label', 80)->default('Kelas & Materi')->after('bimbel_nav_label');
            }

            if (! Schema::hasColumn('client_profile', 'package_nav_label')) {
                $table->string('package_nav_label', 80)->default('Paket Belajar')->after('material_nav_label');
            }

            if (! Schema::hasColumn('client_profile', 'tryout_nav_label')) {
                $table->string('tryout_nav_label', 80)->default('Ujian & Try Out')->after('package_nav_label');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $columns = collect(['material_nav_label', 'package_nav_label', 'tryout_nav_label'])
                ->filter(fn (string $column): bool => Schema::hasColumn('client_profile', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
