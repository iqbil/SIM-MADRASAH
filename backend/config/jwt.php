<?php

return [
    'secret' => env('JWT_SECRET'),
    'issuer' => env('JWT_ISSUER', env('APP_URL', 'http://localhost')),
    'audience' => env('JWT_AUDIENCE', 'sim-madrasah-api'),
    'access_ttl' => (int) env('JWT_ACCESS_TTL', 15),
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 120),
];
