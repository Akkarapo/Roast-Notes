<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FlavorNote extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['flavor_category_id', 'name'];

    /** Subcategory (e.g. Berry); its parent is the top-level category (e.g. Fruity). */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FlavorCategory::class, 'flavor_category_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_flavor_notes');
    }
}
