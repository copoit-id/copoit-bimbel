<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tryout_user_time_adjustments', function (Blueprint $table) {
            $table->bigIncrements('tryout_user_time_id');
            $table->unsignedBigInteger('tryout_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('extra_minutes')->default(0);
            $table->timestamps();

            $table->unique(['tryout_id', 'user_id'], 'tryout_user_time_unique');
            $table->foreign('tryout_id')->references('tryout_id')->on('tryouts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['tryout_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tryout_user_time_adjustments');
    }
};
