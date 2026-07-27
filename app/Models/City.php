<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    public static array $types = ['City', 'District'];

    /** @var array<int, string> */
    protected $fillable = ['province_id', 'provider_id', 'name', 'type', 'postal_code'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
