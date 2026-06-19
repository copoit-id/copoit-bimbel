<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            if (!Schema::hasColumn('client_profile', 'ai_question_generator_settings')) {
                $table->longText('ai_question_generator_settings')
                    ->nullable()
                    ->after('footer_youtube');
            }
        });

        \App\Models\ClientProfile::query()->get()->each(function ($profile) {
            $profile->ai_question_generator_settings = [
                'default_model' => 'gemini-2.5-flash',
                'providers' => [
                    'openai' => [
                        'api_key' => null,
                        'base_url' => 'https://api.openai.com/v1',
                        'timeout' => 90,
                    ],
                    'gemini' => [
                        'api_key' => null,
                        'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                        'timeout' => 90,
                    ],
                ],
                'models' => [
                    [
                        'id' => 'gpt-5.4-mini',
                        'label' => 'OpenAI - GPT-5.4 Mini',
                        'provider' => 'openai',
                        'enabled' => true,
                    ],
                    [
                        'id' => 'gemini-2.5-flash',
                        'label' => 'Gemini - 2.5 Flash',
                        'provider' => 'gemini',
                        'enabled' => true,
                    ],
                    [
                        'id' => 'gemini-2.5-flash-lite',
                        'label' => 'Gemini - 2.5 Flash-Lite',
                        'provider' => 'gemini',
                        'enabled' => true,
                    ],
                ],
            ];
            $profile->save();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            if (Schema::hasColumn('client_profile', 'ai_question_generator_settings')) {
                $table->dropColumn('ai_question_generator_settings');
            }
        });
    }
};
