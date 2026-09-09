<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Services;

use App\Mail\OrderConfirmationMail;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    public function __construct(
        protected CouponService $coupons,
        protected DigitalDownloadService $downloads
    ) {
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     * @param  array<string, mixed>  $shippingData
     *
     * @throws \InvalidArgumentException
     */
    public function createOrder(
        User $user,
        array $items,
        ?Coupon $coupon = null,
        array $shippingData = [],
        ?string $paymentMethod = null,
        ?string $paymentId = null,
        bool $markAsPaid = true
    ): Order {
        if ($items === []) {
            throw new \InvalidArgumentException('Order must contain at least one item.');
        }

        return DB::transaction(function () use ($user, $items, $coupon, $shippingData, $paymentMethod, $paymentId, $markAsPaid) {
            $lineItems = [];
            $subtotal = 0.0;
            $hasPhysical = false;

            foreach ($items as $row) {
                $product = Product::where('id', $row['product_id'])
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw new \InvalidArgumentException('Product #'.$row['product_id'].' is not available.');
                }

                $qty = (int) $row['quantity'];
                if ($qty < 1) {
                    throw new \InvalidArgumentException('Invalid quantity for '.$product->name.'.');
                }

                if ($product->type === 'physical') {
                    $hasPhysical = true;
                    if ($product->stock < $qty) {
                        throw new \InvalidArgumentException('Insufficient stock for '.$product->name.'.');
                    }
                }

                $unitPrice = $product->effectivePrice();
                $lineTotal = round($unitPrice * $qty, 2);
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                ];
            }

            if ($hasPhysical) {
                foreach (['shipping_name', 'shipping_address', 'shipping_city', 'shipping_phone'] as $field) {
                    if (empty($shippingData[$field])) {
                        throw new \InvalidArgumentException('Shipping details are required for physical products.');
                    }
                }
            }

            $discount = 0.0;
            if ($coupon) {
                $this->coupons->validate($coupon->code, $subtotal);
                $discount = $this->coupons->calculateDiscount($coupon, $subtotal);
            }

            $total = round($subtotal - $discount, 2);

            $order = Order::create([
                'user_id' => $user->id,
                'coupon_id' => $coupon?->id,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'status' => $markAsPaid ? 'paid' : 'pending',
                'payment_method' => $paymentMethod,
                'payment_id' => $paymentId,
                'shipping_name' => $shippingData['shipping_name'] ?? null,
                'shipping_address' => $shippingData['shipping_address'] ?? null,
                'shipping_city' => $shippingData['shipping_city'] ?? null,
                'shipping_phone' => $shippingData['shipping_phone'] ?? null,
            ]);

            foreach ($lineItems as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                ]);

                if ($line['product']->type === 'physical' && $markAsPaid) {
                    $line['product']->decrement('stock', $line['quantity']);
                }
            }

            if ($coupon) {
                $coupon->increment('used_count');
            }

            $order->load(['items.product', 'coupon']);

            if ($markAsPaid) {
                $downloadLinks = $this->downloads->linksForOrder($order);
                Mail::to($user->email)->send(new OrderConfirmationMail($user, $order, $downloadLinks));
            }

            return $order;
        });
    }
}
