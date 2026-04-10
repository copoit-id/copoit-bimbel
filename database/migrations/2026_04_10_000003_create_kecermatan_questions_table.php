<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kecermatan_questions', function (Blueprint $table) {
            $table->id('question_id');
            $table->unsignedBigInteger('column_id');
            $table->unsignedBigInteger('row_id');
            $table->integer('question_number'); // nomor soal 1, 2, 3, dst
            $table->string('option_a'); // konten pilihan A
            $table->string('option_b'); // konten pilihan B
            $table->string('option_c'); // konten pilihan C
            $table->string('option_d'); // konten pilihan D
            $table->string('correct_answer'); // A, B, C, atau D (yang TIDAK ADA di kolom)
            $table->string('missing_from_column'); // huruf/angka yang hilang dari kolom (jawaban benar)
            $table->timestamps();

            $table->foreign('column_id')->references('column_id')->on('kecermatan_columns')->onDelete('cascade');
            $table->foreign('row_id')->references('row_id')->on('kecermatan_rows')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kecermatan_questions');
    }
};
