<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_only(): void
    {
        $this->get('/admin/reports')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/reports')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/admin/reports/top-products.csv')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/reports')->assertOk()->assertSee('data-range', false);
    }

    public function test_unknown_csv_is_not_found(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin/reports/nope.csv')->assertNotFound();
    }

    public function test_dashboard_links_to_the_report(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertSee(route('admin.reports.index'));
    }
}
