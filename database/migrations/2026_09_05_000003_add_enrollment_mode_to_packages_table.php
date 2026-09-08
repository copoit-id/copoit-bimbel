<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('packages') && ! Schema::hasColumn('packages', 'enrollment_mode')) {
            Schema::table('packages', function (Blueprint $table): void {
                $table->string('enrollment_mode', 30)->default('direct_purchase')->after('type_package');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('packages') && Schema::hasColumn('packages', 'enrollment_mode')) {
            Schema::table('packages', function (Blueprint $table): void {
                $table->dropColumn('enrollment_mode');
            });
        }
    }
};
