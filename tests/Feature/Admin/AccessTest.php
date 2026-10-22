<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/** Every admin.* route — whatever its method — is closed to guests and members. */
class AccessTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<array{string, string}> [method, uri with dummy params] */
    private function adminRoutes(): array
    {
        return collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route) => str_starts_with((string) $route->getName(), 'admin.'))
            ->flatMap(fn (Route $route) => collect($route->methods())->reject(fn ($m) => $m === 'HEAD')
                ->map(fn ($method) => [$method, '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri())]))
            ->values()->all();
    }

    public function test_there_are_admin_routes(): void
    {
        $this->assertNotEmpty($this->adminRoutes());
    }

    public function test_guests_are_sent_to_login(): void
    {
        foreach ($this->adminRoutes() as [$method, $uri]) {
            $this->call($method, $uri)->assertRedirect('/login');
        }
    }

    public function test_members_are_forbidden(): void
    {
        $member = User::factory()->create();
        foreach ($this->adminRoutes() as [$method, $uri]) {
            $this->actingAs($member)->call($method, $uri)->assertForbidden();
        }
    }

    public function test_admin_sees_the_section_menu(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin')
            ->assertOk()->assertSee('data-admin-nav', false)->assertSee('ภาพรวม');
    }
}
