<x-chart.hbars :label="__('Taste they like vs taste that sells')" :series="[__('% of members who like it (Taste Quiz)'), __('% of units sold in the period')]" :rows="array_map(fn ($r) => [
    'label' => $r['name'],
    'value' => $r['liked'], 'display' => number_format($r['liked'], 1).'% ('.$r['members'].' '.__('people').')',
    'value2' => $r['sold'], 'display2' => number_format($r['sold'], 1).'% ('.number_format($r['units']).' '.__('units').')',
], $rows)" data-taste-vs-sales />
<p class="mt-2 text-xs text-cobalt/60">{{ __('Taste Quiz answers are as members have them today; sales follow the period. A member can like several groups and a bean can sit in several groups.') }}</p>
