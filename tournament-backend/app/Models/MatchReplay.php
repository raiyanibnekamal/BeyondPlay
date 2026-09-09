<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchReplay extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'match_id',
        'vod_url',
        'platform',
        'duration_minutes',
        'added_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
