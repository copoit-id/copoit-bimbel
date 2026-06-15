<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_package_access', function (Blueprint $table) {
            if (!Schema::hasColumn('user_package_access', 'requirement_proof_paths')) {
                $table->json('requirement_proof_paths')->nullable()->after('requirement_proof_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_package_access', function (Blueprint $table) {
            if (Schema::hasColumn('user_package_access', 'requirement_proof_paths')) {
                $table->dropColumn('requirement_proof_paths');
            }
        });
    }
};
