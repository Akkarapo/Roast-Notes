@use('App\Support\Money')
@if (array_sum(array_column($rows, 'units')) === 0)
    <x-empty-state>{{ __('No sales in this period.') }}</x-empty-state>
@else
    <x-chart.hbars :label="__('Share by roast level')" :rows="array_map(fn ($r) => [
        'label' => $r['roast']->label().' · '.$r['roast']->thaiLabel(),
        'value' => $r['share'],
        'display' => number_format($r['share'], 1).'% · '.number_format($r['units']).' '.__('units').' · '.Money::bahtFromSatang($r['revenue']),
    ], $rows)" data-roast-share />
@endif
