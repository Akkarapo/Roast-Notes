<?php

namespace App\Http\Requests;

use App\Enums\BrewMethod;
use App\Enums\RoastLevel;
use App\Models\FlavorCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TasteQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'flavors' => ['required', 'array', 'min:1', 'max:3'],
            'flavors.*' => ['integer', 'distinct', Rule::exists(FlavorCategory::class, 'id')->whereNull('parent_id')],
            'roast' => ['required', Rule::in([...array_column(RoastLevel::cases(), 'value'), 'unsure'])],
            'brew' => ['required', Rule::enum(BrewMethod::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'flavors.required' => 'เลือกรสที่ชอบอย่างน้อย 1 แบบ',
            'flavors.min' => 'เลือกรสที่ชอบอย่างน้อย 1 แบบ',
            'flavors.max' => 'เลือกได้สูงสุด 3 แบบ',
            'roast.required' => 'เลือกระดับคั่ว (หรือ "ไม่แน่ใจ")',
            'brew.required' => 'เลือกวิธีชงที่ใช้บ่อย',
        ];
    }

    /** "unsure" is stored as NULL. */
    public function roastLevel(): ?RoastLevel
    {
        return RoastLevel::tryFrom($this->string('roast')->value());
    }
}
