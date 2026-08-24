<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Upgrade Banner Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the upgrade banner displayed on admin dashboard
    |
    */
    'upgrade_banner_enabled' => env('UPGRADE_BANNER_ENABLED', true),
    'upgrade_banner_title' => env('UPGRADE_BANNER_TITLE', 'Unlock premium features'),
    'upgrade_banner_description' => env('UPGRADE_BANNER_DESCRIPTION', 'Upgrade to Pro for unlimited analytics & real-time insights.'),
    'upgrade_banner_button_text' => env('UPGRADE_BANNER_BUTTON_TEXT', 'Upgrade Now'),
    'upgrade_banner_button_url' => env('UPGRADE_BANNER_BUTTON_URL', '#'),
    'discount_menu_enabled' => env('DISCOUNT_MENU_ENABLED', true),
    'affiliate_menu_enabled' => env('AFFILIATE_MENU_ENABLED', false),
];
