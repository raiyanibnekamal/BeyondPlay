<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Services;

use App\Models\Coupon;

class CouponService
{
    public function findByCode(string $code): ?Coupon
    {
        return Coupon::where('code', strtoupper(trim($code)))->first();
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function validate(string $code, float $subtotal): Coupon
    {
        $coupon = $this->findByCode($code);

        if (! $coupon) {
            throw new \InvalidArgumentException('Invalid coupon code.');
        }

        if ($coupon->status !== 'active') {
            throw new \InvalidArgumentException('This coupon is no longer active.');
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            throw new \InvalidArgumentException('This coupon has expired.');
        }

        if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
            throw new \InvalidArgumentException('This coupon has reached its usage limit.');
        }

        if ($subtotal < (float) $coupon->min_order_amount) {
            throw new \InvalidArgumentException(
                'Order subtotal must be at least '.number_format((float) $coupon->min_order_amount, 2).' to use this coupon.'
            );
        }

        return $coupon;
    }

    public function calculateDiscount(Coupon $coupon, float $subtotal): float
    {
        if ($coupon->type === 'percentage') {
            $discount = round($subtotal * ((float) $coupon->value / 100), 2);
        } else {
            $discount = (float) $coupon->value;
        }

        return min($discount, $subtotal);
    }
}
