<?php

return [
    'default' => env('PAYMENT_GATEWAY', 'xendit'),

    'gateways' => [
        'xendit' => [
            'label' => 'Xendit',
            'type' => 'redirect',
        ],
        'midtrans' => [
            'label' => 'Midtrans',
            'type' => 'redirect',
        ],
        'ipaymu' => [
            'label' => 'iPaymu',
            'type' => 'redirect',
            'handler' => \App\Services\Payments\IpaymuGateway::class,
            'sandbox_url' => env('IPAYMU_SANDBOX_URL', 'https://sandbox.ipaymu.com/api/v2'),
            'production_url' => env('IPAYMU_PRODUCTION_URL', 'https://api.ipaymu.com/api/v2'),
        ],
        'interactive_qris' => [
            'label' => 'InterActive QRIS',
            'type' => 'qris',
            'handler' => \App\Services\Payments\InteractiveQrisGateway::class,
            'base_url' => env('INTERACTIVE_QRIS_BASE_URL', 'https://qris.interactive.co.id/restapi/qris'),
        ],
    ],
];
