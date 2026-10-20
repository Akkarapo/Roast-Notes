<?php

namespace Tests\Feature\Foundation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_open_without_login(): void
    {
        $this->get('/')->assertOk();
        $this->get('/products')->assertOk();
    }

    public function test_member_only_pages_redirect_guests_to_login(): void
    {
        $this->get('/cart')->assertRedirect('/login');
        $this->get('/member')->assertRedirect('/login');
    }

    public function test_member_can_open_member_only_pages(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get('/cart')->assertOk();
        $this->actingAs($member)->get('/member')->assertOk();
    }

    public function test_breeze_dashboard_is_removed(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertNotFound();
    }

    public function test_guest_menu_shows_login_and_register(): void
    {
        $this->get('/')
            ->assertSee(route('login'))
            ->assertSee(route('register'))
            ->assertDontSee(route('cart.index'))
            ->assertDontSee(route('admin.index'));
    }

    public function test_member_menu_shows_cart_and_member_but_not_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertSee(route('cart.index'))
            ->assertSee(route('profile.edit'))
            ->assertSee(route('logout'))
            ->assertDontSee(route('admin.index'));
    }

    public function test_admin_menu_shows_admin_link(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/')
            ->assertSee(route('admin.index'));
    }

    public function test_member_can_update_phone_and_address(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->patch('/member', [
                'name' => $member->name,
                'email' => $member->email,
                'phone' => '0899999999',
                'address' => 'ที่อยู่ใหม่',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/member');

        $member->refresh();
        $this->assertSame('0899999999', $member->phone);
        $this->assertSame('ที่อยู่ใหม่', $member->address);
    }
}
