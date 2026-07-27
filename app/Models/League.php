<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesMediaUrl;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class League extends Model
{
    use HasFactory, ResolvesMediaUrl;

    protected $fillable = [
        'name',
        'slug',
        'country',
        'image',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::saving(function (League $league): void {
            if ($league->isDirty('name') || blank($league->slug)) {
                $league->slug = str($league->name)->slug()->value();
            }
        });

        static::creating(function (League $league): void {
            $league->created_by ??= auth()->id();
        });

        static::updating(function (League $league): void {
            if (auth()->check()) {
                $league->updated_by = auth()->id();
            }
        });

        static::deleting(function (League $league): void {
            if ($league->jerseys()->exists()) {
                throw new DomainException('A league with products cannot be permanently deleted.');
            }
        });
    }

    public function jerseys(): HasMany
    {
        return $this->hasMany(Jersey::class);
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = ucwords(trim($value));
    }

    public function setCountryAttribute(string $value): void
    {
        $this->attributes['country'] = ucwords(trim($value));
    }

    public function getImage(): string
    {
        return $this->resolveMediaUrl($this->image, 'leagues');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
