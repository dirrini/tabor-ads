<?php

return [
    'base_url' => env('MERCADO_PAGO_BASE_URL', 'https://api.mercadopago.com'),
    'public_key' => env('MERCADO_PAGO_PUBLIC_KEY'),
    'access_token' => env('MERCADO_PAGO_ACCESS_TOKEN'),
    'webhook_secret' => env('MERCADO_PAGO_WEBHOOK_SECRET'),
    'currency' => env('MERCADO_PAGO_CURRENCY', 'BRL'),
    'notification_url' => env('MERCADO_PAGO_NOTIFICATION_URL'),
    'premium_plans' => [
        'monthly' => [
            'label' => 'Mensal',
            'amount' => (float) env('MERCADO_PAGO_PREMIUM_MONTHLY_AMOUNT', 1.90),
            'duration_months' => 1,
            'max_installments' => 1,
        ],
        'annual' => [
            'label' => 'Anual',
            'amount' => (float) env('MERCADO_PAGO_PREMIUM_ANNUAL_AMOUNT', 9.90),
            'duration_months' => 12,
            'max_installments' => 1,
        ],
    ],
];
