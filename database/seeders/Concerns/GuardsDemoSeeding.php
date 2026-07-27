<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use LogicException;

trait GuardsDemoSeeding
{
    protected function ensureDemoSeedingIsAllowed(): void
    {
        if (app()->environment('production') && ! config('demo.allow_seeding', false)) {
            throw new LogicException(
                'Demo seeding is disabled in production. Set ALLOW_DEMO_SEEDING=true only for a disposable demonstration environment.',
            );
        }
    }
}
