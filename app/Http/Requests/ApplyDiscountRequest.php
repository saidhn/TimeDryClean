<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('edit orders');
    }

    public function rules(): array
    {
        return [
            'discount_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'discount_value' => 'required|numeric|min:0.01|max:999999.99',
        ];
    }

    public function messages(): array
    {
        return [
            'discount_type.required' => 'Please select a discount type',
            'discount_type.in' => 'Invalid discount type selected',
            'discount_value.required' => 'Please enter a discount value',
            'discount_value.min' => 'Discount value must be greater than 0',
            'discount_value.max' => 'Discount value is too large',
        ];
    }
}
