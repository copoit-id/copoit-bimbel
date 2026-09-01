<?php

namespace Tests\Unit;

use App\Services\UserPasswordResetService;
use Tests\TestCase;

class UserPasswordResetServiceTest extends TestCase
{
    public function test_it_uses_the_email_local_part_as_the_default_password(): void
    {
        $service = new UserPasswordResetService;

        $this->assertSame('contohemail', $service->defaultPasswordFor('contohemail@gmail.com'));
        $this->assertSame('ini.email', $service->defaultPasswordFor('ini.email@gmail.com'));
    }
}
