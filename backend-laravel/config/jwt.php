<?php

declare(strict_types=1);

return [
    'secret' => env('JWT_SECRET'),
    'ttl' => (int) env('JWT_EXPIRES_IN', 7 * 24 * 3600),
];

