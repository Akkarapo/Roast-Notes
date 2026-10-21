<?php

namespace Tests\Feature\TasteProfile;

use App\Enums\RoastLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\TasteProfile\Concerns\BuildsTasteProfiles;
use Tests\TestCase;

class RecommendationsTest extends TestCase
{
    use BuildsTasteProfiles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTaxonomy();
    }

    public function test_home_recommends_four_best_matches(): void
    {
        $this->beanWith('Perfect Light', ['Lemon', 'Jasmine'], ['roast_level' => RoastLevel::Light]);
        $this->beanWith('Perfect Dark', ['Peach', 'Rose'], ['roast_level' => RoastLevel::Dark, 'roast_date' => now()->subDays(8)]);
        $this->beanWith('Half Light', ['Lemon', 'Caramel'], ['roast_level' => RoastLevel::Light]);
        $this->beanWith('Third', ['Lemon', 'Caramel', 'Hazelnut'], ['roast_level' => RoastLevel::Medium]);
        $this->beanWith('Quarter', ['Lemon', 'Caramel', 'Hazelnut', 'Rum'], ['roast_level' => RoastLevel::Medium]);
        $this->beanWith('Zero', ['Hazelnut'], ['roast_level' => RoastLevel::Light]);

        $response = $this->actingAs($this->taster(['fruity', 'floral'], RoastLevel::Light))->get('/');

        $this->assertSame(['Perfect Light', 'Perfect Dark', 'Half Light', 'Third'], $response->viewData('forYou')->pluck('name')->all());
        $response->assertSee('แนะนำสำหรับคุณ')->assertSee('Fruity · Floral · Light');
    }

    public function test_skips_zero_matches_hidden_and_sold_out_beans(): void
    {
        $this->beanWith('Good', ['Lemon']);
        $this->beanWith('Zero', ['Hazelnut']);
        $this->beanWith('Hidden', ['Lemon'], ['is_active' => false]);
        $this->beanWith('Sold Out', ['Lemon'])->variants()->update(['stock' => 0]);

        $this->assertSame(['Good'], $this->actingAs($this->taster(['fruity']))->get('/')->viewData('forYou')->pluck('name')->all());
    }

    public function test_members_without_profile_see_the_quiz_prompt_and_menu_dot(): void
    {
        $this->actingAs(User::factory()->create())->get('/')
            ->assertSee('id="taste-quiz-prompt"', false)
            ->assertSee(route('taste-quiz.edit'))
            ->assertSee('data-quiz-dot', false)
            ->assertDontSee('id="for-you"', false);
    }

    public function test_guests_are_invited_to_sign_up_for_the_quiz(): void
    {
        $this->get('/')->assertSee('สมัครสมาชิกแล้วทำ quiz')->assertDontSee('data-quiz-dot', false);
    }

    public function test_members_with_profile_have_no_prompt_or_dot(): void
    {
        $this->beanWith('Good', ['Lemon']);

        $this->actingAs($this->taster(['fruity']))->get('/')
            ->assertDontSee('id="taste-quiz-prompt"', false)
            ->assertDontSee('data-quiz-dot', false);
    }

    public function test_member_page_shows_taste_profile_card(): void
    {
        $this->actingAs($this->taster(['fruity', 'sweet'], RoastLevel::Dark))->get('/member')
            ->assertSee('Taste Profile')
            ->assertSee('Fruity')
            ->assertSee('Dark · คั่วเข้ม')
            ->assertSee('Pour-over');

        $this->actingAs(User::factory()->create())->get('/member')->assertSee('ทำ Taste Quiz');
    }
}
