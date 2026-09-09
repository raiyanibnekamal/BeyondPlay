<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRule extends Model
{
    protected $fillable = [
        'tournament_id',
        'format_details',
        'schedule_info',
        'scoring_rules',
        'code_of_conduct',
        'dispute_policy',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
