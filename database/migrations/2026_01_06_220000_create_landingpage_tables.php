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
        // Hero Section
        Schema::create('landingpage_hero', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('subtitle');
            $table->text('description')->nullable();
            $table->string('button_text')->default('Get Started');
            $table->string('button_link')->nullable();
            $table->string('image')->nullable();
            // Statistik fields
            $table->string('stat_1_number')->default('1000+');
            $table->string('stat_1_text')->default('Siswa Aktif');
            $table->string('stat_2_number')->default('95%');
            $table->string('stat_2_text')->default('Tingkat Kelulusan');
            $table->string('stat_3_number')->default('50+');
            $table->string('stat_3_text')->default('Instruktur Expert');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Features/Keunggulan
        Schema::create('landingpage_features', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('icon')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Gallery
        Schema::create('landingpage_gallery', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image');
            $table->string('category')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Testimonials
        Schema::create('landingpage_testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position')->nullable();
            $table->text('content');
            $table->string('photo')->nullable();
            $table->integer('rating')->default(5);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // CTA Section
        Schema::create('landingpage_cta', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('primary_button_text')->default('Daftar Sekarang');
            $table->string('secondary_button_text')->default('Login');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landingpage_cta');
        Schema::dropIfExists('landingpage_testimonials');
        Schema::dropIfExists('landingpage_gallery');
        Schema::dropIfExists('landingpage_features');
        Schema::dropIfExists('landingpage_hero');
    }
};
