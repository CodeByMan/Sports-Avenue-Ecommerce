<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Legacy shipping compatibility
|--------------------------------------------------------------------------
|
| Older installations may still reference this configuration key. The
| application now uses the local Pakistan shipping configuration instead.
|
*/
return [
    'origin_provider_id' => (int) env('SHIPPING_ORIGIN_PROVIDER_ID', 101),
];
