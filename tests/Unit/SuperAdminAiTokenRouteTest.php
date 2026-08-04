<?php

namespace Tests\Unit;

use Illuminate\Routing\Router;
use Tests\TestCase;

class SuperAdminAiTokenRouteTest extends TestCase
{
    public function test_token_adjustment_route_is_only_registered_for_super_admin(): void
    {
        $routes = app(Router::class)->getRoutes();
        $route = $routes->getByName('super-admin.ai-gateway-subscriptions.tokens.store');

        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('super-admin', $route->gatherMiddleware());
        $this->assertNull($routes->getByName('admin.user.ai-tokens.store'));
    }
}
