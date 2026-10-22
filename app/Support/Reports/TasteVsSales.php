<?php

namespace App\Support\Reports;

use Illuminate\Support\Facades\DB;

/**
 * For each of the five flavor groups: the share of members who picked it in the Taste Quiz (as they stand today)
 * against the share of units sold in the period that carry a note from it.
 */
class TasteVsSales implements Report
{
    use Queries;

    public function key(): string
    {
        return 'taste-vs-sales';
    }

    public function title(): string
    {
        return __('Taste they like vs taste that sells');
    }

    public function queries(DateRange $range): array
    {
        return [
            'liked' => DB::table('user_taste_preferences')
                ->join('flavor_categories', 'flavor_categories.id', '=', 'user_taste_preferences.flavor_category_id')
                ->groupBy('flavor_categories.id', 'flavor_categories.name')
                ->selectRaw('flavor_categories.id, flavor_categories.name, COUNT(DISTINCT user_taste_preferences.user_id) AS members'),
            'sold' => app(FlavorNotes::class)->unitsByFlavorGroup($range),
        ];
    }

    /** @return list<array{name: string, members: int, liked: float, units: int, sold: float}> */
    public function rows(DateRange $range): array
    {
        $q = $this->queries($range);
        $liked = $q['liked']->get()->keyBy('id');
        $sold = $q['sold']->get()->keyBy('id');
        $members = (int) DB::table('user_taste_preferences')->distinct()->count('user_id');
        $units = (int) $this->soldLines($range)->sum('order_items.quantity');

        return DB::table('flavor_categories')->whereNull('parent_id')->orderBy('id')->get(['id', 'name'])
            ->map(fn ($group) => [
                'name' => $group->name,
                'members' => (int) ($liked[$group->id]->members ?? 0),
                'liked' => self::percent((int) ($liked[$group->id]->members ?? 0), $members),
                'units' => (int) ($sold[$group->id]->units ?? 0),
                'sold' => self::percent((int) ($sold[$group->id]->units ?? 0), $units),
            ])->all();
    }

    public function data(DateRange $range): array
    {
        return ['rows' => $this->rows($range)];
    }

    public function csv(DateRange $range): array
    {
        return [[__('Flavor group'), __('Members who like it'), __('% of members'), __('Units sold'), __('% of units')], array_map(
            fn ($r) => [$r['name'], $r['members'], $r['liked'], $r['units'], $r['sold']], $this->rows($range)
        )];
    }
}
