<?php

namespace Tests\Unit;

use App\Services\AiGatewayPaymentService;
use ReflectionMethod;
use Tests\TestCase;

class AiGatewayPaymentServiceStatusTest extends TestCase
{
    public function test_successful_status_check_does_not_mark_pending_ipaymu_transaction_as_paid(): void
    {
        $status = $this->statusFrom([
            'Status' => 200,
            'Message' => 'Success',
            'Data' => [
                'TransactionId' => 12345,
                'Status' => 0,
                'StatusDesc' => 'Pending',
            ],
        ]);

        $this->assertSame('pending', $status);
    }

    public function test_ipaymu_transaction_status_one_is_paid(): void
    {
        $status = $this->statusFrom([
            'Status' => 200,
            'Message' => 'Success',
            'Data' => [
                'TransactionId' => 12345,
                'Status' => 1,
                'StatusDesc' => 'Berhasil',
            ],
        ]);

        $this->assertSame('paid', $status);
    }

    public function test_api_success_without_transaction_data_is_not_payment_confirmation(): void
    {
        $status = $this->statusFrom([
            'Status' => 'success',
            'Message' => 'Request processed successfully',
        ]);

        $this->assertNull($status);
    }

    public function test_ipaymu_cancelled_transaction_is_failed(): void
    {
        $status = $this->statusFrom([
            'Status' => 200,
            'Data' => ['Status' => 2, 'StatusDesc' => 'Batal'],
        ]);

        $this->assertSame('failed', $status);
    }

    private function statusFrom(array $payload): ?string
    {
        $method = new ReflectionMethod(AiGatewayPaymentService::class, 'ipaymuTransactionStatus');

        return $method->invoke(app(AiGatewayPaymentService::class), $payload);
    }
}
