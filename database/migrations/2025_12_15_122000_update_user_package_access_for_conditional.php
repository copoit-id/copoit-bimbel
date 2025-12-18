<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_package_access', function (Blueprint $table) {
            if (!Schema::hasColumn('user_package_access', 'requirement_proof_path')) {
                $table->string('requirement_proof_path')->nullable()->after('payment_status');
            }

            if (!Schema::hasColumn('user_package_access', 'requirement_review_notes')) {
                $table->text('requirement_review_notes')->nullable()->after('requirement_proof_path');
            }

            if (!Schema::hasColumn('user_package_access', 'requirement_status')) {
                $table->enum('requirement_status', ['none', 'pending', 'approved', 'rejected'])
                    ->default('none')
                    ->after('requirement_review_notes');
            }
        });

        DB::statement("ALTER TABLE user_package_access MODIFY COLUMN status ENUM('active','expired','suspended','pending') NOT NULL DEFAULT 'active'");
        DB::statement("ALTER TABLE user_package_access MODIFY COLUMN payment_status ENUM('paid','pending','failed','free','conditional') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('user_package_access', function (Blueprint $table) {
            if (Schema::hasColumn('user_package_access', 'requirement_status')) {
                $table->dropColumn('requirement_status');
            }

            if (Schema::hasColumn('user_package_access', 'requirement_review_notes')) {
                $table->dropColumn('requirement_review_notes');
            }

            if (Schema::hasColumn('user_package_access', 'requirement_proof_path')) {
                $table->dropColumn('requirement_proof_path');
            }
        });

        DB::statement("ALTER TABLE user_package_access MODIFY COLUMN status ENUM('active','expired','suspended') NOT NULL DEFAULT 'active'");
        DB::statement("ALTER TABLE user_package_access MODIFY COLUMN payment_status ENUM('paid','pending','failed','free') NOT NULL DEFAULT 'pending'");
    }
};
