<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BracketPrediction extends Model
{
    protected $fillable = [
        'tournament_id',
        'user_id',
        'predictions',
        'score',
        'scored_at',
    ];

    protected $casts = [
        'predictions' => 'array',
        'scored_at' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
