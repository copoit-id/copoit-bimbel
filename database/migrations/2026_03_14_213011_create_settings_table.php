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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('group')->default('general');
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        // Seed default settings
        $this->seedDefaultSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }

    /**
     * Seed default settings
     */
    private function seedDefaultSettings(): void
    {
        $settings = [
            [
                'key' => 'upgrade_banner_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'dashboard',
                'label' => 'Show Upgrade Banner',
                'description' => 'Enable or disable the upgrade banner on dashboard',
            ],
            [
                'key' => 'upgrade_banner_title',
                'value' => 'Unlock premium features',
                'type' => 'string',
                'group' => 'dashboard',
                'label' => 'Upgrade Banner Title',
                'description' => 'Title text for the upgrade banner',
            ],
            [
                'key' => 'upgrade_banner_description',
                'value' => 'Upgrade to Pro for unlimited analytics & real-time insights.',
                'type' => 'string',
                'group' => 'dashboard',
                'label' => 'Upgrade Banner Description',
                'description' => 'Description text for the upgrade banner',
            ],
            [
                'key' => 'upgrade_banner_button_text',
                'value' => 'Upgrade Now',
                'type' => 'string',
                'group' => 'dashboard',
                'label' => 'Upgrade Banner Button Text',
                'description' => 'Button text for the upgrade banner',
            ],
            [
                'key' => 'upgrade_banner_button_url',
                'value' => '#',
                'type' => 'string',
                'group' => 'dashboard',
                'label' => 'Upgrade Banner Button URL',
                'description' => 'Button URL for the upgrade banner',
            ],
        ];

        foreach ($settings as $setting) {
            \DB::table('settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
