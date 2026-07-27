<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jersey extends Model
{
    use HasFactory, ResolvesMediaUrl;

    public static array $sizes = ['S', 'M', 'L', 'XL'];

    public static int $topCount = 4;

    protected $fillable = [
        'league_id',
        'brand_id',
        'category_id',
        'sku',
        'name',
        'slug',
        'type',
        'description',
        'weight',
        'price',
        'price_nameset',
        'image',
        'gallery',
        'stock',
        'sold',
        'is_active',
        'is_featured',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'gallery' => 'array',
        'stock' => 'array',
        'weight' => 'integer',
        'price' => 'integer',
        'price_nameset' => 'integer',
        'sold' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Jersey $jersey): void {
            if ($jersey->isDirty('name') || blank($jersey->slug)) {
                $jersey->slug = str($jersey->name)->slug()->value();
            }
        });

        static::creating(function (Jersey $jersey): void {
            $jersey->created_by ??= auth()->id();
        });

        static::updating(function (Jersey $jersey): void {
            if (auth()->check()) {
                $jersey->updated_by = auth()->id();
            }
        });
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = ucwords(trim($value));
    }

    public function setTypeAttribute(string $value): void
    {
        $this->attributes['type'] = ucwords(trim($value));
    }

    public function setSkuAttribute(string $value): void
    {
        $this->attributes['sku'] = strtoupper(trim($value));
    }

    public function getImage(): string
    {
        return $this->resolveMediaUrl($this->image, 'jerseys');
    }

    public function getGalleryImages(): array
    {
        $paths = collect($this->gallery ?: [$this->image])
            ->filter()
            ->prepend($this->image)
            ->unique()
            ->take(3)
            ->values();

        while ($paths->count() < 3) {
            $paths->push($paths->first());
        }

        return $paths
            ->filter()
            ->map(fn (string $path): array => [
                'path' => $path,
                'url' => $this->resolveMediaUrl($path, 'jerseys'),
            ])
            ->values()
            ->all();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeHasLeague(Builder $query, ?string $slug = null): Builder
    {
        return $query->whereHas(
            'league',
            fn (Builder $leagueQuery): Builder => $leagueQuery->where('slug', $slug),
        );
    }

    public function scopeHasBrand(Builder $query, ?string $slug = null): Builder
    {
        return $query->whereHas(
            'brand',
            fn (Builder $brandQuery): Builder => $brandQuery->where('slug', $slug),
        );
    }

    public function scopeHasCategory(Builder $query, ?string $slug = null): Builder
    {
        return $query->whereHas(
            'category',
            fn (Builder $categoryQuery): Builder => $categoryQuery->where('slug', $slug),
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBestSeller(Builder $query): Builder
    {
        return $query->where('sold', '>=', self::$topCount);
    }
}
