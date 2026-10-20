<?php

namespace Tests\Feature\Foundation;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed explicitly: the $seed property only runs on the first migration of the suite.
        $this->seed();
    }

    public function test_seeds_one_admin_account(): void
    {
        $admin = User::where('email', 'admin@roastnotes.test')->firstOrFail();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_seeds_three_member_accounts(): void
    {
        foreach (['member1', 'member2', 'member3'] as $name) {
            $member = User::where('email', "{$name}@roastnotes.test")->firstOrFail();

            $this->assertSame(UserRole::Member, $member->role);
            $this->assertNotNull($member->phone);
            $this->assertNotNull($member->address);
        }
    }

    public function test_seeded_admin_can_log_in(): void
    {
        $this->post('/login', ['email' => 'admin@roastnotes.test', 'password' => 'password']);

        $this->assertAuthenticated();
    }
}
