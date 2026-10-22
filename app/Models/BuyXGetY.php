<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Subtype of Promotion: every X + Y participating units in the cart, the Y cheapest are free. */
class BuyXGetY extends Model
{
    use HasFactory;

    protected $table = 'buy_x_get_y';

    protected $primaryKey = 'promotion_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['promotion_id', 'buy_quantity', 'free_quantity'];

    protected function casts(): array
    {
        return ['buy_quantity' => 'integer', 'free_quantity' => 'integer'];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function groupSize(): int
    {
        return $this->buy_quantity + $this->free_quantity;
    }
}
