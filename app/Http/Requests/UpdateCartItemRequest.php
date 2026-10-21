<?php

namespace App\Http\Requests;

use App\Enums\Grind;
use App\Models\CartItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('update', $this->route('item')); // someone else's item answers 404

        return true;
    }

    public function rules(): array
    {
        return [
            'grind' => ['sometimes', Rule::enum(Grind::class)],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:'.CartItem::MAX_QUANTITY],
        ];
    }

    public function attributes(): array
    {
        return ['grind' => 'การบด', 'quantity' => 'จำนวน'];
    }
}
