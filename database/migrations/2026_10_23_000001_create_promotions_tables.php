<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promotions module — EER specialization: promotions (supertype) -> coupons | buy_x_get_y (disjoint, total).
 * See docs/specs/SPEC-promotions.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['coupon', 'buy_x_get_y']); // discriminator
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        // Subtypes share the supertype's primary key
        Schema::create('coupons', function (Blueprint $table) {
            $table->foreignId('promotion_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('code', 30)->unique();
            $table->unsignedTinyInteger('percent_off');
            $table->decimal('min_subtotal', 10, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedSmallInteger('per_user_limit')->nullable();
        });

        Schema::create('buy_x_get_y', function (Blueprint $table) {
            $table->foreignId('promotion_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('buy_quantity');
            $table->unsignedSmallInteger('free_quantity');
        });

        // "participating products"
        Schema::create('promotion_products', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['promotion_id', 'product_id']);
        });

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->foreignId('promotion_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_discounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
        });
        Schema::dropIfExists('promotion_products');
        Schema::dropIfExists('buy_x_get_y');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('promotions');
    }
};
