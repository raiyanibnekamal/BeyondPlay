<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

/**
 * Demo user register → login → feature smoke test (simulates frontend origin).
 * Run: php tests/demo_user_flow_test.php
 */

$base = 'http://127.0.0.1:8000/api/v1';
$origin = 'http://127.0.0.1:5500';
$passed = 0;
$failed = 0;
$issues = [];
$demo = [
    'username' => 'DemoPlayer' . random_int(1000, 9999),
    'email' => 'demo' . random_int(100000, 999999) . '@arena.test',
    'password' => 'DemoPass123!',
];

function api(string $method, string $path, ?array $body = null, ?string $token = null, bool $withCors = true): array
{
    global $base, $origin;
    $url = $base . $path;
    if ($method === 'GET' && $body) {
        $url .= '?' . http_build_query($body);
        $body = null;
    }
    $ch = curl_init($url);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($withCors) {
        $headers[] = 'Origin: ' . $origin;
    }
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_HEADER => true,
        CURLOPT_POSTFIELDS => $body ? json_encode($body) : null,
    ]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $headerStr = substr($raw, 0, $headerSize);
    $bodyStr = substr($raw, $headerSize);
    $corsOk = stripos($headerStr, 'Access-Control-Allow-Origin') !== false;

    return [
        'code' => $code,
        'body' => json_decode($bodyStr ?: '{}', true) ?: [],
        'cors' => $corsOk,
        'raw' => $bodyStr,
    ];
}

function ok(bool $cond, string $label, string $detail = ''): void
{
    global $passed, $failed, $issues;
    if ($cond) {
        echo "  OK: {$label}\n";
        $passed++;
    } else {
        echo "  FAIL: {$label}" . ($detail ? " — {$detail}" : '') . "\n";
        $failed++;
        $issues[] = $label . ($detail ? ": {$detail}" : '');
    }
}

echo "=== Arena Demo User Flow Test ===\n";
echo "Demo account: {$demo['email']} / {$demo['password']}\n\n";

echo "## Register\n";
$reg = api('POST', '/auth/register', [
    'username' => $demo['username'],
    'email' => $demo['email'],
    'password' => $demo['password'],
    'password_confirmation' => $demo['password'],
]);
ok($reg['code'] === 201, 'POST /auth/register', "HTTP {$reg['code']} " . substr($reg['raw'], 0, 200));
ok($reg['cors'], 'CORS on register');
$token = $reg['body']['token'] ?? null;
ok($token !== null, 'register returns token');

echo "\n## Login (same credentials)\n";
$login = api('POST', '/auth/login', [
    'email' => $demo['email'],
    'password' => $demo['password'],
]);
ok($login['code'] === 200, 'POST /auth/login', "HTTP {$login['code']}");
$token = $login['body']['token'] ?? $token;
ok($token !== null, 'login returns token');

echo "\n## Authenticated user endpoints\n";
$me = api('GET', '/auth/me', null, $token);
ok($me['code'] === 200, 'GET /auth/me', "HTTP {$me['code']}");

$stats = api('GET', '/user/stats', null, $token);
ok($stats['code'] === 200, 'GET /user/stats', "HTTP {$stats['code']}");

$tournaments = api('GET', '/user/tournaments', null, $token);
ok($tournaments['code'] === 200, 'GET /user/tournaments', "HTTP {$tournaments['code']}");

$orders = api('GET', '/user/orders', null, $token);
ok($orders['code'] === 200, 'GET /user/orders', "HTTP {$orders['code']}");

$notifs = api('GET', '/user/notifications', null, $token);
ok($notifs['code'] === 200, 'GET /user/notifications', "HTTP {$notifs['code']}");

$friends = api('GET', '/friends', null, $token);
ok($friends['code'] === 200, 'GET /friends', "HTTP {$friends['code']}");

$achievements = api('GET', '/user/achievements', null, $token);
ok($achievements['code'] === 200, 'GET /user/achievements', "HTTP {$achievements['code']}");

$activity = api('GET', '/user/activity-feed', null, $token);
ok($activity['code'] === 200, 'GET /user/activity-feed', "HTTP {$activity['code']}");

echo "\n## Public + actions\n";
$tList = api('GET', '/tournaments');
ok($tList['code'] === 200, 'GET /tournaments');

$products = api('GET', '/products');
$prodList = $products['body']['data'] ?? [];
ok($products['code'] === 200 && count($prodList) > 0, 'GET /products');

$games = api('GET', '/games');
ok($games['code'] === 200, 'GET /games');

$blog = api('GET', '/posts');
ok($blog['code'] === 200, 'GET /posts (blog)');

if (! empty($prodList[0]['id'])) {
    $wish = api('POST', '/wishlist/' . $prodList[0]['id'], null, $token);
    ok(in_array($wish['code'], [200, 201], true), 'POST /wishlist/{id}', "HTTP {$wish['code']}");
}

$suggest = api('POST', '/games/suggest', [
    'name' => 'Demo Game ' . random_int(1, 99),
    'platform' => 'PC',
    'reason' => 'E2E test suggestion with enough characters for validation.',
], $token);
ok(in_array($suggest['code'], [200, 201], true), 'POST /games/suggest', "HTTP {$suggest['code']}");

$openList = api('GET', '/tournaments', ['status' => 'open']);
$openT = ($openList['body']['data'] ?? [])[0] ?? null;
if ($openT) {
    $tReg = api('POST', '/tournaments/' . $openT['id'] . '/register', [], $token);
    ok(in_array($tReg['code'], [200, 201, 422], true), 'POST tournament register', "HTTP {$tReg['code']}");
} else {
    ok(true, 'tournament register skipped (no open tournament)');
}

echo "\n## Summary\n";
echo "Passed: {$passed}, Failed: {$failed}\n";
echo "\n--- DEMO CREDENTIALS (save these) ---\n";
echo "Email:    {$demo['email']}\n";
echo "Username: {$demo['username']}\n";
echo "Password: {$demo['password']}\n";

if ($issues) {
    echo "\n--- ISSUES ---\n";
    foreach ($issues as $i) {
        echo "- {$i}\n";
    }
}

exit($failed > 0 ? 1 : 0);
