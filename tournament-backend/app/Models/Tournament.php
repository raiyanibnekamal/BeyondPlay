<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tournament extends Model
{
    protected $fillable = [
        'game_id',
        'name',
        'slug',
        'description',
        'cover_image',
        'entry_fee',
        'prize_pool',
        'max_participants',
        'current_participants',
        'format',
        'status',
        'registration_start',
        'registration_end',
        'start_date',
        'end_date',
        'checkin_minutes_before',
        'created_by',
    ];

    protected $casts = [
        'entry_fee' => 'decimal:2',
        'prize_pool' => 'decimal:2',
        'registration_start' => 'datetime',
        'registration_end' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function rule(): HasOne
    {
        return $this->hasOne(TournamentRule::class);
    }

    public function pointTable(): HasMany
    {
        return $this->hasMany(PointTable::class);
    }

    public function bracketPredictions(): HasMany
    {
        return $this->hasMany(BracketPrediction::class);
    }
}
