<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kecermatan_columns', function (Blueprint $table) {
            $table->id('column_id');
            $table->unsignedBigInteger('tryout_id');
            $table->string('nama_kolom');
            $table->integer('jumlah_soal');
            $table->integer('durasi_kolom'); // dalam menit
            $table->enum('tipe_kolom', ['huruf', 'angka', 'simbol']);
            $table->text('kolom_data'); // JSON: ["A", "B", "C", "D", "E"] atau ["1", "2", "3", "4", "5"]
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('tryout_id')->references('tryout_id')->on('tryouts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kecermatan_columns');
    }
};
