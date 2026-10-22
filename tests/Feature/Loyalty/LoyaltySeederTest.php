<?php

namespace Tests\Feature\Loyalty;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_shows_all_three_tiers_and_three_rare_lots(): void
    {
        $this->seed();

        $tiers = User::with('memberTier')->where('email', 'like', 'member%')->orderBy('email')->get()
            ->mapWithKeys(fn (User $u) => [$u->email => $u->memberTier->slug])->all();
        $this->assertSame([
            'member1@roastnotes.test' => 'roaster-master',
            'member2@roastnotes.test' => 'barista',
            'member3@roastnotes.test' => 'bean',
        ], $tiers);
        $this->assertSame(3, Product::exclusiveNow()->count());
    }
}
