<?php

namespace Tests\Feature\Reports;

use App\Support\Reports\DateRange;
use Illuminate\Http\Request;
use Tests\TestCase;

class DateRangeTest extends TestCase
{
    private function range(array $query): DateRange
    {
        return DateRange::fromRequest(Request::create('/admin/reports', 'GET', $query));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(14, 0));
    }

    public function test_presets(): void
    {
        $this->assertSame(['2026-09-24 00:00:00', '2026-10-23 23:59:59'], $this->range(['range' => '30d'])->bounds());
        $this->assertSame(90, $this->range([])->days());                       // default 90d
        $this->assertSame('2025-11-01', $this->range(['range' => '12m'])->start->toDateString());
        $this->assertSame('2026-01-01', $this->range(['range' => 'ytd'])->start->toDateString());
        $this->assertSame('90d', $this->range(['range' => 'nonsense'])->preset);
    }

    public function test_custom_range_includes_both_days(): void
    {
        $range = $this->range(['from' => '2026-10-01', 'to' => '2026-10-10']);

        $this->assertNull($range->preset);
        $this->assertSame(['2026-10-01 00:00:00', '2026-10-10 23:59:59'], $range->bounds());
        $this->assertSame(10, $range->days());
        $this->assertSame(['from' => '2026-10-01', 'to' => '2026-10-10'], $range->query());
    }

    public function test_bad_custom_ranges_fall_back_to_default(): void
    {
        $this->assertSame('90d', $this->range(['from' => '2026-10-10', 'to' => '2026-10-01'])->preset);   // end before start
        $this->assertSame('90d', $this->range(['from' => '2020-01-01', 'to' => '2026-10-01'])->preset);   // > 3 years
        $this->assertSame('90d', $this->range(['from' => '2026-02-30', 'to' => '2026-03-01'])->preset);   // not a date
        $this->assertSame('90d', $this->range(['from' => "1' OR 1=1", 'to' => '2026-03-01'])->preset);
    }

    public function test_previous_period_and_granularity(): void
    {
        $range = $this->range(['from' => '2026-10-01', 'to' => '2026-10-10']);
        $this->assertSame(['2026-09-21 00:00:00', '2026-09-30 23:59:59'], $range->previous()->bounds());
        $this->assertSame('day', $range->granularity());
        $this->assertSame('month', $this->range(['range' => '90d'])->granularity());
        $this->assertSame('day', $this->range(['range' => '30d'])->granularity());
    }
}
