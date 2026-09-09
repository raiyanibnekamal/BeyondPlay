<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::where('status', 'active');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('type')) {
            $request->validate(['type' => Rule::in(['physical', 'digital'])]);
            $query->where('type', $request->type);
        }

        $products = $query->orderBy('name')->paginate(12);

        $products->getCollection()->transform(fn (Product $p) => $this->formatProduct($p));

        return response()->json($products);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        return response()->json($this->formatProduct($product));
    }

    private function formatProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price' => $product->price,
            'sale_price' => $product->sale_price,
            'effective_price' => $product->effectivePrice(),
            'stock' => $product->stock,
            'category' => $product->category,
            'type' => $product->type,
            'image' => $product->image,
            'status' => $product->status,
        ];
    }
}
