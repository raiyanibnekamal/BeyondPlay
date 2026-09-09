<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary
/**
 * PHP built-in server router — security headers for static Arena frontend.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header(
    "Content-Security-Policy: default-src 'self'; script-src 'self' https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https:; connect-src 'self' http://127.0.0.1:8000 https:; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
);

$file = __DIR__.$uri;
if ($uri !== '/' && is_file($file)) {
    return false;
}

if (is_file(__DIR__.'/index.html')) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile(__DIR__.'/index.html');

    return true;
}

http_response_code(404);
echo 'Not found';

return true;
