<?php

namespace App\Support\Reports;

use Illuminate\Database\Query\Builder;

/**
 * One block on the sales report page. The same queries feed the page, the "show SQL" panel and the CSV.
 */
interface Report
{
    /** URL key, e.g. "top-products" (also the partial name admin.reports.partials.<key>) */
    public function key(): string;

    public function title(): string;

    /** @return array<string, Builder> named queries that are actually run */
    public function queries(DateRange $range): array;

    /** View data for the partial. */
    public function data(DateRange $range): array;

    /** @return array{0: list<string>, 1: iterable<list<scalar|null>>} headers + rows */
    public function csv(DateRange $range): array;
}
