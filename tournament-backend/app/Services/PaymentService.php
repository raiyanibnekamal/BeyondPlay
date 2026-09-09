<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaymentService
{
    public function isStripeEnabled(): bool
    {
        return ! empty(config('arena.stripe_secret'));
    }

    public function stripePublishableKey(): ?string
    {
        $key = config('arena.stripe_key');

        return $key ?: null;
    }

    /**
     * @return array{status: string, payment_id: ?string, payment_method: string}
     */
    public function resolveOrderPayment(float $total, ?string $paymentId, ?string $paymentMethod, int $userId): array
    {
        $method = $paymentMethod ?: 'manual';

        if ($total <= 0) {
            return ['status' => 'paid', 'payment_id' => $paymentId, 'payment_method' => $method];
        }

        if ($this->isStripeEnabled()) {
            if (! $paymentId) {
                throw new \InvalidArgumentException('Payment is required. Complete Stripe checkout first.');
            }
            $this->assertStripePaymentSucceeded($paymentId, (int) round($total * 100), $userId);

            return ['status' => 'paid', 'payment_id' => $paymentId, 'payment_method' => 'stripe'];
        }

        // No gateway configured — orders stay pending until admin marks paid.
        return ['status' => 'pending', 'payment_id' => $paymentId, 'payment_method' => $method];
    }

    /**
     * Verify tournament entry fee payment (Stripe when configured).
     */
    public function assertEntryFeePaid(float $entryFee, ?string $paymentId, int $userId): ?string
    {
        if ($entryFee <= 0) {
            return null;
        }

        if (! $this->isStripeEnabled()) {
            throw new \InvalidArgumentException(
                'This tournament has an entry fee. Configure Stripe (STRIPE_SECRET) or set entry_fee to 0.'
            );
        }

        if (! $paymentId) {
            throw new \InvalidArgumentException('Entry fee payment is required.');
        }

        $this->assertStripePaymentSucceeded($paymentId, (int) round($entryFee * 100), $userId);

        return $paymentId;
    }

    public function createStripePaymentIntent(float $amount, int $userId, string $description, array $metadata = []): array
    {
        if (! $this->isStripeEnabled()) {
            throw new \RuntimeException('Stripe is not configured.');
        }

        $response = Http::withToken(config('arena.stripe_secret'))
            ->asForm()
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => (int) round($amount * 100),
                'currency' => config('arena.stripe_currency', 'usd'),
                'description' => $description,
                'metadata' => array_merge($metadata, ['user_id' => (string) $userId]),
                'automatic_payment_methods[enabled]' => 'true',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Could not create payment: '.($response->json('error.message') ?? 'Stripe error'));
        }

        $body = $response->json();

        return [
            'id' => $body['id'],
            'client_secret' => $body['client_secret'],
        ];
    }

    protected function assertStripePaymentSucceeded(string $paymentId, int $expectedAmountCents, int $userId): void
    {
        $response = Http::withToken(config('arena.stripe_secret'))
            ->get('https://api.stripe.com/v1/payment_intents/'.$paymentId);

        if (! $response->successful()) {
            throw new \InvalidArgumentException('Invalid payment reference.');
        }

        $intent = $response->json();

        if (($intent['status'] ?? '') !== 'succeeded') {
            throw new \InvalidArgumentException('Payment has not been completed.');
        }

        if ((int) ($intent['amount'] ?? 0) !== $expectedAmountCents) {
            throw new \InvalidArgumentException('Payment amount does not match.');
        }

        if ((string) ($intent['metadata']['user_id'] ?? '') !== (string) $userId) {
            throw new \InvalidArgumentException('Payment does not belong to this account.');
        }
    }
}
