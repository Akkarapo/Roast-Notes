<?php

namespace Tests\Feature\Foundation;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'สมชาย ใจดี',
            'email' => 'somchai@example.com',
            'phone' => '0812345678',
            'address' => '239 ถ.ห้วยแก้ว เชียงใหม่ 50200',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    public function test_register_form_shows_phone_and_address_fields(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('name="phone"', false)
            ->assertSee('name="address"', false);
    }

    public function test_member_registers_with_phone_and_address(): void
    {
        $this->post('/register', $this->validPayload());

        $this->assertAuthenticated();
        $user = User::where('email', 'somchai@example.com')->firstOrFail();
        $this->assertSame('0812345678', $user->phone);
        $this->assertSame('239 ถ.ห้วยแก้ว เชียงใหม่ 50200', $user->address);
        $this->assertSame(UserRole::Member, $user->role);
    }

    public function test_phone_and_address_are_optional(): void
    {
        $this->post('/register', $this->validPayload(['phone' => null, 'address' => null]));

        $this->assertAuthenticated();
    }

    public function test_role_in_register_form_is_ignored(): void
    {
        $this->post('/register', $this->validPayload(['role' => 'admin']));

        $this->assertSame(UserRole::Member, User::where('email', 'somchai@example.com')->value('role'));
    }

    public function test_phone_longer_than_20_chars_is_rejected(): void
    {
        $this->post('/register', $this->validPayload(['phone' => str_repeat('1', 21)]))
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_redirects_to_configured_route_after_register(): void
    {
        config(['roastnotes.after_register_route' => 'home']);

        $this->post('/register', $this->validPayload())
            ->assertRedirect(route('home', absolute: false));
    }

    public function test_redirect_target_follows_config_change(): void
    {
        config(['roastnotes.after_register_route' => 'profile.edit']);

        $this->post('/register', $this->validPayload())
            ->assertRedirect(route('profile.edit', absolute: false));
    }
}
