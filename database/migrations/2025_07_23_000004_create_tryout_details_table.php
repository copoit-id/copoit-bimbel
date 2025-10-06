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
        Schema::create('tryout_details', function (Blueprint $table) {
            $table->id('tryout_detail_id');
            $table->unsignedBigInteger('tryout_id');
            $table->enum('type_subtest', [
                'twk',
                'tiu',
                'tkp',
                'general',
                'listening',
                'reading',
                'writing',
                'teknis',
                'social culture',
                'management',
                'interview',
                'word',
                'excel',
                'ppt',
            ]);
            $table->integer('duration')->default(60);
            $table->decimal('passing_score', 5, 2)->default(60.00);
            $table->timestamps();

            $table->foreign('tryout_id')->references('tryout_id')->on('tryouts')->onDelete('cascade');
            $table->index('tryout_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tryout_details');
    }
};
