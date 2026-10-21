<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CQI cupping scores (1:1 with Product). Every attribute is out of 10, total out of 100.
 */
class CuppingScore extends Model
{
    use HasFactory;

    public const ATTRIBUTES = ['aroma', 'flavor', 'aftertaste', 'acidity', 'body', 'balance', 'uniformity', 'clean_cup', 'sweetness', 'cupper_points'];

    /** Attributes shown as bars on the product page. */
    public const DISPLAYED = ['aroma', 'flavor', 'aftertaste', 'acidity', 'body', 'balance'];

    protected $primaryKey = 'product_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['product_id', ...self::ATTRIBUTES, 'total_points', 'graded_on'];

    protected function casts(): array
    {
        return array_fill_keys([...self::ATTRIBUTES, 'total_points'], 'decimal:2') + ['graded_on' => 'date'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
