<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DeploymentSuperAdminController extends Controller
{
    public function __invoke(string $token): Response
    {
        $configuredToken = (string) config('app.deploy_migration_token');

        abort_unless(
            (bool) config('app.deploy_migration_enabled')
            && $configuredToken !== ''
            && hash_equals($configuredToken, $token),
            404,
        );

        $username = trim((string) config('seeders.super_admin.username'));
        $email = trim((string) config('seeders.super_admin.email'));
        $password = (string) config('seeders.super_admin.password');

        abort_if(
            $username === ''
            || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || $password === 'Passw0rd'
            || mb_strlen($password) < 16,
            500,
            'Konfigurasi SUPER_ADMIN_* tidak valid.',
        );

        try {
            $user = DB::transaction(function () use ($username, $email, $password): User {
                $usernameTaken = User::query()
                    ->where('username', $username)
                    ->where('email', '!=', $email)
                    ->exists();

                abort_if($usernameTaken, 500, 'Username super admin sudah digunakan akun lain.');

                $role = Role::query()->where('slug', 'super_admin')->firstOrFail();

                $user = User::query()->updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => 'Super Admin',
                        'username' => $username,
                        'password' => Hash::make($password),
                        'role' => 'super_admin',
                        'status' => 'aktif',
                        'email_verified_at' => now(),
                    ],
                );

                $user->roles()->syncWithoutDetaching([$role->id]);

                return $user;
            });
        } catch (\Throwable $exception) {
            report($exception);
            Log::error('Deployment super admin bootstrap failed.');

            abort(500, 'Pembuatan super admin gagal. Periksa laravel.log di hosting.');
        }

        return response('Super admin siap: '.$user->email, 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
