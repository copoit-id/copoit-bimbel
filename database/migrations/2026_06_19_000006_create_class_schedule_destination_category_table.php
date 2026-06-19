<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('class_schedules')
            || !Schema::hasTable('participant_destination_categories')
        ) {
            return;
        }

        if (!Schema::hasTable('class_schedule_destination_categories')) {
            Schema::create('class_schedule_destination_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('class_schedule_id');
                $table->unsignedBigInteger('participant_destination_category_id');
                $table->timestamps();

                $table->unique(
                    ['class_schedule_id', 'participant_destination_category_id'],
                    'csdc_schedule_category_unique'
                );
                $table->index('participant_destination_category_id', 'csdc_category_index');
                $table->foreign('class_schedule_id', 'csdc_schedule_fk')
                    ->references('id')
                    ->on('class_schedules')
                    ->cascadeOnDelete();
                $table->foreign('participant_destination_category_id', 'csdc_category_fk')
                    ->references('id')
                    ->on('participant_destination_categories')
                    ->cascadeOnDelete();
            });

            return;
        }

        $this->ensureExistingTableConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedule_destination_categories');
    }

    private function ensureExistingTableConstraints(): void
    {
        Schema::table('class_schedule_destination_categories', function (Blueprint $table) {
            if (!Schema::hasIndex('class_schedule_destination_categories', 'csdc_schedule_category_unique')) {
                $table->unique(
                    ['class_schedule_id', 'participant_destination_category_id'],
                    'csdc_schedule_category_unique'
                );
            }

            if (!Schema::hasIndex('class_schedule_destination_categories', 'csdc_category_index')) {
                $table->index('participant_destination_category_id', 'csdc_category_index');
            }
        });

        $this->addForeignIfMissing(
            'csdc_schedule_fk',
            'class_schedule_id',
            'class_schedules',
            'id'
        );

        $this->addForeignIfMissing(
            'csdc_category_fk',
            'participant_destination_category_id',
            'participant_destination_categories',
            'id'
        );
    }

    private function addForeignIfMissing(string $name, string $column, string $referencesTable, string $referencesColumn): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'class_schedule_destination_categories')
            ->where('CONSTRAINT_NAME', $name)
            ->exists();

        if ($exists) {
            return;
        }

        try {
            Schema::table('class_schedule_destination_categories', function (Blueprint $table) use ($name, $column, $referencesTable, $referencesColumn) {
                $table->foreign($column, $name)
                    ->references($referencesColumn)
                    ->on($referencesTable)
                    ->cascadeOnDelete();
            });
        } catch (\Throwable) {
            // Table may already have an equivalent FK from the failed/partial migrate attempt.
        }
    }
};
