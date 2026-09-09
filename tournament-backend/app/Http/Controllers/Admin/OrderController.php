<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Services\DigitalDownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['user:id,username,email', 'items.product:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $order = Order::with(['items.product', 'user'])->findOrFail($id);
        $previous = $order->status;

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'paid', 'shipped', 'delivered', 'cancelled'])],
        ]);

        $order->update($data);

        if ($previous !== 'paid' && $order->status === 'paid' && $order->user) {
            $downloads = app(DigitalDownloadService::class);
            $links = $downloads->linksForOrder($order);
            try {
                Mail::to($order->user->email)->send(
                    new OrderConfirmationMail($order->user, $order, $links)
                );
            } catch (\Throwable $e) {
                // Mail driver may be log in dev
            }
        }

        return response()->json(['message' => 'Order status updated.', 'order' => $order]);
    }
}
