<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

/**
 * STEP 18 smoke test: game suggestion approval + stats + predictions.
 * Run: php tests/step18_stats_test.php
 */

$base = 'http://127.0.0.1:8000/api/v1';

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

echo "=== STEP 18 Stats + Predictions + Game Suggestions ===\n";

$admin = api('POST', '/auth/login', ['email' => 'admin@arena.gg', 'password' => 'password']);
$adminToken = $admin['body']['token'] ?? null;
$player = api('POST', '/auth/login', ['email' => 'player@arena.gg', 'password' => 'password']);
$playerToken = $player['body']['token'] ?? null;
$playerId = $player['body']['user']['id'] ?? null;

if (! $adminToken || ! $playerToken) {
    echo "FAIL: login\n";
    exit(1);
}
echo "OK: admin + player login\n";

$gameName = 'Rocket League '.random_int(1000, 9999);
$suggest = api('POST', '/games/suggest', [
    'name' => $gameName,
    'genre' => 'Sports',
    'description' => 'Cars playing soccer.',
], $playerToken);

if ($suggest['code'] !== 201) {
    echo "FAIL: submit suggestion\n";
    print_r($suggest);
    exit(1);
}
$suggestionId = $suggest['body']['suggestion']['id'] ?? null;
echo "OK: game suggestion submitted (#{$suggestionId})\n";

$list = api('GET', '/admin/game-suggestions', ['status' => 'pending'], $adminToken);
if ($list['code'] !== 200) {
    echo "FAIL: list suggestions\n";
    exit(1);
}
echo "OK: admin lists pending suggestions\n";

$approve = api('PUT', "/admin/game-suggestions/{$suggestionId}/approve", null, $adminToken);
if ($approve['code'] !== 200) {
    echo "FAIL: approve suggestion\n";
    print_r($approve);
    exit(1);
}
$gameSlug = $approve['body']['game']['slug'] ?? '';
echo "OK: suggestion approved, game slug: {$gameSlug}\n";

$games = api('GET', '/games');
$names = array_column($games['body'] ?? [], 'name');
if (! in_array($gameName, $names, true)) {
    echo "FAIL: new game not in games list\n";
    exit(1);
}
echo "OK: game appears in public games list\n";

$notifs = api('GET', '/user/notifications', null, $playerToken);
$types = array_column($notifs['body']['data'] ?? [], 'type');
if (! in_array('game_suggestion_approved', $types, true)) {
    echo "FAIL: missing approval notification\n";
    exit(1);
}
echo "OK: player notified of approval\n";

$stats = api('GET', "/stats/player/{$playerId}");
if ($stats['code'] !== 200 || ! isset($stats['body']['totals'])) {
    echo "FAIL: player stats endpoint\n";
    exit(1);
}
echo "OK: public player stats endpoint\n";

$myStats = api('GET', '/user/stats', null, $playerToken);
if ($myStats['code'] !== 200) {
    echo "FAIL: user stats endpoint\n";
    exit(1);
}
echo "OK: authenticated user stats endpoint\n";

// Prediction flow: create open tournament with future start + bracket
$future = date('Y-m-d H:i:s', strtotime('+14 days'));
$create = api('POST', '/admin/tournaments', [
    'game_id' => 1,
    'name' => 'Predict Cup '.random_int(100, 999),
    'status' => 'open',
    'start_date' => $future,
    'max_participants' => 8,
    'format' => 'single_elimination',
], $adminToken);

if ($create['code'] !== 201) {
    echo "FAIL: create tournament\n";
    print_r($create);
    exit(1);
}
$tournamentId = $create['body']['tournament']['id'] ?? null;
echo "OK: open tournament created (#{$tournamentId})\n";

$team1 = api('POST', '/teams', ['name' => 'Alpha '.random_int(10, 99)], $playerToken);
$team2 = api('POST', '/teams', ['name' => 'Beta '.random_int(10, 99)], $adminToken);
$team1Id = $team1['body']['team']['id'] ?? null;
$team2Id = $team2['body']['team']['id'] ?? null;

api('POST', "/tournaments/{$tournamentId}/register", ['team_id' => $team1Id], $playerToken);
api('POST', "/tournaments/{$tournamentId}/register", ['team_id' => $team2Id], $adminToken);

$bracket = api('POST', "/admin/tournaments/{$tournamentId}/generate-bracket", null, $adminToken);
if ($bracket['code'] !== 200) {
    echo "FAIL: generate bracket\n";
    print_r($bracket);
    exit(1);
}
echo "OK: bracket generated\n";

$matches = api('GET', "/tournaments/{$tournamentId}/matches");
$firstMatch = $matches['body'][0] ?? $matches['body']['data'][0] ?? null;
if (! $firstMatch) {
    echo "FAIL: no matches for predictions\n";
    exit(1);
}
$matchId = $firstMatch['id'];
$pickTeam = $firstMatch['team1_id'] ?? $firstMatch['team2_id'];

$predict = api('POST', "/tournaments/{$tournamentId}/predict", [
    'predictions' => ['match_'.$matchId => $pickTeam],
], $playerToken);

if ($predict['code'] !== 201) {
    echo "FAIL: submit prediction\n";
    print_r($predict);
    exit(1);
}
echo "OK: bracket prediction submitted\n";

// Complete match and tournament, then score predictions
api('PUT', "/admin/matches/{$matchId}/score", [
    'team1_score' => 2,
    'team2_score' => 0,
    'status' => 'completed',
    'winner_id' => $pickTeam,
], $adminToken);

$complete = api('PUT', "/admin/tournaments/{$tournamentId}", ['status' => 'completed'], $adminToken);
if ($complete['code'] !== 200) {
    echo "FAIL: mark tournament completed\n";
    exit(1);
}
echo "OK: tournament marked completed (predictions scored)\n";

$board = api('GET', "/tournaments/{$tournamentId}/predictions");
$scores = array_column($board['body']['leaderboard'] ?? [], 'score');
if (! in_array(1, $scores, true) && ! in_array('1', $scores, true)) {
    echo "FAIL: prediction leaderboard missing score\n";
    print_r($board['body']);
    exit(1);
}
echo "OK: prediction leaderboard shows scored entry\n";

echo "\n=== All STEP 18 checks passed ===\n";
