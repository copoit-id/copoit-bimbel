<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kecermatan_rows', function (Blueprint $table) {
            $table->id('row_id');
            $table->unsignedBigInteger('column_id');
            $table->integer('row_number'); // 1, 2, 3, 4, 5
            $table->text('row_text'); // teks baris soal (rich text)
            $table->timestamps();

            $table->foreign('column_id')->references('column_id')->on('kecermatan_columns')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kecermatan_rows');
    }
};
