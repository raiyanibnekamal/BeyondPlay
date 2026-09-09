<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()
            ->wishlist()
            ->where('products.status', 'active')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => $p->price,
                'sale_price' => $p->sale_price,
                'effective_price' => $p->effectivePrice(),
                'image' => $p->image,
                'type' => $p->type,
                'added_at' => $p->pivot->added_at,
            ]);

        return response()->json(['wishlist' => $items]);
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        $product = Product::where('id', $productId)->where('status', 'active')->firstOrFail();

        $request->user()->wishlist()->syncWithoutDetaching([
            $product->id => ['added_at' => now()],
        ]);

        return response()->json([
            'message' => 'Added to wishlist.',
            'product_id' => $product->id,
        ], 201);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $request->user()->wishlist()->detach($productId);

        return response()->json(['message' => 'Removed from wishlist.']);
    }
}
