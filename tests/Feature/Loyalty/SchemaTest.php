<?php

namespace Tests\Feature\Loyalty;

use App\Models\MemberTier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_provides_three_tiers_in_spend_order(): void
    {
        $tiers = MemberTier::ordered()->get();

        $this->assertSame(['bean', 'barista', 'roaster-master'], $tiers->pluck('slug')->all());
        $this->assertSame(['0.00', '25000.00', '50000.00'], $tiers->pluck('min_spend')->all());
        $this->assertSame([0, 5, 10], $tiers->pluck('discount_percent')->all());
        $this->assertSame([false, false, true], $tiers->pluck('early_access')->all());
        $this->assertSame('barista', $tiers[0]->next()->slug);
        $this->assertNull($tiers[2]->next());
    }

    public function test_new_members_start_as_bean(): void
    {
        $user = User::factory()->create();

        $this->assertSame('bean', $user->memberTier->slug);
        $this->assertSame('bean', $user->fresh()->memberTier->slug);
        $this->assertSame('roaster-master', User::factory()->tier('roaster-master')->create()->memberTier->slug);
    }

    public function test_a_tier_in_use_cannot_be_deleted(): void
    {
        User::factory()->tier('barista')->create();

        $this->expectException(QueryException::class);
        MemberTier::where('slug', 'barista')->delete();
    }

    public function test_exclusive_window(): void
    {
        $rare = Product::factory()->exclusive()->create();
        $released = Product::factory()->exclusive(now()->subMinute())->create();
        $normal = Product::factory()->create();

        $this->assertTrue($rare->isExclusiveNow());
        $this->assertFalse($released->isExclusiveNow());
        $this->assertFalse($normal->isExclusiveNow());
        $this->assertSame([$rare->id], Product::exclusiveNow()->pluck('id')->all());
    }
}
