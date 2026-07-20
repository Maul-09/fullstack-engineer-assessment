<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'regular_price',
        'flash_price',
        'flash_starts_at',
        'flash_ends_at',
        'inventory_quantity',
    ];

    protected function casts(): array
    {
        return [
            'regular_price' => 'integer',
            'flash_price' => 'integer',
            'flash_starts_at' => 'immutable_datetime',
            'flash_ends_at' => 'immutable_datetime',
            'inventory_quantity' => 'integer',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function currentPrice(CarbonInterface $at): int
    {
        $flashSaleIsActive = $this->flash_price !== null
            && $this->flash_starts_at?->lte($at)
            && $this->flash_ends_at?->gt($at);

        return $flashSaleIsActive ? $this->flash_price : $this->regular_price;
    }
}
