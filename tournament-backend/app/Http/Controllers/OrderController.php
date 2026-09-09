<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers;

use App\Services\CouponService;
use App\Services\DigitalDownloadService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with(['items.product:id,name,slug,image,type', 'coupon:id,code'])
            ->latest()
            ->paginate(15);

        return response()->json($orders);
    }

    public function store(
        Request $request,
        OrderService $orders,
        CouponService $coupons,
        PaymentService $payments
    ): JsonResponse {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_id' => ['nullable', 'string', 'max:200'],
            'shipping_name' => ['nullable', 'string', 'max:150'],
            'shipping_address' => ['nullable', 'string'],
            'shipping_city' => ['nullable', 'string', 'max:100'],
            'shipping_phone' => ['nullable', 'string', 'max:20'],
        ]);

        $coupon = null;
        if (! empty($data['coupon_code'])) {
            try {
                $subtotalPreview = 0;
                foreach ($data['items'] as $row) {
                    $product = \App\Models\Product::find($row['product_id']);
                    if ($product) {
                        $subtotalPreview += $product->effectivePrice() * $row['quantity'];
                    }
                }
                $coupon = $coupons->validate($data['coupon_code'], $subtotalPreview);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        $shipping = [
            'shipping_name' => $data['shipping_name'] ?? null,
            'shipping_address' => $data['shipping_address'] ?? null,
            'shipping_city' => $data['shipping_city'] ?? null,
            'shipping_phone' => $data['shipping_phone'] ?? null,
        ];

        // Preview total for payment resolution
        $previewSubtotal = 0.0;
        foreach ($data['items'] as $row) {
            $product = \App\Models\Product::find($row['product_id']);
            if ($product) {
                $previewSubtotal += $product->effectivePrice() * $row['quantity'];
            }
        }
        $previewDiscount = $coupon ? $coupons->calculateDiscount($coupon, $previewSubtotal) : 0;
        $previewTotal = round($previewSubtotal - $previewDiscount, 2);

        try {
            $payment = $payments->resolveOrderPayment(
                $previewTotal,
                $data['payment_id'] ?? null,
                $data['payment_method'] ?? null,
                $request->user()->id
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        try {
            $order = $orders->createOrder(
                $request->user(),
                $data['items'],
                $coupon,
                $shipping,
                $payment['payment_method'],
                $payment['payment_id'],
                $payment['status'] === 'paid'
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $downloadLinks = $payment['status'] === 'paid'
            ? app(DigitalDownloadService::class)->linksForOrder($order)
            : [];

        $message = $payment['status'] === 'paid'
            ? 'Order created successfully.'
            : 'Order received. Payment is pending — an admin will confirm once payment is verified.';

        return response()->json([
            'message' => $message,
            'order' => $order,
            'download_links' => $downloadLinks,
            'payment_status' => $payment['status'],
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = \App\Models\Order::with(['items.product', 'coupon'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $downloadLinks = app(DigitalDownloadService::class)->linksForOrder($order);

        return response()->json([
            'order' => $order,
            'download_links' => $downloadLinks,
        ]);
    }

    public function download(
        Request $request,
        int $order,
        int $product,
        DigitalDownloadService $downloads
    ): BinaryFileResponse|JsonResponse {
        $userId = (int) $request->query('user');

        $orderModel = \App\Models\Order::where('id', $order)
            ->where('user_id', $userId)
            ->where('status', 'paid')
            ->firstOrFail();

        $item = $orderModel->items()->where('product_id', $product)->firstOrFail();
        $productModel = $item->product;

        if ($productModel->type !== 'digital') {
            return response()->json(['message' => 'This product is not a digital download.'], 422);
        }

        $path = $downloads->resolveFilePath($productModel);

        if (! $path) {
            return response()->json(['message' => 'Digital file not found.'], 404);
        }

        return response()->download($path, basename($path));
    }
}
