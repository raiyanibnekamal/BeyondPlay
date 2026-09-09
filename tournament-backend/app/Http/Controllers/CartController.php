<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers;

use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function validateCoupon(Request $request, CouponService $coupons): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $coupon = $coupons->validate($data['code'], (float) $data['subtotal']);
            $discount = $coupons->calculateDiscount($coupon, (float) $data['subtotal']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'valid' => true,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => $coupon->value,
            ],
            'discount_amount' => $discount,
            'subtotal' => (float) $data['subtotal'],
            'total_after_discount' => round((float) $data['subtotal'] - $discount, 2),
        ]);
    }
}
