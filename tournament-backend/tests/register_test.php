<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

$base = 'http://127.0.0.1:8000/api/v1';
$email = 'newuser'.random_int(10000, 99999).'@arena.test';
$body = json_encode([
    'username' => 'NewPlayer'.random_int(100, 999),
    'email' => $email,
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);

$ch = curl_init($base.'/auth/register');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => $body,
]);
$raw = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

echo "HTTP $code\n";
if ($err) {
    echo "CURL: $err\n";
}
echo $raw."\n";
