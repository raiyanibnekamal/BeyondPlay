<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerStat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'game_id',
        'matches_played',
        'wins',
        'losses',
        'kills',
        'deaths',
        'total_score',
        'tournament_wins',
        'win_streak',
        'best_streak',
        'updated_at',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function getKdRatioAttribute(): float
    {
        return $this->deaths > 0 ? round($this->kills / $this->deaths, 2) : (float) $this->kills;
    }

    public function getWinRateAttribute(): float
    {
        return $this->matches_played > 0
            ? round($this->wins / $this->matches_played * 100, 1)
            : 0.0;
    }

    public function getAvgScoreAttribute(): float
    {
        return $this->matches_played > 0
            ? round($this->total_score / $this->matches_played, 1)
            : 0.0;
    }
}
