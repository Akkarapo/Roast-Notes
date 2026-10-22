<?php

namespace App\Http\Requests;

use App\Enums\Grind;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AddToCartRequest extends FormRequest
{
    /** Rare lots in early access: 403 with the reason for anyone without the tier. */
    public function authorize(): Response
    {
        $product = ProductVariant::with('product')->find($this->integer('variant_id'))?->product;

        return $product ? Gate::forUser($this->user())->inspect('purchase', $product) : Response::allow();
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
