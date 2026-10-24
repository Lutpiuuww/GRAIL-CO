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

    public function rules(): array
    {
        $address = ['exclude_if:delivery_method,pickup', 'required', 'string'];

        return [
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9][0-9 ()-]{7,19}$/'],
            'delivery_method' => ['required', Rule::in(array_keys(config('checkout.delivery')))],
            'payment_method' => ['required', Rule::in(array_keys(config('checkout.payment')))],
            'address' => [...$address, 'min:10', 'max:500'],
            'province' => [...$address, Rule::in(IndonesianRegions::provinces())],
            'city' => [...$address, Rule::in(IndonesianRegions::cities($this->input('province')))],
            'postal_code' => ['exclude_if:delivery_method,pickup', 'required', 'regex:/^[0-9]{5}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['recipient_name' => 'nama penerima', 'phone' => 'nomor telepon', 'address' => 'alamat lengkap', 'city' => 'kota/kabupaten', 'province' => 'provinsi', 'postal_code' => 'kode pos', 'delivery_method' => 'metode pengiriman', 'payment_method' => 'metode pembayaran'];
    }
}
