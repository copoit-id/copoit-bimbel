<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DemoRequestRouteTest extends TestCase
{
    public function test_public_demo_request_routes_and_super_admin_review_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('demo-requests.create'));
        $this->assertTrue(Route::has('demo-requests.store'));
        $this->assertTrue(Route::has('super-admin.admins.requests.approve'));
        $this->assertTrue(Route::has('super-admin.admins.requests.reject'));

        $this->assertSame(['GET', 'HEAD'], Route::getRoutes()->getByName('demo-requests.create')->methods());
        $this->assertSame(['POST'], Route::getRoutes()->getByName('demo-requests.store')->methods());
        $this->assertSame(['DELETE'], Route::getRoutes()->getByName('super-admin.admins.requests.reject')->methods());
    }
}
