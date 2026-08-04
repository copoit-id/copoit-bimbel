<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tutor_attendances')) {
            return;
        }

        Schema::table('tutor_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('tutor_attendances', 'approval_status')) {
                $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('status');
            }

            if (! Schema::hasColumn('tutor_attendances', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('marked_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('tutor_attendances', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        DB::table('tutor_attendances')
            ->where('source', 'admin')
            ->where('approval_status', 'pending')
            ->update([
                'approval_status' => 'approved',
                'approved_by' => DB::raw('marked_by'),
                'approved_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('tutor_attendances')) {
            Schema::table('tutor_attendances', function (Blueprint $table) {
                if (Schema::hasColumn('tutor_attendances', 'approved_at')) {
                    $table->dropColumn('approved_at');
                }

                if (Schema::hasColumn('tutor_attendances', 'approved_by')) {
                    try {
                        $table->dropForeign(['approved_by']);
                    } catch (\Throwable) {
                        // The foreign key may not exist on older installations.
                    }
                    $table->dropColumn('approved_by');
                }

                if (Schema::hasColumn('tutor_attendances', 'approval_status')) {
                    $table->dropColumn('approval_status');
                }
            });
        }
    }
};
