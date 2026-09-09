<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'sale_price',
        'stock',
        'category',
        'type',
        'digital_file',
        'image',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function wishlistedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wishlists')
            ->withPivot('added_at');
    }

    public function effectivePrice(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }

    public static function effectivePriceFrom(?float $price, ?float $salePrice): float
    {
        return (float) ($salePrice ?? $price ?? 0);
    }
}
