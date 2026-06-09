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
        'interactive_qris' => [
            'label' => 'InterActive QRIS',
            'type' => 'qris',
            'handler' => \App\Services\Payments\InteractiveQrisGateway::class,
            'base_url' => env('INTERACTIVE_QRIS_BASE_URL', 'https://qris.interactive.co.id/restapi/qris'),
        ],
    ],
];
