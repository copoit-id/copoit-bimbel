<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Schema::table('tryout_details', function (Blueprint $table) {
        //     // Update enum to include certification subtypes
        //     $table->dropColumn('type_subtest');
        // });

        // Schema::table('tryout_details', function (Blueprint $table) {
        //     $table->enum('type_subtest', [
        //         'twk', 'tiu', 'tkp', 'general',
        //         'writing', 'reading', 'listening'
        //     ])->after('tryout_id');
        // });
    }

    public function down()
    {
        // Schema::table('tryout_details', function (Blueprint $table) {
        //     $table->dropColumn('type_subtest');
        // });

        // Schema::table('tryout_details', function (Blueprint $table) {
        //     $table->enum('type_subtest', ['twk', 'tiu', 'tkp', 'general'])->after('tryout_id');
        // });
    }
};