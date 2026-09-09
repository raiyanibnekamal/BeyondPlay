<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'tournament_id',
        'round',
        'match_number',
        'team1_id',
        'team2_id',
        'team1_score',
        'team2_score',
        'winner_id',
        'status',
        'scheduled_at',
        'completed_at',
        'stream_url',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function team1(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team1_id');
    }

    public function team2(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_id');
    }

    public function replay(): HasOne
    {
        return $this->hasOne(MatchReplay::class, 'match_id');
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class, 'match_id');
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(TournamentCheckin::class, 'match_id');
    }
}
