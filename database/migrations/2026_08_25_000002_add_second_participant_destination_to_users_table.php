<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'second_participant_destination_category_id')) {
                $table->foreignId('second_participant_destination_category_id')
                    ->nullable()
                    ->constrained('participant_destination_categories')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'second_participant_destination_source')) {
                $table->string('second_participant_destination_source', 16)->nullable();
            }

            if (! Schema::hasColumn('users', 'second_participant_destination_external_id')) {
                $table->string('second_participant_destination_external_id', 100)->nullable();
            }

            if (! Schema::hasColumn('users', 'second_participant_destination_institution_name')) {
                $table->string('second_participant_destination_institution_name')->nullable();
            }

            if (! Schema::hasColumn('users', 'second_participant_destination_program_name')) {
                $table->string('second_participant_destination_program_name')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'second_participant_destination_category_id')) {
                try {
                    $table->dropForeign(['second_participant_destination_category_id']);
                } catch (\Exception) {
                    // Foreign key mungkin sudah tidak ada.
                }
                try {
                    $table->dropIndex(['second_participant_destination_category_id']);
                } catch (\Exception) {
                    // Index mungkin sudah tidak ada.
                }
                $table->dropColumn('second_participant_destination_category_id');
            }

            foreach ([
                'second_participant_destination_source',
                'second_participant_destination_external_id',
                'second_participant_destination_institution_name',
                'second_participant_destination_program_name',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
