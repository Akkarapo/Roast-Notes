<?php

namespace Tests\Feature\Community;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::factory()->create();
        $this->product = Product::factory()->withVariants()->create(['slug' => 'yirga', 'name' => 'Yirga Washed']);
    }

    public function test_member_saves_and_removes_a_product(): void
    {
        $this->actingAs($this->member)->from('/products/yirga')->post('/wishlist/yirga')
            ->assertRedirect('/products/yirga')->assertSessionHas('flash', 'เก็บ Yirga Washed ไว้ใน Wish List แล้ว');
        $this->actingAs($this->member)->post('/wishlist/yirga'); // twice is harmless
        $this->assertSame([$this->product->id], $this->member->wishlist()->pluck('products.id')->all());

        $this->actingAs($this->member)->get('/products/yirga')->assertSee('data-wishlist-button="saved"', false);

        $this->actingAs($this->member)->delete('/wishlist/yirga')->assertSessionHas('flash');
        $this->assertSame(0, $this->member->wishlist()->count());
        $this->actingAs($this->member)->get('/products/yirga')->assertSee('data-wishlist-button="empty"', false);
    }

    public function test_guest_is_sent_to_login_and_back(): void
    {
        $this->get('/products/yirga')->assertSee('data-wishlist-button="empty"', false);
        $this->from('/products/yirga')->post('/wishlist/yirga')->assertRedirect('/login');
        $this->post('/login', ['email' => $this->member->email, 'password' => 'password'])->assertRedirect('/products/yirga');
    }

    public function test_wishlist_page_and_menu_count(): void
    {
        $other = Product::factory()->withVariants()->create(['name' => 'Huila Washed']);
        $this->member->wishlist()->attach([$this->product->id, $other->id]);
        User::factory()->create()->wishlist()->attach(Product::factory()->create(['name' => 'Not Mine']));

        $this->actingAs($this->member)->get('/wishlist')
            ->assertOk()
            ->assertSee('เก็บไว้ 2 รายการ')
            ->assertSee('Yirga Washed')->assertSee('Huila Washed')->assertDontSee('Not Mine')
            ->assertSee('Wish List (2)');
    }

    public function test_empty_wishlist_invites_to_shop(): void
    {
        $this->get('/wishlist')->assertRedirect('/login');
        $this->actingAs($this->member)->get('/wishlist')->assertSee('ยังไม่มีเมล็ดที่เก็บไว้');
    }

    public function test_hearts_do_not_add_queries_per_card(): void
    {
        Product::factory()->count(6)->withVariants()->create();
        $this->member->wishlist()->attach($this->product);
        $this->actingAs($this->member)->get('/products'); // warm up

        DB::enableQueryLog();
        $this->actingAs($this->member)->get('/products')->assertSee('data-wishlist-button="saved"', false);
        $few = count(DB::getQueryLog());
        Product::factory()->count(5)->withVariants()->create();
        DB::flushQueryLog();
        $this->actingAs($this->member)->get('/products');

        $this->assertSame($few, count(DB::getQueryLog()));
    }
}
