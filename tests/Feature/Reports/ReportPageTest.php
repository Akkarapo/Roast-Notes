<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_all_seven_reports_with_sql_and_csv_on_demo_data(): void
    {
        $this->seed();
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/reports')->assertOk();
        foreach (['summary', 'sales-over-time', 'top-products', 'roast-share', 'flavor-notes', 'discounts', 'taste-vs-sales'] as $key) {
            $response->assertSee('data-report="'.$key.'"', false)->assertSee(route('admin.reports.csv', ['key' => $key, 'range' => '90d']));
        }
        $response->assertSee('data-sql', false)->assertSee('GROUP BY')->assertSee('INNER JOIN')->assertSee('data-chart="bars"', false)
            ->assertDontSee('ไม่มียอดขายในช่วงนี้');
    }

    public function test_query_count_does_not_grow_with_data(): void
    {
        $this->seed();
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->get('/admin/reports?range=30d');

        DB::enableQueryLog();
        $this->actingAs($admin)->get('/admin/reports?range=30d');
        $short = count(DB::getQueryLog());
        DB::flushQueryLog();
        $this->actingAs($admin)->get('/admin/reports?range=12m');

        $this->assertSame($short, count(DB::getQueryLog()));
        $this->assertLessThan(40, $short);
    }

    public function test_csv_download(): void
    {
        $this->seed();
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/reports/top-products.csv?from=2026-01-01&to='.today()->toDateString());
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('roast-notes-top-products-2026-01-01-'.today()->toDateString().'.csv');
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lines = array_values(array_filter(explode("\n", substr($csv, 3))));
        $this->assertSame('อันดับ,สินค้า,ประเภท,ระดับคั่ว,ชิ้น,"ยอดขาย (บาท)"', $lines[0]);
        $this->assertCount(11, $lines); // header + top 10
        $this->assertStringStartsWith('1,', $lines[1]);
    }
}
