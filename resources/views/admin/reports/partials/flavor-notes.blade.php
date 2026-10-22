@if (! $notes)
    <x-empty-state>{{ __('No sales in this period.') }}</x-empty-state>
@else
    <div class="grid items-start gap-4 lg:grid-cols-2">
        <div>
            <h3 class="mb-2 text-sm font-semibold">{{ __('Top :n notes (units of beans with the note)', ['n' => count($notes)]) }}</h3>
            <x-chart.hbars :label="__('Popular flavor notes')" :rows="array_map(fn ($n) => [
                'label' => $n['name'], 'value' => $n['units'], 'display' => number_format($n['units']).' · '.__(':n products', ['n' => $n['products']]),
            ], $notes)" data-notes />
        </div>
        <div>
            <h3 class="mb-2 text-sm font-semibold">{{ __('By flavor group (% of units sold)') }}</h3>
            <x-chart.hbars :label="__('By flavor group (% of units sold)')" :rows="array_map(fn ($g) => [
                'label' => $g['name'], 'value' => $g['share'], 'display' => number_format($g['share'], 1).'% · '.number_format($g['units']),
            ], $groups)" data-groups />
            <p class="mt-2 text-xs text-cobalt/60">{{ __('A bean can belong to several groups, so the shares add up to more than 100%.') }}</p>
        </div>
    </div>
@endif
