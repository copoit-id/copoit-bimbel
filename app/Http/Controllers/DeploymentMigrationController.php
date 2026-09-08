<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DeploymentMigrationController extends Controller
{
    public function __invoke(Request $request, string $token): Response
    {
        $configuredToken = (string) config('app.deploy_migration_token');

        abort_unless(
            (bool) config('app.deploy_migration_enabled')
            && $configuredToken !== ''
            && hash_equals($configuredToken, $token),
            404,
        );

        $exitCode = Artisan::call('migrate', ['--force' => true]);

        if ($exitCode !== 0) {
            Log::error('Deployment migration command failed.', ['exit_code' => $exitCode]);

            abort(500, 'Migration gagal. Periksa laravel.log di hosting.');
        }

        return response(Artisan::output(), 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
