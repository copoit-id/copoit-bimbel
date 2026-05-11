<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tes_korans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_id');
            $table->string('name');
            $table->enum('test_type', ['pauli', 'kraepelin'])->default('pauli');
            $table->enum('direction', ['top_to_bottom', 'bottom_to_top'])->default('top_to_bottom');
            $table->integer('duration_minutes')->default(60);
            $table->integer('columns_count')->default(30);
            $table->integer('rows_count')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('package_id')->references('package_id')->on('packages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_korans');
    }
};
