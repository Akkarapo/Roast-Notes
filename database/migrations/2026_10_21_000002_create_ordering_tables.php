<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ordering module tables — see docs/specs/SPEC-ordering.md
 */
return new class extends Migration
{
    private const GRINDS = ['whole_bean', 'drip', 'espresso', 'none'];

    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->enum('grind', self::GRINDS)->default('whole_bean');
            $table->unsignedSmallInteger('quantity');
            $table->timestamps();
            $table->unique(['cart_id', 'product_variant_id', 'grind']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['pending', 'confirmed', 'shipped', 'in_transit', 'delivered', 'cancelled'])->default('pending')->index();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('recipient_name', 100);
            $table->string('phone', 20);
            $table->text('shipping_address');
            $table->string('note', 500)->nullable();
            $table->dateTime('placed_at')->index();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });

        // Snapshot of what was bought — later price or name changes never alter an old order
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->string('product_name', 160);
            $table->string('size', 10);
            $table->enum('grind', self::GRINDS);
            $table->decimal('unit_price', 10, 2);
            $table->unsignedSmallInteger('quantity');
            $table->decimal('line_total', 10, 2);
        });

        // One row per discount applied (coupon, buy X get Y, member tier, ...)
        Schema::create('order_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('source', 30);
            $table->string('label', 120);
            $table->decimal('amount', 10, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_discounts');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
