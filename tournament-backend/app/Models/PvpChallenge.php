<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PvpChallenge extends Model
{
    protected $fillable = [
        'challenger_id',
        'challenged_id',
        'game_id',
        'message',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function challenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'challenger_id');
    }

    public function challenged(): BelongsTo
    {
        return $this->belongsTo(User::class, 'challenged_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function pvpMatch(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PvpMatch::class, 'challenge_id');
    }
}
