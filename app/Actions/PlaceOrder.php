<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Money;
use App\Support\Pricing\CartLine;
use App\Support\Pricing\DiscountLine;
use App\Support\Pricing\PriceCalculator;
use App\Support\Promotions\CouponValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    public function __construct(private PriceCalculator $calculator, private CouponValidator $coupons) {}

    /**
     * Turn the member's cart into a pending order: lock stock, re-price on the server,
     * snapshot every line, record discounts, take stock, empty the cart — all or nothing.
     *
     * @param  array{recipient_name: string, phone: string, shipping_address: string, note?: ?string}  $shipping
     *
     * @throws ValidationException when the cart is empty or any line cannot be fulfilled (nothing is saved)
     */
    public function __invoke(User $user, array $shipping, ?string $couponCode = null): Order
    {
        return DB::transaction(function () use ($user, $shipping, $couponCode) {
            $cart = $user->cartOrCreate();
            $items = $cart->items()->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => __('Your cart is empty.')]);
            }

            // Lock the variant rows so two checkouts cannot both take the last bag
            $variants = ProductVariant::with('product')
                ->whereIn('id', $items->pluck('product_variant_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $items->each(fn (CartItem $item) => $item->setRelation('variant', $variants[$item->product_variant_id]));

            $problems = $items->reject(fn (CartItem $item) => $item->isPurchasable())
                ->map(fn (CartItem $item) => ! $item->variant->product->is_active
                    ? __(':item is no longer available.', ['item' => $item->variant->displayName()])
                    : __(':item: only :stock left.', ['item' => $item->variant->displayName(), 'stock' => $item->variant->stock]))
                ->values()->all();
            if ($problems) {
                throw ValidationException::withMessages(['cart' => $problems]);
            }

            // re-check the coupon with its promotion row locked (usage limits under concurrency)
            if ($couponCode !== null) {
                $lines = $items->map(fn (CartItem $item) => new CartLine($item));
                $reason = $this->coupons->check($couponCode, $user, $lines)->reason;
                if ($reason !== null) {
                    throw ValidationException::withMessages(['coupon' => $reason]);
                }
            }

            $quote = $this->calculator->quoteItems($user, $items, $couponCode);

            $order = Order::create([
                'order_number' => $this->nextOrderNumber(),
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'subtotal' => Money::fromSatang($quote->subtotal),
                'discount_total' => Money::fromSatang($quote->discountTotal),
                'shipping_fee' => Money::fromSatang($quote->shippingFee),
                'total' => Money::fromSatang($quote->total),
                'recipient_name' => $shipping['recipient_name'],
                'phone' => $shipping['phone'],
                'shipping_address' => $shipping['shipping_address'],
                'note' => $shipping['note'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($quote->lines as $line) {
                $order->items()->create([
                    'product_variant_id' => $line->variant->id,
                    'product_name' => $line->product->name,
                    'size' => $line->variant->size->value,
                    'grind' => $line->grind,
                    'unit_price' => Money::fromSatang($line->unitPrice),
                    'quantity' => $line->quantity,
                    'line_total' => Money::fromSatang($line->lineTotal),
                ]);
                $line->variant->decrement('stock', $line->quantity);
            }

            $order->discounts()->createMany(array_map(fn (DiscountLine $discount) => [
                'promotion_id' => $discount->promotionId(),
                'source' => $discount->source,
                'label' => $discount->label,
                'amount' => Money::fromSatang($discount->amount),
            ], $quote->discounts));

            $cart->items()->delete();

            return $order;
        });
    }

    /** RN-20261021-0007: date + running number for that day (unique index is the final guard). */
    private function nextOrderNumber(): string
    {
        $prefix = 'RN-'.now()->format('Ymd').'-';
        $last = Order::where('order_number', 'like', $prefix.'%')->lockForUpdate()->max('order_number');

        return $prefix.str_pad((string) ($last ? (int) substr($last, -4) + 1 : 1), 4, '0', STR_PAD_LEFT);
    }
}
