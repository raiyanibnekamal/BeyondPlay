<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

/**
 * STEP 19 smoke test: shop orders, coupons, wishlist price drop.
 * Run: php tests/step19_shop_test.php
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

echo "=== STEP 19 Shop + Wishlist + Coupons ===\n";

$player = api('POST', '/auth/login', ['email' => 'player@arena.gg', 'password' => 'password']);
$token = $player['body']['token'] ?? null;
if (! $token) {
    echo "FAIL: login\n";
    exit(1);
}
echo "OK: player login\n";

$products = api('GET', '/products', ['type' => 'physical']);
if ($products['code'] !== 200) {
    echo "FAIL: list products\n";
    exit(1);
}
$productList = $products['body']['data'] ?? $products['body'] ?? [];
$product = $productList[0] ?? null;
if (! $product) {
    echo "FAIL: no physical products\n";
    exit(1);
}
$productId = $product['id'];
echo "OK: list products (physical)\n";

$coupon = api('POST', '/cart/validate-coupon', [
    'code' => 'ARENA10',
    'subtotal' => 899,
], $token);

if ($coupon['code'] !== 200 || empty($coupon['body']['valid'])) {
    echo "FAIL: validate coupon (run db:seed for ARENA10)\n";
    print_r($coupon);
    exit(1);
}
echo "OK: coupon validated, discount ".$coupon['body']['discount_amount']."\n";

$order = api('POST', '/orders', [
    'items' => [['product_id' => $productId, 'quantity' => 1]],
    'coupon_code' => 'ARENA10',
    'payment_method' => 'test',
    'shipping_name' => 'Test Player',
    'shipping_address' => '123 Arena St',
    'shipping_city' => 'Dhaka',
    'shipping_phone' => '01700000000',
], $token);

if ($order['code'] !== 201) {
    echo "FAIL: create order\n";
    print_r($order);
    exit(1);
}

$orderId = $order['body']['order']['id'] ?? null;
$itemCount = count($order['body']['order']['items'] ?? []);
if (! $orderId || $itemCount < 1) {
    echo "FAIL: order items missing\n";
    exit(1);
}
echo "OK: order #{$orderId} created with {$itemCount} item(s)\n";

$detail = api('GET', "/orders/{$orderId}", null, $token);
if ($detail['code'] !== 200) {
    echo "FAIL: order detail\n";
    exit(1);
}
echo "OK: order detail (own order only)\n";

$wish = api('POST', "/wishlist/{$productId}", null, $token);
if (! in_array($wish['code'], [200, 201], true)) {
    echo "FAIL: add wishlist\n";
    exit(1);
}
echo "OK: product added to wishlist\n";

// Trigger price drop via Eloquent (admin product API is STEP 20)
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$model = App\Models\Product::find($productId);
$oldPrice = $model->effectivePrice();
$model->update(['sale_price' => max(1, $oldPrice - 50)]);

$notifs = api('GET', '/user/notifications', null, $token);
$types = array_column($notifs['body']['data'] ?? [], 'type');
if (! in_array('wishlist_price_drop', $types, true)) {
    echo "FAIL: wishlist price drop notification missing\n";
    exit(1);
}
echo "OK: wishlist price drop notification created\n";

echo "\n=== All STEP 19 checks passed ===\n";
