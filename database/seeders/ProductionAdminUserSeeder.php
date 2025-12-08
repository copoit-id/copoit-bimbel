<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Production Admin',
                'username' => env('PROD_ADMIN_USERNAME', 'prod_admin'),
                'email' => env('PROD_ADMIN_EMAIL', 'admin@copoit.com'),
                'password' => env('PROD_ADMIN_PASSWORD', 'Passw0rd'),
                'role' => 'admin',
            ],
            [
                'name' => 'Production User',
                'username' => env('PROD_USER_USERNAME', 'prod_user'),
                'email' => env('PROD_USER_EMAIL', 'user@copoit.com'),
                'password' => env('PROD_USER_PASSWORD', 'Passw0rd'),
                'role' => 'user',
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'username' => $account['username'],
                    'password' => Hash::make($account['password']),
                    'role' => $account['role'],
                    'status' => 'aktif',
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
