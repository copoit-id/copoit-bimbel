<?php

namespace Tests\Unit;

use App\Services\Payments\IpaymuGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class IpaymuGatewaySignatureTest extends TestCase
{
    public function test_it_signs_the_exact_json_body_sent_to_ipaymu(): void
    {
        config([
            'services.ipaymu.api_key' => 'test-api-key',
            'services.ipaymu.va' => 'test-va',
            'services.ipaymu.base_url' => 'https://ipaymu.test',
        ]);
        $payload = [
            'notifyUrl' => 'https://demo.bimbelhub.com/api/webhook/ipaymu',
            'returnUrl' => 'https://client.test/paket-ai?payment=success',
        ];
        $expectedBody = json_encode($payload, JSON_THROW_ON_ERROR);
        $expectedSignature = hash_hmac(
            'sha256',
            'POST:test-va:'.strtolower(hash('sha256', $expectedBody)).':test-api-key',
            'test-api-key'
        );
        Http::fake(['ipaymu.test/*' => Http::response(['Status' => '200'])]);

        $method = new ReflectionMethod(IpaymuGateway::class, 'post');
        $method->invoke(app(IpaymuGateway::class), '/api/v2/payment', $payload);

        Http::assertSent(function (Request $request) use ($expectedBody, $expectedSignature): bool {
            return $request->body() === $expectedBody
                && $request->header('signature')[0] === $expectedSignature;
        });
    }
}
