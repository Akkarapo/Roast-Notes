<?php

namespace Tests\Feature\Admin;

use App\Enums\BrewMethod;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_search_and_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $good = Review::factory()->create(['rating' => 5, 'brew_method' => BrewMethod::Espresso, 'comment' => 'ช็อกโกแลตชัดมาก']);
        $bad = Review::factory()->create(['rating' => 1, 'brew_method' => BrewMethod::PourOver, 'comment' => 'ข้อความไม่เหมาะสม']);

        $this->actingAs($admin)->get('/admin/reviews')->assertOk()->assertSee('ช็อกโกแลตชัดมาก')->assertSee('ข้อความไม่เหมาะสม');
        $this->actingAs($admin)->get('/admin/reviews?rating=1')->assertSee('ข้อความไม่เหมาะสม')->assertDontSee('ช็อกโกแลตชัดมาก');
        $this->actingAs($admin)->get('/admin/reviews?brew=espresso')->assertSee('ช็อกโกแลตชัดมาก')->assertDontSee('ข้อความไม่เหมาะสม');
        $this->actingAs($admin)->get('/admin/reviews?q=ไม่เหมาะสม')->assertSee('ข้อความไม่เหมาะสม')->assertDontSee('ช็อกโกแลตชัดมาก');

        $this->actingAs($admin)->delete("/admin/reviews/{$bad->id}")->assertSessionHas('flash', 'ลบรีวิวแล้ว');
        $this->assertModelMissing($bad);
        $this->assertModelExists($good);
    }
}
