<?php

return [
    'prod_admin' => [
        'username' => env('PROD_ADMIN_USERNAME', 'prod_admin'),
        'email' => env('PROD_ADMIN_EMAIL', 'admin@copoit.com'),
        'password' => env('PROD_ADMIN_PASSWORD', 'Passw0rd'),
    ],
    'super_admin' => [
        'username' => env('SUPER_ADMIN_USERNAME', 'super_admin'),
        'email' => env('SUPER_ADMIN_EMAIL', 'superadmin@copoit.com'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'Passw0rd'),
    ],
    'prod_user' => [
        'username' => env('PROD_USER_USERNAME', 'prod_user'),
        'email' => env('PROD_USER_EMAIL', 'user@copoit.com'),
        'password' => env('PROD_USER_PASSWORD', 'Passw0rd'),
    ],
];
