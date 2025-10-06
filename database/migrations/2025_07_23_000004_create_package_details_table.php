<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_details', function (Blueprint $table) {
            $table->id('package_detail_id');
            $table->foreignId('package_id')->constrained('packages', 'package_id')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes', 'class_id')->onDelete('cascade')->nullable();
            $table->foreignId('tryout_id')->constrained('tryouts', 'tryout_id')->onDelete('cascade')->nullable();
            $table->enum('content_type', ['tryout', 'class']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_details');
    }
};
