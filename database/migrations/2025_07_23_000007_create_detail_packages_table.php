<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_packages', function (Blueprint $table) {
            $table->id('detail_package_id');
            $table->foreignId('package_id')->constrained('packages', 'package_id')->onDelete('cascade');
            $table->morphs('detailable'); // For polymorphic relationship (classes/tryouts)
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['package_id', 'detailable_type', 'detailable_id'], 'unique_package_detailable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_packages');
    }
};
