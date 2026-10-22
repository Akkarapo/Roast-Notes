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
    public function markDelivered(?User $by = null, ?string $note = null): void
    {
        DB::transaction(function () use ($by, $note) {
            $from = $this->status;
            $this->update(['status' => OrderStatus::Delivered]);
            $this->recordStatus($from, OrderStatus::Delivered, $by, $note);
            app(RecalculateMemberTier::class)($this->user);
        });
    }

    /** Append to the status history. Call inside the same transaction as the change itself. */
    public function recordStatus(?OrderStatus $from, OrderStatus $to, ?User $by = null, ?string $note = null): OrderStatusChange
    {
        return $this->statusChanges()->create([
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $by?->id,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(OrderStatusChange::class)->orderBy('created_at')->orderBy('id');
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
