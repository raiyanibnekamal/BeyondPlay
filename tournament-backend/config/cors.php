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

// Production: allow Vercel domains and configured frontend. Local dev: allow default frontend ports.
if ($parts === []) {
    $parts = [
        'http://127.0.0.1:5500',
        'http://localhost:5500',
        'http://127.0.0.1:3000',
        'http://localhost:3000',
        'https://beyond-play-tournament-backend.vercel.app',
    ];
}

$allowedOrigins = $parts;
$supportsCredentials = true;

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [
        '#^https?://.*\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => $supportsCredentials,

];
