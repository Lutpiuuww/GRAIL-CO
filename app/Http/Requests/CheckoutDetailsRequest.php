<?php

namespace App\Http\Requests;

use App\Support\IndonesianRegions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('address_source')) {
            $this->merge(['address_source' => 'other']);
        }

        if ($this->input('delivery_method') !== 'pickup' && $this->input('address_source') === 'profile') {
            $this->merge([
                'address' => $this->user()?->street_address,
                'province' => $this->user()?->address_province,
                'city' => $this->user()?->address_city,
                'postal_code' => $this->user()?->address_postal_code,
            ]);
        }
    }

    public function rules(): array
    {
        $address = ['exclude_if:delivery_method,pickup', 'required', 'string'];

        return [
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9][0-9 ()-]{7,19}$/'],
            'delivery_method' => ['required', Rule::in(array_keys(config('checkout.delivery')))],
            'payment_method' => ['required', Rule::in(array_keys(config('checkout.payment')))],
            'address_source' => ['exclude_if:delivery_method,pickup', 'required', Rule::in(['profile', 'other']), function ($attribute, $value, $fail) {
                if ($value === 'profile' && ! $this->user()?->hasSavedDeliveryAddress()) {
                    $fail('Alamat utama belum lengkap. Lengkapi profil atau pilih alamat lain.');
                }
            }],
            'address' => [...$address, 'min:10', 'max:500'],
            'province' => [...$address, Rule::in(IndonesianRegions::provinces())],
            'city' => [...$address, Rule::in(IndonesianRegions::cities($this->input('province')))],
            'postal_code' => ['exclude_if:delivery_method,pickup', 'required', 'regex:/^[0-9]{5}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['recipient_name' => 'nama penerima', 'phone' => 'nomor telepon', 'address' => 'alamat lengkap', 'city' => 'kota/kabupaten', 'province' => 'provinsi', 'postal_code' => 'kode pos', 'address_source' => 'pilihan alamat', 'delivery_method' => 'metode pengiriman', 'payment_method' => 'metode pembayaran'];
    }
}
