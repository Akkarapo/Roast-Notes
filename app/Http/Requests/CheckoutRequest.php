<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['recipient_name' => 'ชื่อผู้รับ', 'phone' => 'เบอร์โทร', 'shipping_address' => 'ที่อยู่จัดส่ง', 'note' => 'หมายเหตุ'];
    }

    /** @return array{recipient_name: string, phone: string, shipping_address: string, note: ?string} */
    public function shippingDetails(): array
    {
        return $this->safe()->only(['recipient_name', 'phone', 'shipping_address', 'note']) + ['note' => null];
    }
}
