<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontSetting extends Model
{
    use HasFactory, ResolvesMediaUrl;

    protected $fillable = [
        'name',
        'hero_eyebrow',
        'hero_title',
        'hero_subtitle',
        'hero_image',
        'hero_cta_label',
        'hero_cta_url',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (StorefrontSetting $setting): void {
            $setting->created_by ??= auth()->id();
        });

        static::updating(function (StorefrontSetting $setting): void {
            $setting->updated_by = auth()->id();
        });
    }

    public function getHeroImage(): string
    {
        return $this->resolveMediaUrl($this->hero_image, 'storefront');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
