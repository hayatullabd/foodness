<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_address' => ['required', 'string'],
            'delivery_division' => ['nullable', 'string', 'max:255'],
            'delivery_district' => ['nullable', 'string', 'max:255'],
            'delivery_upazila' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_status' => ['nullable', 'string', Rule::in(['pending', 'paid', 'failed', 'refunded'])],
            'shipping_charge' => ['required', 'numeric', 'min:0'],
            'discount' => ['required', 'numeric', 'min:0'],
            'action' => ['nullable', 'string', Rule::in(['save', 'confirm', 'send_to_courier'])],
            'courier_api_id' => [
                'nullable',
                'required_if:action,send_to_courier',
                'integer',
                Rule::exists('courier_apis', 'id')->where('is_active', true),
            ],
            'notes' => ['nullable', 'string', 'max:350'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer', 'exists:order_items,id'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.color' => ['nullable', 'string', 'max:100'],
            'items.*.size' => ['nullable', 'string', 'max:100'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.variation_name' => ['nullable', 'string', 'max:255'],
            'items.*.attributes' => ['nullable', 'array'],
            'items.*.qty' => ['required_with:items', 'integer', 'min:1'],
            'items.*.price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
