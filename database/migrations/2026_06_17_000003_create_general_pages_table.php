<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_key')->unique();
            $table->string('template_key')->default('default');
            $table->json('content')->nullable();
            $table->json('settings')->nullable();
            $table->json('seo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_pages');
    }
};
