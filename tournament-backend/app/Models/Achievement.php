<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Achievement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'condition_type',
        'condition_value',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('earned_at');
    }
}
