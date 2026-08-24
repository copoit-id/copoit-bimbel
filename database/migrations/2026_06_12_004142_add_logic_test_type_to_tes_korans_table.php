<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tes_korans', function (Blueprint $table) {
            if (!Schema::hasColumn('tes_korans', 'logic_test_type')) {
                $table->enum('logic_test_type', ['standar', 'stan'])
                    ->default('standar')
                    ->after('test_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tes_korans', function (Blueprint $table) {
            if (Schema::hasColumn('tes_korans', 'logic_test_type')) {
                $table->dropColumn('logic_test_type');
            }
        });
    }
};
