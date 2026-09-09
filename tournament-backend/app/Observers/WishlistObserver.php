<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Observers;

use App\Mail\WishlistPriceDropMail;
use App\Models\Product;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;

class WishlistObserver
{
    public function __construct(
        protected NotificationService $notifications
    ) {
    }

    public function updating(Product $product): void
    {
        if (! $product->isDirty(['price', 'sale_price'])) {
            return;
        }

        $oldEffective = Product::effectivePriceFrom(
            (float) $product->getOriginal('price'),
            $product->getOriginal('sale_price') !== null ? (float) $product->getOriginal('sale_price') : null
        );

        $newEffective = $product->effectivePrice();

        if ($newEffective >= $oldEffective) {
            return;
        }

        $users = $product->wishlistedBy()->get();

        foreach ($users as $user) {
            $this->notifications->create(
                $user->id,
                'wishlist_price_drop',
                'Price dropped!',
                'Price dropped on '.$product->name.'! Was '.number_format($oldEffective, 2).' now '.number_format($newEffective, 2).'.',
                'shop-details.html?slug='.$product->slug
            );

            Mail::to($user->email)->send(new WishlistPriceDropMail(
                $user,
                $product,
                $oldEffective,
                $newEffective
            ));
        }
    }
}
