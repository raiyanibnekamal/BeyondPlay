<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PvpMatch extends Model
{
    protected $fillable = [
        'challenge_id',
        'game_id',
        'team1_id',
        'team2_id',
        'team1_score',
        'team2_score',
        'winner_id',
        'status',
        'scheduled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(PvpChallenge::class, 'challenge_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function team1(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team1_id');
    }

    public function team2(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team2_id');
    }
}
