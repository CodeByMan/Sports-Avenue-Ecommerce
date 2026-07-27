<?php

declare(strict_types=1);

return [
    'allow_seeding' => (bool) env('ALLOW_DEMO_SEEDING', false),
    'password' => env('DEMO_USER_PASSWORD', 'LocalDemoOnly!2026'),
];
