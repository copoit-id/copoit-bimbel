<?php

use App\Http\Middleware\AdminExpiryMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\DisableBrowserCache;
use App\Http\Middleware\EnforceConcurrentLoginLimit;
use App\Http\Middleware\EnsureCertificateManagementEnabled;
use App\Http\Middleware\EnsureClientFeatureEnabled;
use App\Http\Middleware\EnsurePanelPortal;
use App\Http\Middleware\EnsurePlanFeatureEnabled;
use App\Http\Middleware\EnsureTutorContentOwnership;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetPanelUrlDefaults;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\TutorMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [
            EnforceConcurrentLoginLimit::class,
            SetPanelUrlDefaults::class,
            EnsurePlanFeatureEnabled::class,
        ]);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'admin.expiry' => AdminExpiryMiddleware::class,
            'tutor' => TutorMiddleware::class,
            'super-admin' => SuperAdminMiddleware::class,
            'certificate.enabled' => EnsureCertificateManagementEnabled::class,
            'client-feature' => EnsureClientFeatureEnabled::class,
            'permission' => CheckPermission::class,
            'panel.portal' => EnsurePanelPortal::class,
            'module' => EnsurePlanFeatureEnabled::class,
            'tutor-content-owner' => EnsureTutorContentOwnership::class,
            'no-cache' => DisableBrowserCache::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, \Throwable $exception, Request $request): Response {
            if ($response->getStatusCode() === 419 && ! $request->expectsJson()) {
                return redirect()
                    ->route('login')
                    ->with('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
            }

            return $response;
        });
    })->create();
