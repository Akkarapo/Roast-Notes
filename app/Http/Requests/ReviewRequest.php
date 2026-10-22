<?php

namespace App\Http\Requests;

use App\Enums\BrewMethod;
use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Only these three fields are accepted — the order item comes from the route, never from the form. */
class ReviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'brew_method' => ['required', Rule::enum(BrewMethod::class)],
            'comment' => ['nullable', 'string', 'max:'.Review::COMMENT_MAX],
        ];
    }

    public function attributes(): array
    {
        return ['rating' => 'คะแนน', 'brew_method' => 'วิธีชง', 'comment' => 'ความเห็น'];
    }
}
