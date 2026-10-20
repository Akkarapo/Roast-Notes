<?php

namespace Tests\Feature\Foundation;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_defaults_to_member_role(): void
    {
        $user = User::create([
            'name' => 'ลูกค้า',
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        $this->assertSame(UserRole::Member, $user->fresh()->role);
        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_admin_factory_state_creates_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->isAdmin());
    }

    public function test_role_cannot_be_mass_assigned(): void
    {
        $user = User::create([
            'name' => 'แฮกเกอร์',
            'email' => 'hacker@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->assertSame(UserRole::Member, $user->fresh()->role);
    }

    public function test_phone_and_address_are_stored(): void
    {
        $user = User::factory()->create([
            'phone' => '0812345678',
            'address' => '239 ถ.ห้วยแก้ว ต.สุเทพ อ.เมือง จ.เชียงใหม่ 50200',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '0812345678',
            'role' => 'member',
        ]);
    }
}
