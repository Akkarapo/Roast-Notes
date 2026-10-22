<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Reports\DateRange;
use App\Support\Reports\Report;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $range = DateRange::fromRequest($request);

        return view('admin.reports.index', [
            'range' => $range,
            'reports' => collect($this->reports())->map(fn (Report $report) => [
                'report' => $report,
                'data' => $report->data($range),
                'sql' => collect($report->queries($range))->map(fn ($query) => $query->toRawSql())->all(),
            ])->all(),
        ]);
    }

    public function csv(Request $request, string $key): StreamedResponse
    {
        $report = collect($this->reports())->first(fn (Report $r) => $r->key() === $key) ?? abort(404);
        $range = DateRange::fromRequest($request);
        [$headers, $rows] = $report->csv($range);
        $filename = sprintf('roast-notes-%s-%s-%s.csv', $key, $range->start->toDateString(), $range->end->toDateString());

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads Thai as UTF-8
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return list<Report> */
    private function reports(): array
    {
        return array_map(fn (string $class) => app($class), config('roastnotes.reports', []));
    }
}
