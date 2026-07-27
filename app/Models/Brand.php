<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory, ResolvesMediaUrl;

    protected $fillable = [
        'name',
        'slug',
        'country',
        'description',
        'website',
        'image',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Brand $brand): void {
            if ($brand->isDirty('name') || blank($brand->slug)) {
                $brand->slug = str($brand->name)->slug()->value();
            }
        });

        static::creating(function (Brand $brand): void {
            $brand->created_by ??= auth()->id();
        });

        static::updating(function (Brand $brand): void {
            if (auth()->check()) {
                $brand->updated_by = auth()->id();
            }
        });
    }

    public function jerseys(): HasMany
    {
        return $this->hasMany(Jersey::class);
    }

    public function getImage(): string
    {
        return $this->resolveMediaUrl($this->image, 'brands');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
