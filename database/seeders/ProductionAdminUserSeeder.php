<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ProductionAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Running ProductionAdminUserSeeder...');
        $this->command->info(sprintf(
            'DB connection: %s (%s)',
            config('database.default'),
            config('database.connections.' . config('database.default') . '.database')
        ));

        $adminEmail = config('seeders.prod_admin.email');
        $userEmail = config('seeders.prod_user.email');

        if (empty($adminEmail) || empty($userEmail)) {
            $this->command->error('Production seeder aborted: PROD_ADMIN_EMAIL/PROD_USER_EMAIL must be set (use config cache safe values).');
            return;
        }

        $accounts = [
            [
                'name' => 'Production Admin',
                'username' => config('seeders.prod_admin.username'),
                'email' => $adminEmail,
                'password' => config('seeders.prod_admin.password'),
                'role' => 'admin',
            ],
            [
                'name' => 'Production User',
                'username' => config('seeders.prod_user.username'),
                'email' => $userEmail,
                'password' => config('seeders.prod_user.password'),
                'role' => 'user',
            ],
        ];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
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

            $this->command->info(sprintf(
                '%s user for %s',
                $user->wasRecentlyCreated ? 'Created' : 'Updated',
                $account['email']
            ));
        }
    }
}
