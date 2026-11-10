<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
            'source_platform' => ['nullable', 'string', 'max:50', Rule::in(array_keys(\App\Support\OrderSources::forManualCreate()))],
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_status' => ['nullable', 'string', Rule::in(['pending', 'paid', 'failed', 'refunded'])],
            'order_status_id' => ['nullable', 'integer', Rule::exists('order_statuses', 'id')],
            'shipping_charge' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'action' => ['nullable', 'string', Rule::in(['save', 'confirm'])],
            'courier_api_id' => [
                'nullable',
                'integer',
                Rule::exists('courier_apis', 'id')->where('is_active', true),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
            'admin_notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.color' => ['nullable', 'string', 'max:100'],
            'items.*.size' => ['nullable', 'string', 'max:100'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.variation_name' => ['nullable', 'string', 'max:255'],
            'items.*.attributes' => ['nullable', 'array'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Please add at least one product to the order.',
            'items.min' => 'Please add at least one product to the order.',
            'customer_name.required' => 'Customer name is required.',
            'customer_phone.required' => 'Customer phone number is required.',
            'customer_address.required' => 'Customer address is required.',
        ];
    }
}
