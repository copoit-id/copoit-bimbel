<?php

namespace Tests\Unit;

use App\Models\User;
use Laravel\Sanctum\HasApiTokens;
use PHPUnit\Framework\TestCase;

class SanctumRuntimeTest extends TestCase
{
    public function test_user_token_support_is_available_at_runtime(): void
    {
        $this->assertTrue(trait_exists(HasApiTokens::class));
        $this->assertContains(HasApiTokens::class, class_uses_recursive(User::class));
        $this->assertTrue(method_exists(User::class, 'createToken'));
    }
}
