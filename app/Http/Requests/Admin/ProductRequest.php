<?php

namespace App\Http\Requests\Admin;

use App\Enums\BagSize;
use App\Enums\ProcessMethod;
use App\Enums\ProductType;
use App\Enums\RoastLevel;
use App\Models\FlavorNote;
use App\Models\Origin;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Product + its bag sizes in one form. variants[<size>] = {enabled, price, stock}.
 * Beans sell 100g/250g/500g, drip bags sell sachets.
 */
class ProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', 'alpha_dash', Rule::unique(Product::class)->ignore($product)],
            'nickname' => ['required', 'string', 'max:60'],
            'type' => ['required', Rule::enum(ProductType::class)],
            'origin_id' => ['required', Rule::exists(Origin::class, 'id')],
            'farm_name' => ['nullable', 'string', 'max:150'],
            'variety' => ['nullable', 'string', 'max:60'],
            'process' => ['required', Rule::enum(ProcessMethod::class)],
            'altitude_m' => ['nullable', 'integer', 'between:0,3000'],
            'roast_level' => ['required', Rule::enum(RoastLevel::class)],
            'roast_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'exclusive_until' => ['nullable', 'date'],
            'flavor_notes' => ['required', 'array', 'min:1', 'max:5'],
            'flavor_notes.*' => ['integer', Rule::exists(FlavorNote::class, 'id')],
            'variants' => ['required', 'array'],
            'variants.*.enabled' => ['boolean'],
            'variants.*.price' => ['nullable', 'numeric', 'min:1', 'max:99999'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0', 'max:99999'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $type = ProductType::tryFrom((string) $this->input('type'));
            if (! $type) {
                return;
            }
            $allowed = array_map(fn (BagSize $s) => $s->value, $this->sizesFor($type));
            $enabled = array_keys(array_filter((array) $this->input('variants'), fn ($v) => ! empty($v['enabled'])));

            foreach ($enabled as $size) {
                foreach (['price' => 'ราคา', 'stock' => 'สต็อก'] as $key => $label) {
                    if (blank($this->input("variants.{$size}.{$key}"))) {
                        $validator->errors()->add("variants.{$size}.{$key}", __('Enter :field for :size.', ['field' => $label, 'size' => $size]));
                    }
                }
            }

            if (array_diff($enabled, $allowed)) {
                $validator->errors()->add('variants', __('This size is not sold for :type.', ['type' => $type->label()]));
            } elseif (! $enabled) {
                $validator->errors()->add('variants', __('Turn on at least one size.'));
            }
        }];
    }

    /** @return list<BagSize> */
    public function sizesFor(ProductType $type): array
    {
        return $type === ProductType::DripBag ? [BagSize::Sachet] : BagSize::forBeans();
    }

    /** @return array<string, array{price: string, stock: int}> enabled sizes only, keyed by size value */
    public function enabledVariants(): array
    {
        return collect((array) $this->validated('variants'))
            ->filter(fn ($v) => ! empty($v['enabled']))
            ->map(fn ($v) => ['price' => (string) $v['price'], 'stock' => (int) $v['stock']])
            ->all();
    }

    public function productAttributes(): array
    {
        return $this->safe()->except(['flavor_notes', 'variants']);
    }

    public function attributes(): array
    {
        return [
            'name' => 'ชื่อ', 'slug' => 'slug', 'nickname' => 'ชื่อเล่น', 'type' => 'ประเภท', 'origin_id' => 'แหล่งปลูก',
            'process' => 'กระบวนการ', 'roast_level' => 'ระดับคั่ว', 'roast_date' => 'วันคั่ว', 'flavor_notes' => 'โน้ตรสชาติ',
            'variants.*.price' => 'ราคา', 'variants.*.stock' => 'สต็อก', 'altitude_m' => 'ความสูง',
        ];
    }
}
