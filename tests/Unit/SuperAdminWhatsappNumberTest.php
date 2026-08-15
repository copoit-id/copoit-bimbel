<?php

namespace Tests\Unit;

use App\Http\Controllers\superadmin\SuperAdminController;
use ReflectionMethod;
use Tests\TestCase;

class SuperAdminWhatsappNumberTest extends TestCase
{
    public function test_whatsapp_numbers_are_normalized_to_the_wa_me_format(): void
    {
        $method = new ReflectionMethod(SuperAdminController::class, 'normalizeWhatsAppNumber');
        $controller = app(SuperAdminController::class);

        $this->assertSame('6281234567890', $method->invoke($controller, '0812-3456-7890'));
        $this->assertSame('6281234567890', $method->invoke($controller, '+62 812 3456 7890'));
        $this->assertSame('6281234567890', $method->invoke($controller, '81234567890'));
    }
}
