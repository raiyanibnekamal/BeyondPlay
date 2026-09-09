<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HttpOnly auth cookie (first-party SPA)
    |--------------------------------------------------------------------------
    |
    | When true, login/register set an HttpOnly cookie and InjectBearerFromCookie
    | middleware supplies the Sanctum token. Requires SESSION_DOMAIN / HTTPS.
    |
    */

    'httponly_auth_cookie' => env('ARENA_HTTPONLY_AUTH_COOKIE', false),

    'auth_cookie_name' => env('ARENA_AUTH_COOKIE_NAME', 'arena_auth'),

    /*
    | Stripe (optional — when set, shop orders and entry fees require payment)
    */
    'stripe_key' => env('STRIPE_KEY'),
    'stripe_secret' => env('STRIPE_SECRET'),
    'stripe_currency' => env('STRIPE_CURRENCY', 'usd'),

    /*
    | Require verified email for sensitive actions (register still works; login warns)
    */
    'require_email_verification' => env('ARENA_REQUIRE_EMAIL_VERIFICATION', false),

];
