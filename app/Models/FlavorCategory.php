<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Node in the WCR sensory lexicon. parent_id = null means a top-level category (Fruity, Floral, ...).
 */
class FlavorCategory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['parent_id', 'slug', 'name'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(FlavorNote::class);
    }

    public function scopeTopLevel(Builder $query): void
    {
        $query->whereNull('parent_id');
    }
}
