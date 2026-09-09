<?php

namespace App\Http\Controllers;

use App\Models\UserCart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserCartController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $cart = UserCart::where('user_id', $request->user()->id)->first();

        return response()->json([
            'items' => $cart?->items ?? [],
            'coupon_code' => $cart?->coupon_code,
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ]);

        $cart = UserCart::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'items' => $data['items'],
                'coupon_code' => $data['coupon_code'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Cart saved.',
            'items' => $cart->items,
            'coupon_code' => $cart->coupon_code,
        ]);
    }
}
