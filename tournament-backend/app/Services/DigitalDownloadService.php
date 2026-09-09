<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\URL;

class DigitalDownloadService
{
    /**
     * @return array<int, array{product_id: int, product_name: string, download_url: string}>
     */
    public function linksForOrder(Order $order): array
    {
        if ($order->status !== 'paid') {
            return [];
        }

        $links = [];

        foreach ($order->items as $item) {
            $product = $item->product;
            if (! $product || $product->type !== 'digital' || ! $product->digital_file) {
                continue;
            }

            $links[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'download_url' => URL::temporarySignedRoute(
                    'api.v1.orders.download',
                    now()->addHours(24),
                    [
                        'order' => $order->id,
                        'product' => $product->id,
                        'user' => $order->user_id,
                    ]
                ),
            ];
        }

        return $links;
    }

    public function resolveFilePath(Product $product): ?string
    {
        if (! $product->digital_file) {
            return null;
        }

        $path = storage_path('app/'.$product->digital_file);

        return is_file($path) ? $path : null;
    }
}
