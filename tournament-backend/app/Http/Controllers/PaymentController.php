<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function config(PaymentService $payments): JsonResponse
    {
        return response()->json([
            'stripe_enabled' => $payments->isStripeEnabled(),
            'stripe_publishable_key' => $payments->stripePublishableKey(),
            'currency' => config('arena.stripe_currency', 'usd'),
        ]);
    }

    public function createIntent(Request $request, PaymentService $payments): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.5'],
            'purpose' => ['required', 'string', 'max:100'],
            'reference_id' => ['nullable', 'integer'],
        ]);

        $intent = $payments->createStripePaymentIntent(
            (float) $data['amount'],
            $request->user()->id,
            $data['purpose'],
            [
                'purpose' => $data['purpose'],
                'reference_id' => (string) ($data['reference_id'] ?? ''),
            ]
        );

        return response()->json($intent);
    }
}
