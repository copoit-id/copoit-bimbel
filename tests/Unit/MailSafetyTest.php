<?php

namespace Tests\Unit;

use App\Support\MailSafety;
use PHPUnit\Framework\TestCase;

class MailSafetyTest extends TestCase
{
    public function test_it_rejects_crlf_in_email_addresses(): void
    {
        $email = "member@example.com\r\nBcc: attacker@example.com";

        $this->assertNull(MailSafety::email($email));
    }

    public function test_it_removes_control_characters_from_email_headers(): void
    {
        $header = MailSafety::header("Bimbel\r\nBcc: attacker@example.com", 'Copoit Academy');

        $this->assertSame('Bimbel Bcc: attacker@example.com', $header);
        $this->assertStringNotContainsString("\r", $header);
        $this->assertStringNotContainsString("\n", $header);
    }
}
