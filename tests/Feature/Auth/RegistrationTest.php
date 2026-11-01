<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('taste-quiz.edit', absolute: false));
    }

    public function test_email_with_capital_letters_is_saved_in_lowercase(): void
    {
        $this->post('/register', [
            'name' => 'Mixed Case',
            'email' => '  Mixed.Case@Example.COM ',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'mixed.case@example.com']);
    }

    public function test_same_email_in_another_case_is_rejected_as_taken(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register', [
            'name' => 'Dup',
            'email' => 'Taken@Example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');
    }
}
