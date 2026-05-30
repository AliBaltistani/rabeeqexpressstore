<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user('sanctum');

        return [
            'shippingAddress'              => ['required', 'array'],
            'shippingAddress.firstName'    => ['required', 'string', 'max:100'],
            'shippingAddress.lastName'     => ['required', 'string', 'max:100'],
            'shippingAddress.phone'        => ['required', 'string', 'max:20'],
            'shippingAddress.addressLine1' => ['required', 'string', 'max:500'],
            'shippingAddress.addressLine2' => ['nullable', 'string', 'max:500'],
            'shippingAddress.city'         => ['required', 'string', 'max:100'],
            'shippingAddress.country'      => ['required', 'string', 'max:100'],
            'shippingAddress.state'        => ['nullable', 'string', 'max:100'],
            'shippingAddress.postalCode'   => ['nullable', 'string', 'max:20'],
            'billingAddress'               => ['nullable', 'array'],
            'paymentMethod'                => ['required', 'string', 'in:cod,bank_transfer,stripe,paypal,tamara,tabby,wallet'],
            'shippingRateId'               => ['nullable', 'integer', 'exists:shipping_rates,id'],
            'shippingMethodId'             => ['nullable', 'integer', 'exists:shipping_methods,id'],
            'couponCode'                   => ['nullable', 'string', 'max:50'],
            'notes'                        => ['nullable', 'string', 'max:1000'],
            'currency'                     => ['nullable', 'string', 'max:3'],
            'guestEmail'                   => [$user ? 'nullable' : 'required', 'email', 'max:255'],
            'guestName'                    => ['nullable', 'string', 'max:200'],
            'guestPhone'                   => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shippingAddress.required'             => 'Shipping address is required.',
            'shippingAddress.firstName.required'    => 'First name is required.',
            'shippingAddress.lastName.required'     => 'Last name is required.',
            'shippingAddress.phone.required'        => 'Phone number is required.',
            'shippingAddress.addressLine1.required' => 'Street address is required.',
            'shippingAddress.city.required'         => 'City is required.',
            'shippingAddress.country.required'      => 'Country is required.',
            'paymentMethod.required'               => 'Please select a payment method.',
            'paymentMethod.in'                     => 'Invalid payment method selected.',
            'guestEmail.required'                  => 'Email is required for guest checkout.',
        ];
    }
}
