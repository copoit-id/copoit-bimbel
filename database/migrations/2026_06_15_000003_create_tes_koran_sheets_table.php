<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tes_koran_sheets')) {
            Schema::create('tes_koran_sheets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tes_koran_id')->constrained('tes_korans')->cascadeOnDelete();
                $table->unsignedSmallInteger('sheet_order')->default(1);
                $table->string('name')->nullable();
                $table->enum('number_type', ['satuan', 'puluhan', 'ratusan'])->default('satuan');
                $table->enum('operation_type', ['addition', 'subtraction', 'division'])->default('addition');
                $table->unsignedInteger('column_duration_seconds')->default(60);
                $table->unsignedSmallInteger('columns_count')->default(30);
                $table->unsignedSmallInteger('rows_count')->default(10);
                $table->timestamps();

                $table->index(['tes_koran_id', 'sheet_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_koran_sheets');
    }
};
