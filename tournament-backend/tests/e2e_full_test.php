<?php

/**
 * Full end-to-end API smoke test for Arena platform.
 * Run: php tests/e2e_full_test.php
 * Requires: php artisan serve, php artisan db:seed, php artisan queue:work (optional)
 */

$base = 'http://127.0.0.1:8000/api/v1';
$passed = 0;
$failed = 0;

function api(string $method, string $path, ?array $body = null, ?string $token = null): array
{
    global $base;
    $url = $base.$path;
    if ($method === 'GET' && $body) {
        $url .= '?'.http_build_query($body);
        $body = null;
    }
    $ch = curl_init($url);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
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

function ok(bool $cond, string $label): void
{
    global $passed, $failed;
    if ($cond) {
        echo "  OK: {$label}\n";
        $passed++;
    } else {
        echo "  FAIL: {$label}\n";
        $failed++;
    }
}

echo "=== Arena Full E2E API Test ===\n\n";

// --- Flow 1: User journey ---
echo "## 1. Register → login → tournament → shop\n";

$email = 'e2e'.random_int(100000, 999999).'@arena.test';
$reg = api('POST', '/auth/register', [
    'username' => 'E2EPlayer'.random_int(100, 999),
    'email' => $email,
    'password' => 'Arena@2026!',
    'password_confirmation' => 'Arena@2026!',
]);
ok($reg['code'] === 201 && ! empty($reg['body']['token']), 'register new user');
$userToken = $reg['body']['token'] ?? null;

$login = api('POST', '/auth/login', ['email' => 'player@arena.gg', 'password' => 'password']);
ok($login['code'] === 200, 'login existing player');
$playerToken = $login['body']['token'] ?? $userToken;

$tournaments = api('GET', '/tournaments');
$items = $tournaments['body']['data'] ?? $tournaments['body'] ?? [];
$tournament = $items[0] ?? null;
ok($tournament !== null, 'list tournaments');

if ($tournament) {
    $tid = $tournament['id'];
    $open = api('GET', '/tournaments', ['status' => 'open']);
    $openList = $open['body']['data'] ?? [];
    $target = null;
    foreach ($openList as $t) {
        if ($t['status'] === 'open') {
            $target = $t;
            break;
        }
    }
    if ($target) {
        $regT = api('POST', '/tournaments/'.$target['id'].'/register', [], $playerToken);
        ok(in_array($regT['code'], [200, 201, 422], true), 'register for open tournament (or already registered)');
    } else {
        ok(true, 'register skipped (no open tournament)');
    }
}

$stats = api('GET', '/user/stats', null, $playerToken);
ok($stats['code'] === 200, 'user stats');

$product = api('GET', '/products');
$prodList = $product['body']['data'] ?? [];
ok(count($prodList) > 0, 'list products');
if (! empty($prodList[0])) {
    $pid = $prodList[0]['id'];
    $order = api('POST', '/orders', [
        'items' => [['product_id' => $pid, 'quantity' => 1]],
        'payment_method' => 'test',
        'shipping_name' => 'E2E',
        'shipping_address' => '1 Test St',
        'shipping_city' => 'City',
        'shipping_phone' => '000',
    ], $playerToken);
    ok($order['code'] === 201, 'create shop order');
}

$wish = api('POST', '/wishlist/'.($prodList[0]['id'] ?? 1), null, $playerToken);
ok(in_array($wish['code'], [200, 201], true), 'add wishlist');

// --- Flow 2: Admin journey ---
echo "\n## 2. Admin: tournament, bracket, score, game suggestion, bulk email\n";

$admin = api('POST', '/auth/login', ['email' => 'admin@arena.gg', 'password' => 'password']);
ok($admin['code'] === 200, 'admin login');
$adminToken = $admin['body']['token'] ?? null;

$future = date('Y-m-d H:i:s', strtotime('+30 days'));
$create = api('POST', '/admin/tournaments', [
    'game_id' => 1,
    'name' => 'E2E Cup '.random_int(10, 99),
    'status' => 'open',
    'start_date' => $future,
    'max_participants' => 8,
    'format' => 'single_elimination',
], $adminToken);
ok($create['code'] === 201, 'admin create tournament');
$e2eTid = $create['body']['tournament']['id'] ?? null;

if ($e2eTid) {
    $team1 = api('POST', '/teams', ['name' => 'E2E A '.random_int(1, 9)], $playerToken);
    $team2 = api('POST', '/teams', ['name' => 'E2E B '.random_int(1, 9)], $adminToken);
    $t1 = $team1['body']['team']['id'] ?? null;
    $t2 = $team2['body']['team']['id'] ?? null;
    if ($t1 && $t2) {
        api('POST', "/tournaments/{$e2eTid}/register", ['team_id' => $t1], $playerToken);
        api('POST', "/tournaments/{$e2eTid}/register", ['team_id' => $t2], $adminToken);
        $bracket = api('POST', "/admin/tournaments/{$e2eTid}/generate-bracket", null, $adminToken);
        ok($bracket['code'] === 200, 'generate bracket');
        $matches = api('GET', "/tournaments/{$e2eTid}/matches");
        $m = $matches['body'][0] ?? null;
        if ($m && $m['team1_id']) {
            $score = api('PUT', '/admin/matches/'.$m['id'].'/score', [
                'team1_score' => 2,
                'team2_score' => 0,
                'status' => 'completed',
                'winner_id' => $m['team1_id'],
            ], $adminToken);
            ok($score['code'] === 200, 'enter match score');
            $playerStats = api('GET', '/stats/player/1');
            ok($playerStats['code'] === 200, 'player stats after match');
        }
    }
}

$suggest = api('POST', '/games/suggest', [
    'name' => 'E2E Game '.random_int(1, 9),
    'genre' => 'FPS',
    'description' => 'End to end test game suggestion.',
], $playerToken);
ok($suggest['code'] === 201, 'submit game suggestion');
$sugId = $suggest['body']['suggestion']['id'] ?? null;
if ($sugId) {
    $approve = api('PUT', "/admin/game-suggestions/{$sugId}/approve", null, $adminToken);
    ok($approve['code'] === 200, 'approve game suggestion');
    $notifs = api('GET', '/user/notifications', null, $playerToken);
    $types = array_column($notifs['body']['data'] ?? [], 'type');
    ok(in_array('game_suggestion_approved', $types, true), 'notification after approve');
}

$bulk = api('POST', '/admin/bulk-email', [
    'subject' => 'E2E Test',
    'body' => 'Bulk email test body',
    'audience' => 'active',
], $adminToken);
ok($bulk['code'] === 200 && ($bulk['body']['recipient_count'] ?? 0) > 0, 'bulk email queue');

$prod = api('POST', '/admin/products', [
    'name' => 'E2E Product',
    'price' => 49.99,
    'stock' => 10,
    'type' => 'physical',
    'status' => 'active',
], $adminToken);
ok($prod['code'] === 201, 'admin create product');

// --- Flow 3: Public extras ---
echo "\n## 3. Banners, sponsors, contact\n";
ok(api('GET', '/banners/active')['code'] === 200, 'active banners');
ok(api('GET', '/sponsors')['code'] === 200, 'sponsors');
ok(api('POST', '/contact', [
    'name' => 'E2E',
    'email' => 'e2e@test.com',
    'subject' => 'Hi',
    'message' => 'End to end contact message for Arena platform testing.',
])['code'] === 201, 'contact form');

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
