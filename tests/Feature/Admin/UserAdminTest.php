<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\MemberTier;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['name' => 'Admin One']);
    }

    public function test_list_search_and_filters(): void
    {
        $somchai = User::factory()->create(['name' => 'สมชาย ใจดี', 'phone' => '0811111111']);
        User::factory()->tier('barista')->create(['name' => 'สมหญิง รักกาแฟ']);
        Order::factory()->for($somchai)->status(OrderStatus::Delivered)->create(['total' => 1234]);

        $this->actingAs($this->admin)->get('/admin/users')->assertOk()->assertSee('สมชาย ใจดี')->assertSee('฿1,234');
        $this->actingAs($this->admin)->get('/admin/users?q=0811111111')->assertSee('สมชาย ใจดี')->assertDontSee('สมหญิง');
        $this->actingAs($this->admin)->get('/admin/users?tier=barista')->assertSee('สมหญิง')->assertDontSee('สมชาย ใจดี');
        $this->actingAs($this->admin)->get('/admin/users?role=admin')->assertSee('Admin One')->assertDontSee('สมชาย ใจดี');
    }

    public function test_detail_page(): void
    {
        $member = User::factory()->create(['name' => 'ปิติ ชอบดริป']);
        $order = Order::factory()->for($member)->create();

        $this->actingAs($this->admin)->get("/admin/users/{$member->id}")
            ->assertOk()->assertSee('ปิติ ชอบดริป')->assertSee(route('admin.orders.show', $order))->assertSee('ยังไม่ได้ทำ Taste Quiz');
    }

    public function test_set_tier_manually(): void
    {
        $member = User::factory()->create();
        $master = MemberTier::where('slug', 'roaster-master')->value('id');

        $this->actingAs($this->admin)->patch("/admin/users/{$member->id}/tier", ['member_tier_id' => $master])->assertSessionHasNoErrors();
        $this->assertSame('roaster-master', $member->fresh()->memberTier->slug);
        $this->actingAs($this->admin)->patch("/admin/users/{$member->id}/tier", ['member_tier_id' => 99])->assertSessionHasErrors('member_tier_id');
    }

    public function test_make_and_remove_admin_with_guards(): void
    {
        $member = User::factory()->create();

        $this->actingAs($this->admin)->patch("/admin/users/{$member->id}/role", ['role' => 'admin'])->assertSessionHasNoErrors();
        $this->assertSame(UserRole::Admin, $member->fresh()->role);

        $this->actingAs($this->admin)->patch("/admin/users/{$this->admin->id}/role", ['role' => 'member'])->assertSessionHasErrors('role'); // self
        $this->actingAs($this->admin)->patch("/admin/users/{$member->id}/role", ['role' => 'member'])->assertSessionHasNoErrors();
        $this->assertSame(UserRole::Member, $member->fresh()->role);

        // the last admin cannot be removed, even by another admin's request
        $other = User::factory()->admin()->create();
        $this->actingAs($other)->patch("/admin/users/{$this->admin->id}/role", ['role' => 'member'])->assertSessionHasNoErrors();
        $this->actingAs($other)->patch("/admin/users/{$other->id}/role", ['role' => 'member'])->assertSessionHasErrors('role');
        $this->assertSame(1, User::where('role', 'admin')->count());
    }
}
