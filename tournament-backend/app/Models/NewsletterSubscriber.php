<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
