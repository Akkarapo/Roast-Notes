<?php

namespace App\Http\Requests\Admin;

use App\Enums\PromotionType;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** One form for both subtypes; the type is fixed once the promotion exists (disjoint specialization). */
class PromotionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'code' => $this->filled('code') ? Coupon::normalize((string) $this->input('code')) : null,
        ]);
    }

    public function type(): PromotionType
    {
        /** @var Promotion|null $promotion */
        $promotion = $this->route('promotion');

        return $promotion?->type ?? PromotionType::tryFrom((string) $this->input('type')) ?? PromotionType::Coupon;
    }

    public function rules(): array
    {
        $promotion = $this->route('promotion');
        $rules = [
            'type' => [$promotion ? 'prohibited' : 'required', Rule::enum(PromotionType::class)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
            'products' => ['required', 'array', 'min:1'],
            'products.*' => ['integer', Rule::exists(Product::class, 'id')],
        ];

        return $rules + ($this->type() === PromotionType::Coupon ? [
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9]+$/', Rule::unique(Coupon::class, 'code')->ignore($promotion?->id, 'promotion_id')],
            'percent_off' => ['required', 'integer', 'between:1,100'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1', 'max:999'],
        ] : [
            'buy_quantity' => ['required', 'integer', 'between:1,100'],
            'free_quantity' => ['required', 'integer', 'between:1,100'],
        ]);
    }

    public function messages(): array
    {
        return ['code.regex' => __('Use only letters A–Z and digits.')];
    }

    public function attributes(): array
    {
        return [
            'name' => 'ชื่อโปร', 'starts_at' => 'วันเริ่ม', 'ends_at' => 'วันสิ้นสุด', 'products' => 'สินค้าร่วมรายการ', 'code' => 'โค้ด',
            'percent_off' => '% ลด', 'min_subtotal' => 'ยอดขั้นต่ำ', 'usage_limit' => 'จำนวนครั้งทั้งหมด', 'per_user_limit' => 'ครั้งต่อคน',
            'buy_quantity' => 'ซื้อ (X)', 'free_quantity' => 'แถม (Y)',
        ];
    }

    public function promotionAttributes(): array
    {
        return $this->safe()->only(['name', 'description', 'starts_at', 'ends_at', 'is_active']);
    }

    public function subtypeAttributes(): array
    {
        return $this->type() === PromotionType::Coupon
            ? $this->safe()->only(['code', 'percent_off', 'min_subtotal', 'usage_limit', 'per_user_limit'])
            : $this->safe()->only(['buy_quantity', 'free_quantity']);
    }
}
