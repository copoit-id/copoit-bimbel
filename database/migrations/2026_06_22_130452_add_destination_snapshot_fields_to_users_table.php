<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'participant_destination_source')) {
                $table->string('participant_destination_source', 30)
                    ->nullable()
                    ->after('participant_destination_category_id');
            }

            if (!Schema::hasColumn('users', 'participant_destination_external_id')) {
                $table->string('participant_destination_external_id', 100)
                    ->nullable()
                    ->after('participant_destination_source');
            }

            if (!Schema::hasColumn('users', 'participant_destination_institution_name')) {
                $table->string('participant_destination_institution_name')
                    ->nullable()
                    ->after('participant_destination_external_id');
            }

            if (!Schema::hasColumn('users', 'participant_destination_program_name')) {
                $table->string('participant_destination_program_name')
                    ->nullable()
                    ->after('participant_destination_institution_name');
            }
        });

        DB::table('users')
            ->whereNotNull('participant_destination_category_id')
            ->whereNull('participant_destination_source')
            ->update(['participant_destination_source' => 'db']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'participant_destination_program_name')) {
                $table->dropColumn('participant_destination_program_name');
            }

            if (Schema::hasColumn('users', 'participant_destination_institution_name')) {
                $table->dropColumn('participant_destination_institution_name');
            }

            if (Schema::hasColumn('users', 'participant_destination_external_id')) {
                $table->dropColumn('participant_destination_external_id');
            }

            if (Schema::hasColumn('users', 'participant_destination_source')) {
                $table->dropColumn('participant_destination_source');
            }
        });
    }
};
