<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Coupon::orderByDesc('id')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $coupon = Coupon::create($data);

        return response()->json(['message' => 'Coupon created.', 'coupon' => $coupon], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $data = $request->validate([
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('coupons', 'code')->ignore($coupon->id),
            ],
            'type' => ['sometimes', Rule::in(['percentage', 'fixed'])],
            'value' => ['sometimes', 'numeric', 'min:0'],
            'min_order_amount' => ['sometimes', 'numeric', 'min:0'],
            'max_uses' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);
        $coupon->update($data);

        return response()->json(['message' => 'Coupon updated.', 'coupon' => $coupon]);
    }

    public function destroy(int $id): JsonResponse
    {
        Coupon::findOrFail($id)->delete();

        return response()->json(['message' => 'Coupon deleted.']);
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('coupons', 'code')->ignore($coupon?->id),
            ],
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
    }
}
