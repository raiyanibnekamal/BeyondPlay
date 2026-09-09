<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

/**
 * STEP 20 smoke test: banners, sponsors, bulk email, analytics.
 * Run: php tests/step20_final_test.php
 */

$base = 'http://127.0.0.1:8000/api/v1';

function api(string $method, string $path, ?array $body = null, ?string $token = null, ?string $page = null): array
{
    global $base;
    $url = $base.$path;
    if ($method === 'GET' && $body) {
        $url .= '?'.http_build_query($body);
        $body = null;
    }
    $ch = curl_init($url);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($page) {
        $headers[] = 'X-Arena-Page: '.$page;
    }
    if ($token) {
        $headers[] = 'Authorization: Bearer '.$token;
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body ? json_encode($body) : null,
    ]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $code, 'body' => json_decode($raw ?: '{}', true) ?: []];
}

echo "=== STEP 20 Admin Extras + Polish ===\n";

$admin = api('POST', '/auth/login', ['email' => 'admin@arena.gg', 'password' => 'password']);
$token = $admin['body']['token'] ?? null;
if (! $token) {
    echo "FAIL: admin login\n";
    exit(1);
}
echo "OK: admin login\n";

$banners = api('GET', '/banners/active', null, null, 'index.html');
if ($banners['code'] !== 200 || empty($banners['body']['banners'])) {
    echo "FAIL: active banners (run db:seed)\n";
    print_r($banners);
    exit(1);
}
echo "OK: GET /banners/active\n";

$sponsors = api('GET', '/sponsors');
if ($sponsors['code'] !== 200 || empty($sponsors['body']['sponsors'])) {
    echo "FAIL: sponsors list\n";
    exit(1);
}
echo "OK: GET /sponsors\n";

$create = api('POST', '/admin/banners', [
    'title' => 'Test Banner',
    'message' => 'API test announcement',
    'link' => 'tournament.html',
    'type' => 'info',
    'is_active' => true,
], $token);

if ($create['code'] !== 201) {
    echo "FAIL: create banner\n";
    print_r($create);
    exit(1);
}
echo "OK: admin create banner\n";

$bulk = api('POST', '/admin/bulk-email', [
    'subject' => 'Arena Test',
    'body' => 'This is a queued bulk email test.',
    'audience' => 'active',
], $token);

if ($bulk['code'] !== 200 || empty($bulk['body']['recipient_count'])) {
    echo "FAIL: bulk email\n";
    print_r($bulk);
    exit(1);
}
echo "OK: bulk email queued for ".$bulk['body']['recipient_count']." users\n";

$analytics = api('GET', '/admin/analytics/overview', null, $token, 'admin/analytics.html');
if ($analytics['code'] !== 200 || ! isset($analytics['body']['totals'])) {
    echo "FAIL: analytics overview\n";
    exit(1);
}
echo "OK: analytics overview (users: ".$analytics['body']['totals']['users'].")\n";

$contact = api('POST', '/contact', [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'subject' => 'Hello',
    'message' => 'This is a test contact message for Arena platform.',
], null, 'contact.html');

if ($contact['code'] !== 201) {
    echo "FAIL: contact form\n";
    exit(1);
}
echo "OK: contact message submitted\n";

echo "\n=== All STEP 20 checks passed ===\n";
echo "Run: php artisan arena:weekly-digest\n";
echo "Run: php artisan queue:work (for bulk email job)\n";
