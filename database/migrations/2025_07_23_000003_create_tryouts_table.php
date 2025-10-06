<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tryouts', function (Blueprint $table) {
            $table->id('tryout_id');
            $table->string('name');
            $table->text('description');
            $table->boolean('is_certification')->default(false);
            $table->boolean('is_toefl')->default(false);
            $table->enum('type_tryout', ['tiu', 'twk', 'tkp', 'skd_full', 'general', 'certification', 'listening', 'reading', 'writing', 'pppk_full', 'teknis', 'social culture', 'management', 'interview', 'word', 'excel', 'ppt', 'computer']);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tryouts');
    }
};
