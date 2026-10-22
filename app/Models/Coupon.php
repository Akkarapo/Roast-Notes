<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Subtype of Promotion: % off participating products, entered as a code. */
class Coupon extends Model
{
    use HasFactory;

    protected $primaryKey = 'promotion_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['promotion_id', 'code', 'percent_off', 'min_subtotal', 'usage_limit', 'per_user_limit'];

    protected function casts(): array
    {
        return [
            'percent_off' => 'integer',
            'min_subtotal' => 'decimal:2',
            'usage_limit' => 'integer',
            'per_user_limit' => 'integer',
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /** Codes are stored upper-case; members may type them in any case. */
    public static function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }

    protected function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = self::normalize($value);
    }
}
