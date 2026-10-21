<?php

namespace Tests\Feature\TasteProfile;

use App\Enums\BrewMethod;
use App\Enums\RoastLevel;
use App\Models\FlavorCategory;
use App\Models\User;
use Database\Seeders\FlavorTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TasteQuizTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FlavorTaxonomySeeder::class);
        $this->member = User::factory()->create();
    }

    private function ids(string ...$slugs): array
    {
        return FlavorCategory::whereIn('slug', $slugs)->pluck('id')->all();
    }

    private function answer(array $data)
    {
        return $this->actingAs($this->member)->put('/taste-quiz', $data);
    }

    public function test_new_members_land_on_the_quiz_after_registering(): void
    {
        $this->post('/register', ['name' => 'ใหม่', 'email' => 'new@example.com', 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertRedirect(route('taste-quiz.edit', absolute: false));
    }

    public function test_guests_cannot_open_the_quiz(): void
    {
        $this->get('/taste-quiz')->assertRedirect('/login');
    }

    public function test_quiz_shows_three_questions_and_top_level_categories_only(): void
    {
        $this->actingAs($this->member)->get('/taste-quiz')
            ->assertOk()
            ->assertSeeInOrder(['1 / 3', 'Fruity', 'Floral', 'Sweet', 'Nutty/Cocoa', 'Sour/Fermented', '2 / 3', '3 / 3', 'Pour-over'])
            ->assertDontSee('value="'.FlavorCategory::where('slug', 'berry')->value('id').'" x-model', false)
            ->assertSee('ข้ามไปก่อน');
    }

    public function test_saving_answers(): void
    {
        $this->answer(['flavors' => $this->ids('fruity', 'floral'), 'roast' => 'light', 'brew' => 'pour_over'])
            ->assertRedirect('/');

        $this->member->refresh();
        $this->assertEqualsCanonicalizing(['fruity', 'floral'], $this->member->tastePreferences->pluck('slug')->all());
        $this->assertSame(RoastLevel::Light, $this->member->preferred_roast_level);
        $this->assertSame(BrewMethod::PourOver, $this->member->preferred_brew_method);
        $this->assertNotNull($this->member->taste_quiz_completed_at);
        $this->assertTrue($this->member->hasTasteProfile());
    }

    public function test_not_sure_roast_is_stored_as_null(): void
    {
        $this->answer(['flavors' => $this->ids('sweet'), 'roast' => 'unsure', 'brew' => 'espresso'])->assertSessionHasNoErrors();

        $this->assertNull($this->member->fresh()->preferred_roast_level);
    }

    public function test_one_to_three_top_level_flavors_are_required(): void
    {
        $this->answer(['flavors' => [], 'roast' => 'light', 'brew' => 'espresso'])->assertSessionHasErrors('flavors');
        $this->answer(['flavors' => $this->ids('fruity', 'floral', 'sweet', 'nutty-cocoa'), 'roast' => 'light', 'brew' => 'espresso'])->assertSessionHasErrors('flavors');
        $this->answer(['flavors' => $this->ids('berry'), 'roast' => 'light', 'brew' => 'espresso'])->assertSessionHasErrors('flavors.0');
        $this->answer(['flavors' => $this->ids('fruity'), 'roast' => 'burnt', 'brew' => 'kettle'])->assertSessionHasErrors(['roast', 'brew']);

        $this->assertFalse($this->member->fresh()->hasTasteProfile());
    }

    public function test_retaking_the_quiz_replaces_answers_and_prefills_the_form(): void
    {
        $this->answer(['flavors' => $this->ids('fruity', 'floral'), 'roast' => 'light', 'brew' => 'pour_over']);
        $this->answer(['flavors' => $this->ids('nutty-cocoa'), 'roast' => 'dark', 'brew' => 'espresso']);

        $this->assertSame(['nutty-cocoa'], $this->member->fresh()->tastePreferences->pluck('slug')->all());

        $this->actingAs($this->member)->get('/taste-quiz')
            ->assertSee('แก้ไข Taste Profile')
            ->assertSee('value="espresso" x-model="brew" checked', false);
    }
}
