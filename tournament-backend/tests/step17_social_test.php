<?php

/**
 * Manual STEP 17 smoke test: friend request + PvP challenge + notifications.
 * Run: php tests/step17_social_test.php
 */

$base = 'http://127.0.0.1:8000/api/v1';

function api(string $method, string $path, ?array $body = null, ?string $token = null): array
{
    global $base;
    $ch = curl_init($base.$path);
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

echo "=== STEP 17 Social + Notifications ===\n";

$login1 = api('POST', '/auth/login', ['email' => 'player@arena.gg', 'password' => 'password']);
$token1 = $login1['body']['token'] ?? null;
if (! $token1) {
    echo "FAIL: player login\n";
    exit(1);
}
echo "OK: player login\n";

$reg = api('POST', '/auth/register', [
    'username' => 'TestBuddy'.random_int(1000, 9999),
    'email' => 'buddy'.random_int(10000, 99999).'@test.local',
    'password' => 'password',
    'password_confirmation' => 'password',
]);
$token2 = $reg['body']['token'] ?? null;
$user2Id = $reg['body']['user']['id'] ?? null;
if (! $token2 || ! $user2Id) {
    echo "FAIL: register second user\n";
    print_r($reg);
    exit(1);
}
echo "OK: registered buddy user #{$user2Id}\n";

$req = api('POST', "/friends/request/{$user2Id}", null, $token1);
if ($req['code'] !== 201) {
    echo "FAIL: send friend request\n";
    print_r($req);
    exit(1);
}
$requestId = $req['body']['request']['id'] ?? null;
echo "OK: friend request sent (#{$requestId})\n";

$incoming = api('GET', '/friends/requests', null, $token2);
if (empty($incoming['body']['requests'])) {
    echo "FAIL: incoming requests empty\n";
    exit(1);
}
echo "OK: buddy sees incoming request\n";

$accept = api('PUT', "/friends/request/{$requestId}/accept", null, $token2);
if ($accept['code'] !== 200) {
    echo "FAIL: accept friend\n";
    print_r($accept);
    exit(1);
}
echo "OK: friend request accepted\n";

$notifs = api('GET', '/user/notifications', null, $token1);
$types = array_column($notifs['body']['data'] ?? [], 'type');
if (! in_array('friend_accepted', $types, true)) {
    echo "FAIL: player missing friend_accepted notification\n";
    print_r($notifs['body']);
    exit(1);
}
echo "OK: requester got friend_accepted notification\n";

$friends = api('GET', '/friends', null, $token1);
if (empty($friends['body']['friends'])) {
    echo "FAIL: friends list empty\n";
    exit(1);
}
echo "OK: friends list has ".count($friends['body']['friends'])." friend(s)\n";

$gameId = 1;
$challenge = api('POST', '/challenges', [
    'challenged_id' => $user2Id,
    'game_id' => $gameId,
    'message' => '1v1?',
], $token1);
if ($challenge['code'] !== 201) {
    echo "FAIL: send challenge\n";
    print_r($challenge);
    exit(1);
}
$challengeId = $challenge['body']['challenge']['id'] ?? null;
echo "OK: PvP challenge sent (#{$challengeId})\n";

$incomingC = api('GET', '/challenges/incoming', null, $token2);
if (empty($incomingC['body']['challenges'])) {
    echo "FAIL: incoming challenges empty\n";
    exit(1);
}
echo "OK: challenged user sees incoming challenge\n";

$acceptC = api('PUT', "/challenges/{$challengeId}/accept", null, $token2);
if ($acceptC['code'] !== 200) {
    echo "FAIL: accept challenge\n";
    print_r($acceptC);
    exit(1);
}
echo "OK: challenge accepted\n";

$notifs2 = api('GET', '/user/notifications', null, $token1);
$types2 = array_column($notifs2['body']['data'] ?? [], 'type');
if (! in_array('pvp_challenge_accepted', $types2, true)) {
    echo "FAIL: challenger missing pvp_challenge_accepted notification\n";
    exit(1);
}
echo "OK: challenger notified on accept\n";

$read = api('PUT', '/user/notifications/read', null, $token1);
if ($read['code'] !== 200) {
    echo "FAIL: mark all read\n";
    exit(1);
}
echo "OK: mark all notifications read\n";

$feed = api('GET', '/user/activity-feed', null, $token1);
if (! isset($feed['body']['feed'])) {
    echo "FAIL: activity feed\n";
    exit(1);
}
echo "OK: activity feed returned ".count($feed['body']['feed'])." item(s)\n";

echo "\n=== All STEP 17 checks passed ===\n";
