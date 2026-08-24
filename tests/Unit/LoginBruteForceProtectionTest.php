<?php

namespace Tests\Unit;

use App\Http\Controllers\AuthController;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class LoginBruteForceProtectionTest extends TestCase
{
    public function test_login_rate_limiter_includes_a_global_account_guard(): void
    {
        $request = Request::create('/login', 'POST', ['email' => ' Peserta@Example.test ']);
        $request->server->set('REMOTE_ADDR', '203.0.113.10');

        $limits = app(RateLimiter::class)->limiter('login')($request);

        $this->assertCount(3, $limits);
        $this->assertSame(12, $limits[2]->maxAttempts);
        $this->assertSame(
            'login-account-global|'.hash('sha256', 'peserta@example.test'),
            $limits[2]->key
        );
    }

    public function test_failed_login_counter_uses_the_same_key_when_an_ip_changes(): void
    {
        $firstRequest = Request::create('/login', 'POST', ['email' => 'Peserta@Example.test']);
        $firstRequest->server->set('REMOTE_ADDR', '203.0.113.10');
        $secondRequest = Request::create('/login', 'POST', ['email' => ' peserta@example.test ']);
        $secondRequest->server->set('REMOTE_ADDR', '203.0.113.11');

        $method = new ReflectionMethod(AuthController::class, 'throttleKey');
        $controller = app(AuthController::class);

        $this->assertSame(
            $method->invoke($controller, $firstRequest),
            $method->invoke($controller, $secondRequest)
        );
    }
}
