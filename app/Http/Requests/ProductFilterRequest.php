<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use App\Enums\RoastLevel;
use App\Models\FlavorCategory;
use App\Models\Origin;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

/**
 * Product list filters. Unknown or malformed values are ignored instead of failing,
 * so a hand-edited URL never shows an error page.
 */
class ProductFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    /**
     * @return array{flavor: list<string>, roast: list<RoastLevel>, type: ?ProductType, country: ?string, promo: bool, sort: string}
     */
    public function filters(): array
    {
        $flavor = $this->strings('flavor');
        $country = is_string($this->query('country')) ? $this->query('country') : null;
        $sort = $this->query('sort');

        return [
            'flavor' => $flavor === [] ? [] : FlavorCategory::topLevel()->whereIn('slug', $flavor)->pluck('slug')->all(),
            'roast' => array_values(array_filter(array_map(RoastLevel::tryFrom(...), $this->strings('roast')))),
            'type' => is_string($this->query('type')) ? ProductType::tryFrom($this->query('type')) : null,
            'country' => $country !== null && Origin::where('country', $country)->exists() ? $country : null,
            'promo' => $this->query('promo') === '1',
            'sort' => in_array($sort, Product::SORTS, true) ? $sort : 'newest',
        ];
    }

    /** @return list<string> */
    private function strings(string $key): array
    {
        return array_values(array_filter(Arr::wrap($this->query($key)), 'is_string'));
    }
}
