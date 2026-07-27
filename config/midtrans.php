<?php

declare(strict_types=1);

return [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'is_production' => env('MIDTRANS_PRODUCTION', false),
    'is_sanitized' => env('MIDTRANS_SANITIZED', true),
    'is_3ds' => env('MIDTRANS_3DS', true),
    'verify_terminal_status' => env('MIDTRANS_VERIFY_TERMINAL_STATUS', true),
    'enabled_payments' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MIDTRANS_ENABLED_PAYMENTS', 'credit_card'))
    ))),
];
