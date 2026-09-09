<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('name')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $product = Product::create($data);

        return response()->json(['message' => 'Product created.', 'product' => $product], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $data = $this->validated($request, $product);

        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        $product->update($data);

        return response()->json(['message' => 'Product updated.', 'product' => $product->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        Product::findOrFail($id)->delete();

        return response()->json(['message' => 'Product deleted.']);
    }

    private function validated(Request $request, ?Product $existing = null): array
    {
        $required = $existing ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'price' => [$required, 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'category' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['physical', 'digital'])],
            'digital_file' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function uniqueSlug(string $name, ?int $exceptId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $n = 1;

        while (Product::where('slug', $slug)->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
