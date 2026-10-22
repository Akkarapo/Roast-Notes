<?php

use App\Models\MemberTier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_tiers', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('slug', 30)->unique();
            $table->string('name', 50);
            $table->decimal('min_spend', 10, 2)->unique();
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->boolean('early_access')->default(false);
            $table->timestamps();
        });

        // Reference data the app always needs (not demo data) — thresholds agreed in SPEC-loyalty.md
        DB::table('member_tiers')->insert([
            ['id' => MemberTier::BEAN, 'slug' => 'bean', 'name' => 'Bean', 'min_spend' => 0, 'discount_percent' => 0, 'early_access' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'slug' => 'barista', 'name' => 'Barista', 'min_spend' => 25000, 'discount_percent' => 5, 'early_access' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'slug' => 'roaster-master', 'name' => 'Roaster Master', 'min_spend' => 50000, 'discount_percent' => 10, 'early_access' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('member_tier_id')->default(MemberTier::BEAN)->after('role');
            $table->foreign('member_tier_id')->references('id')->on('member_tiers')->restrictOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dateTime('exclusive_until')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('exclusive_until'));
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['member_tier_id']);
            $table->dropColumn('member_tier_id');
        });
        Schema::dropIfExists('member_tiers');
    }
};
