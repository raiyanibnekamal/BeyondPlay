<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'cover_image',
        'logo',
        'description',
        'genre',
        'status',
    ];

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function playerStats(): HasMany
    {
        return $this->hasMany(PlayerStat::class);
    }
}
