<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesMediaUrl;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory, ResolvesMediaUrl;

    protected $fillable = [
        'order_id',
        'jersey_id',
        'product_name',
        'product_sku',
        'product_slug',
        'image_path',
        'product_weight',
        'size',
        'quantity',
        'unit_price',
        'nameset_unit_price',
        'line_total',
        'nameset',
        'stock_reserved_at',
        'stock_released_at',
        'stock_consumed_at',
    ];

    protected $casts = [
        'product_weight' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'integer',
        'nameset_unit_price' => 'integer',
        'line_total' => 'integer',
        'nameset' => 'array',
        'stock_reserved_at' => 'datetime',
        'stock_released_at' => 'datetime',
        'stock_consumed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (OrderItem $item): void {
            if (! $item->exists) {
                return;
            }

            $orderStatus = $item->order()->value('status');

            if ($orderStatus !== Order::STATUS_IN_CART
                && $item->isDirty([
                    'jersey_id', 'product_name', 'product_sku', 'product_slug', 'image_path',
                    'product_weight', 'size', 'quantity', 'unit_price', 'nameset_unit_price',
                    'line_total', 'nameset',
                ])) {
                throw new DomainException('Historical order item snapshots are immutable.');
            }
        });

        static::deleting(function (OrderItem $item): void {
            if ($item->order()->where('status', '!=', Order::STATUS_IN_CART)->exists()) {
                throw new DomainException('Historical order items cannot be deleted.');
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function jersey(): BelongsTo
    {
        return $this->belongsTo(Jersey::class);
    }

    public function getImageUrlAttribute(): string
    {
        return $this->resolveMediaUrl($this->image_path, 'jerseys');
    }
}
