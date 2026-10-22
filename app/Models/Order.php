<?php

namespace App\Models;

use App\Actions\RecalculateMemberTier;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number', 'user_id', 'status', 'subtotal', 'discount_total', 'shipping_fee', 'total',
        'recipient_name', 'phone', 'shipping_address', 'note', 'placed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'placed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    /**
     * Mark the order delivered and let the member move up a tier. The admin module calls this.
     */
    public function markDelivered(): void
    {
        DB::transaction(function () {
            $this->update(['status' => OrderStatus::Delivered]);
            app(RecalculateMemberTier::class)($this->user);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(OrderDiscount::class)->orderBy('id');
    }
}
