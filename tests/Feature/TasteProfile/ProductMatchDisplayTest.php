<?php

namespace Tests\Feature\TasteProfile;

use App\Enums\BrewMethod;
use App\Enums\RoastLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\TasteProfile\Concerns\BuildsTasteProfiles;
use Tests\TestCase;

class ProductMatchDisplayTest extends TestCase
{
    use BuildsTasteProfiles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTaxonomy();
    }

    public function test_cards_show_match_only_for_members_with_a_profile(): void
    {
        $this->beanWith('Berry Lot', ['Blueberry', 'Caramel']);
        $badge = 'font-script text-[22px]">50%';

        $this->actingAs($this->taster(['fruity']))->get('/products')->assertSee($badge, false);
        $this->actingAs(User::factory()->create())->get('/products')->assertDontSee($badge, false);
        auth()->logout();
        $this->get('/products')->assertDontSee($badge, false);
    }

    public function test_sort_by_match_puts_best_matches_first_then_favourite_roast(): void
    {
        $this->beanWith('Half Dark', ['Lemon', 'Caramel'], ['roast_level' => RoastLevel::Dark]);
        $this->beanWith('Full Match', ['Lemon', 'Jasmine'], ['roast_level' => RoastLevel::Medium]);
        $this->beanWith('Half Light', ['Peach', 'Hazelnut'], ['roast_level' => RoastLevel::Light]);
        $this->beanWith('No Match', ['Hazelnut'], ['roast_level' => RoastLevel::Light]);

        $names = $this->actingAs($this->taster(['fruity', 'floral'], RoastLevel::Light))
            ->get('/products?sort=match')->viewData('products')->pluck('name')->all();

        $this->assertSame(['Full Match', 'Half Light', 'Half Dark', 'No Match'], $names);
    }

    public function test_match_sort_is_hidden_and_ignored_without_a_profile(): void
    {
        $this->beanWith('Any', ['Lemon']);

        $this->actingAs(User::factory()->create())->get('/products?sort=match')
            ->assertOk()
            ->assertDontSee('value="match"', false);
    }

    public function test_match_sort_works_with_filters_and_query_count_stays_flat(): void
    {
        $taster = $this->taster(['fruity']);
        $count = function () use ($taster) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($taster)->get('/products?sort=match&roast[]=medium')->assertOk();

            return count(DB::getQueryLog());
        };

        $this->beanWith('One', ['Lemon'], ['roast_level' => RoastLevel::Medium]);
        $count(); // warm-up: session row and the member's preferences get cached on the first request
        $few = $count();
        foreach (range(2, 10) as $i) {
            $this->beanWith("Bean $i", ['Lemon', 'Caramel'], ['roast_level' => RoastLevel::Medium]);
        }

        $this->assertSame($few, $count());
    }

    public function test_product_page_explains_the_match(): void
    {
        $this->beanWith('Guji', ['Raspberry', 'Earl grey', 'Caramel'], ['roast_level' => RoastLevel::Light]);

        $this->actingAs($this->taster(['fruity', 'floral'], RoastLevel::Light))->get('/products/guji')
            ->assertSee('data-match="67"', false)
            ->assertSee('ตรงกับที่คุณชอบ:')
            ->assertSee('Fruity (Raspberry)')
            ->assertSee('Floral (Earl grey)')
            ->assertSee('คั่วระดับที่คุณชอบ');
    }

    public function test_product_page_invites_members_without_profile_to_the_quiz(): void
    {
        $this->beanWith('Guji', ['Raspberry']);

        $this->actingAs(User::factory()->create())->get('/products/guji')
            ->assertSee('ดูว่าเข้ากับลิ้นคุณไหม')
            ->assertSee(route('taste-quiz.edit'));
    }

    public function test_grind_is_preselected_from_brew_method(): void
    {
        $this->beanWith('Guji', ['Raspberry']);

        $this->actingAs($this->taster(['fruity'], brew: BrewMethod::Espresso))->get('/products/guji')
            ->assertSee('value="espresso" checked', false);
        $this->actingAs($this->taster(['fruity'], brew: BrewMethod::PourOver))->get('/products/guji')
            ->assertSee('value="drip" checked', false);
    }
}
