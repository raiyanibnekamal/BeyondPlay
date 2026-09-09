<?php

$origins = env('CORS_ALLOWED_ORIGINS');
$frontend = env('FRONTEND_URL');

$parts = [];
if ($origins !== null && $origins !== '' && $origins !== '*') {
    $parts = array_merge($parts, array_map('trim', explode(',', $origins)));
}
if ($frontend) {
    $parts[] = trim($frontend);
}
$parts = array_values(array_unique(array_filter($parts)));

// Production: deny cross-origin when env vars are unset. Local dev: allow default frontend ports.
if ($parts === [] && env('APP_ENV', 'production') === 'local') {
    $parts = [
        'http://127.0.0.1:5500',
        'http://localhost:5500',
        'http://127.0.0.1:3000',
        'http://localhost:3000',
    ];
}

$allowedOrigins = $parts;
$supportsCredentials = $parts !== [];

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => $supportsCredentials,

];
