<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog module tables — see docs/specs/SPEC-catalog.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('origins', function (Blueprint $table) {
            $table->id();
            $table->string('country', 60);
            $table->string('region', 100);
            $table->unique(['country', 'region']);
        });

        // WCR sensory lexicon hierarchy (recursive: top-level category -> subcategory)
        Schema::create('flavor_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('flavor_categories')->cascadeOnDelete();
            $table->string('slug', 40)->unique();
            $table->string('name', 60);
        });

        Schema::create('flavor_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flavor_category_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40)->unique();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('nickname', 60);
            $table->enum('type', ['bean', 'drip_bag'])->default('bean')->index();
            $table->string('farm_name', 150)->nullable();
            $table->string('variety', 60)->nullable();
            $table->enum('process', ['washed', 'natural', 'honey', 'semi_washed', 'other']);
            $table->unsignedSmallInteger('altitude_m')->nullable();
            $table->enum('roast_level', ['light', 'medium', 'dark'])->index();
            $table->date('roast_date')->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->string('cqi_ref', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('product_flavor_notes', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flavor_note_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'flavor_note_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('size', ['100g', '250g', '500g', 'sachet']);
            $table->unsignedSmallInteger('weight_g');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->string('sku', 40)->unique();
            $table->timestamps();
            $table->unique(['product_id', 'size']);
        });

        // 1:1 with products — only lots that came from CQI have scores
        Schema::create('cupping_scores', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();
            foreach (['aroma', 'flavor', 'aftertaste', 'acidity', 'body', 'balance', 'uniformity', 'clean_cup', 'sweetness', 'cupper_points'] as $column) {
                $table->decimal($column, 4, 2);
            }
            $table->decimal('total_points', 5, 2);
            $table->date('graded_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupping_scores');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_flavor_notes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('flavor_notes');
        Schema::dropIfExists('flavor_categories');
        Schema::dropIfExists('origins');
    }
};
