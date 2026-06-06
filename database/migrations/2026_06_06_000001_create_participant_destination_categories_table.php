<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('participant_destination_categories')) {
            Schema::create('participant_destination_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')
                    ->nullable()
                    ->constrained('participant_destination_categories')
                    ->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['parent_id', 'slug'], 'pdc_parent_slug_unique');
                $table->index(['parent_id', 'is_active', 'sort_order'], 'pdc_parent_active_order_index');
            });
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'participant_destination_category_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('participant_destination_category_id')
                    ->nullable()
                    ->after('phone')
                    ->constrained('participant_destination_categories')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'participant_destination_category_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('participant_destination_category_id');
            });
        }

        Schema::dropIfExists('participant_destination_categories');
    }
};
