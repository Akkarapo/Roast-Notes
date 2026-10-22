<?php

namespace App\Support\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Which flavor notes sell: units of beans carrying each note, plus the five top-level flavor groups. */
class FlavorNotes implements Report
{
    use Queries;

    public const LIMIT = 10;

    public function key(): string
    {
        return 'flavor-notes';
    }

    public function title(): string
    {
        return __('Popular flavor notes');
    }

    public function queries(DateRange $range): array
    {
        return [
            'notes' => $this->soldLines($range)
                ->join('product_flavor_notes', 'product_flavor_notes.product_id', '=', 'products.id')
                ->join('flavor_notes', 'flavor_notes.id', '=', 'product_flavor_notes.flavor_note_id')
                ->groupBy('flavor_notes.id', 'flavor_notes.name')
                ->selectRaw('flavor_notes.id, flavor_notes.name, SUM(order_items.quantity) AS units, COUNT(DISTINCT products.id) AS products')
                ->orderByDesc('units')->orderBy('flavor_notes.name')
                ->limit(self::LIMIT),
            'groups' => $this->unitsByFlavorGroup($range),
        ];
    }

    /**
     * Units sold per top-level flavor group. A line counts once per group even if its bean has two notes
     * in that group (DISTINCT line + group in a derived table, then SUM).
     */
    public function unitsByFlavorGroup(DateRange $range): Builder
    {
        $lineGroups = $this->soldLines($range)
            ->join('product_flavor_notes', 'product_flavor_notes.product_id', '=', 'products.id')
            ->join('flavor_notes', 'flavor_notes.id', '=', 'product_flavor_notes.flavor_note_id')
            ->join('flavor_categories', 'flavor_categories.id', '=', 'flavor_notes.flavor_category_id')
            ->selectRaw('DISTINCT order_items.id AS order_item_id, order_items.quantity, COALESCE(flavor_categories.parent_id, flavor_categories.id) AS group_id');

        return DB::query()->fromSub($lineGroups, 'line_groups')
            ->join('flavor_categories AS flavor_groups', 'flavor_groups.id', '=', 'line_groups.group_id')
            ->groupBy('flavor_groups.id', 'flavor_groups.slug', 'flavor_groups.name')
            ->selectRaw('flavor_groups.id, flavor_groups.slug, flavor_groups.name, SUM(line_groups.quantity) AS units')
            ->orderByDesc('units');
    }

    public function data(DateRange $range): array
    {
        $q = $this->queries($range);
        $units = (int) $this->soldLines($range)->sum('order_items.quantity');

        return [
            'notes' => $q['notes']->get()->map(fn ($r) => ['name' => $r->name, 'units' => (int) $r->units, 'products' => (int) $r->products])->all(),
            'groups' => $q['groups']->get()->map(fn ($r) => ['name' => $r->name, 'units' => (int) $r->units, 'share' => self::percent((int) $r->units, $units)])->all(),
        ];
    }

    public function csv(DateRange $range): array
    {
        $data = $this->data($range);
        $rows = array_merge(
            array_map(fn ($n) => [__('Note'), $n['name'], $n['units'], null], $data['notes']),
            array_map(fn ($g) => [__('Flavor group'), $g['name'], $g['units'], $g['share']], $data['groups']),
        );

        return [[__('Kind'), __('Name'), __('Units'), __('Share of units %')], $rows];
    }
}
