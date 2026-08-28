<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DemoRequestRouteTest extends TestCase
{
    public function test_public_demo_request_routes_and_super_admin_approval_route_are_registered(): void
    {
        $this->assertTrue(Route::has('demo-requests.create'));
        $this->assertTrue(Route::has('demo-requests.store'));
        $this->assertTrue(Route::has('super-admin.admins.requests.approve'));

        $this->assertSame(['GET', 'HEAD'], Route::getRoutes()->getByName('demo-requests.create')->methods());
        $this->assertSame(['POST'], Route::getRoutes()->getByName('demo-requests.store')->methods());
    }
}
