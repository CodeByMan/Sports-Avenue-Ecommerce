<?php

declare(strict_types=1);

return [
    'csp_report_only' => (bool) env('SECURITY_CSP_REPORT_ONLY', false),
    'content_security_policy' => [
        "default-src 'self'",
        "base-uri 'self'",
        "object-src 'none'",
        "frame-ancestors 'self'",
        "form-action 'self'",
        "img-src 'self' data: blob: https:",
        "font-src 'self' data: https:",
        "style-src 'self' 'unsafe-inline' https:",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://app.sandbox.midtrans.com https://app.midtrans.com",
        "connect-src 'self' https: wss:",
        'frame-src https://app.sandbox.midtrans.com https://app.midtrans.com',
        "media-src 'self' blob:",
        "worker-src 'self' blob:",
        'upgrade-insecure-requests',
    ],
];
