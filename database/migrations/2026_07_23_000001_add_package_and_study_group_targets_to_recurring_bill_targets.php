<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recurring_bill_targets')) {
            return;
        }

        Schema::table('recurring_bill_targets', function (Blueprint $table): void {
            if (! Schema::hasColumn('recurring_bill_targets', 'package_id')) {
                $table->foreignId('package_id')
                    ->nullable()
                    ->after('class_id')
                    ->constrained('packages', 'package_id')
                    ->nullOnDelete();
                $table->unique(['recurring_bill_id', 'package_id']);
            }

            if (! Schema::hasColumn('recurring_bill_targets', 'study_group_id')) {
                $table->foreignId('study_group_id')
                    ->nullable()
                    ->after('package_id')
                    ->constrained('study_groups')
                    ->nullOnDelete();
                $table->unique(['recurring_bill_id', 'study_group_id']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('recurring_bill_targets')) {
            return;
        }

        Schema::table('recurring_bill_targets', function (Blueprint $table): void {
            if (Schema::hasColumn('recurring_bill_targets', 'study_group_id')) {
                try {
                    $table->dropForeign(['study_group_id']);
                } catch (\Throwable) {
                    // Foreign key may not exist in an older deployment.
                }
                try {
                    $table->dropUnique(['recurring_bill_id', 'study_group_id']);
                } catch (\Throwable) {
                    // Unique index may not exist in an older deployment.
                }
                $table->dropColumn('study_group_id');
            }

            if (Schema::hasColumn('recurring_bill_targets', 'package_id')) {
                try {
                    $table->dropForeign(['package_id']);
                } catch (\Throwable) {
                    // Foreign key may not exist in an older deployment.
                }
                try {
                    $table->dropUnique(['recurring_bill_id', 'package_id']);
                } catch (\Throwable) {
                    // Unique index may not exist in an older deployment.
                }
                $table->dropColumn('package_id');
            }
        });
    }
};
