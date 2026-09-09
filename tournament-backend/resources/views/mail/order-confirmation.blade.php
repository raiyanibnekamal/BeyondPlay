<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Confirmation</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5;">
    <h1>Thanks for your order, {{ $user->username }}!</h1>
    <p>Order <strong>#{{ $order->id }}</strong> has been received.</p>
    <p>Subtotal: {{ number_format($order->subtotal, 2) }}<br>
    Discount: {{ number_format($order->discount_amount, 2) }}<br>
    <strong>Total: {{ number_format($order->total_amount, 2) }}</strong></p>
    <h2>Items</h2>
    <ul>
        @foreach ($order->items as $item)
            <li>{{ $item->product->name ?? 'Product' }} × {{ $item->quantity }} — {{ number_format($item->unit_price * $item->quantity, 2) }}</li>
        @endforeach
    </ul>
    @if (count($downloadLinks) > 0)
        <h2>Digital downloads</h2>
        <p>Links expire in 24 hours:</p>
        <ul>
            @foreach ($downloadLinks as $link)
                <li><a href="{{ $link['download_url'] }}">{{ $link['product_name'] }}</a></li>
            @endforeach
        </ul>
    @endif
    <p>— BeyondPlay Esports</p>
</body>
</html>
