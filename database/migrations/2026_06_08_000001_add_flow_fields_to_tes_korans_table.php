<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tes_korans', function (Blueprint $table) {
            if (!Schema::hasColumn('tes_korans', 'number_type')) {
                $table->enum('number_type', ['satuan', 'puluhan', 'ratusan'])
                    ->default('satuan')
                    ->after('direction');
            }

            if (!Schema::hasColumn('tes_korans', 'operation_type')) {
                $table->enum('operation_type', ['addition', 'subtraction', 'division'])
                    ->default('addition')
                    ->after('number_type');
            }

            if (!Schema::hasColumn('tes_korans', 'column_duration_seconds')) {
                $table->unsignedInteger('column_duration_seconds')
                    ->default(60)
                    ->after('operation_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tes_korans', function (Blueprint $table) {
            if (Schema::hasColumn('tes_korans', 'column_duration_seconds')) {
                $table->dropColumn('column_duration_seconds');
            }

            if (Schema::hasColumn('tes_korans', 'operation_type')) {
                $table->dropColumn('operation_type');
            }

            if (Schema::hasColumn('tes_korans', 'number_type')) {
                $table->dropColumn('number_type');
            }
        });
    }
};
