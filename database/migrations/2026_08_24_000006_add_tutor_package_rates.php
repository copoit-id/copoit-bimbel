<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tutor_package_rates')
            && Schema::hasTable('tentors')
            && Schema::hasTable('packages')) {
            Schema::create('tutor_package_rates', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tentor_id')->constrained('tentors')->cascadeOnDelete();
                $table->unsignedBigInteger('package_id');
                $table->decimal('amount', 12, 0)->default(0);
                $table->timestamps();

                $table->foreign('package_id')->references('package_id')->on('packages')->cascadeOnDelete();
                $table->unique(['tentor_id', 'package_id']);
            });
        }

        if (Schema::hasTable('tutor_payroll_items')
            && Schema::hasTable('packages')
            && ! Schema::hasColumn('tutor_payroll_items', 'package_id')) {
            Schema::table('tutor_payroll_items', function (Blueprint $table): void {
                $table->unsignedBigInteger('package_id')->nullable()->after('class_session_id');
                $table->foreign('package_id')->references('package_id')->on('packages')->nullOnDelete();
                $table->index('package_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tutor_payroll_items') && Schema::hasColumn('tutor_payroll_items', 'package_id')) {
            Schema::table('tutor_payroll_items', function (Blueprint $table): void {
                try {
                    $table->dropForeign(['package_id']);
                } catch (\Throwable) {
                    // Foreign key may not exist on older installations.
                }

                try {
                    $table->dropIndex(['package_id']);
                } catch (\Throwable) {
                    // Index may not exist on older installations.
                }

                $table->dropColumn('package_id');
            });
        }

        if (Schema::hasTable('tutor_package_rates')) {
            Schema::drop('tutor_package_rates');
        }
    }
};
