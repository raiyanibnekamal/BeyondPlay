<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTable extends Model
{
    protected $table = 'point_table';

    public $timestamps = false;

    protected $fillable = [
        'tournament_id',
        'team_id',
        'matches_played',
        'wins',
        'losses',
        'draws',
        'points',
        'goal_difference',
        'updated_at',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
