<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public guest checkout allowed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'street' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'country' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['required', 'string', 'in:COD,UPI,Razorpay,CARD'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'Please provide your full name.',
            'customer_phone.required' => 'Please provide a 10-digit mobile number.',
            'customer_phone.regex' => 'The mobile number must be a valid 10-digit Indian phone number.',
            'pincode.regex' => 'The pincode must be a valid 6-digit postal code.',
            'items.required' => 'Your cart is empty. Please add items before checking out.',
            'items.*.product_id.exists' => 'One or more items in your cart no longer exist.',
        ];
    }
}
