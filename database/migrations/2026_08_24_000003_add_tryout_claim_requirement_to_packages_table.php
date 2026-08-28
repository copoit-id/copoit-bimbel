<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('packages')) {
            return;
        }

        Schema::table('packages', function (Blueprint $table): void {
            if (! Schema::hasColumn('packages', 'free_claim_requirement_type')) {
                $table->string('free_claim_requirement_type', 30)
                    ->nullable();
            }

            if (! Schema::hasColumn('packages', 'free_claim_tryout_id')) {
                $table->unsignedBigInteger('free_claim_tryout_id')
                    ->nullable();
                $table->index('free_claim_tryout_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('packages')) {
            return;
        }

        Schema::table('packages', function (Blueprint $table): void {
            if (Schema::hasColumn('packages', 'free_claim_tryout_id')) {
                $table->dropIndex(['free_claim_tryout_id']);
                $table->dropColumn('free_claim_tryout_id');
            }

            if (Schema::hasColumn('packages', 'free_claim_requirement_type')) {
                $table->dropColumn('free_claim_requirement_type');
            }
        });
    }
};
