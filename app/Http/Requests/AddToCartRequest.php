<?php

namespace App\Http\Requests;

use App\Enums\Grind;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', Rule::exists(ProductVariant::class, 'id')],
            'grind' => ['nullable', Rule::enum(Grind::class)],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.CartItem::MAX_QUANTITY],
        ];
    }

    public function attributes(): array
    {
        return ['variant_id' => 'ขนาด', 'grind' => 'การบด', 'quantity' => 'จำนวน'];
    }
}
