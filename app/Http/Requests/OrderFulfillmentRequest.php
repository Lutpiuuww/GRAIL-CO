<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderFulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBackOffice() ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['processing', 'shipped', 'ready_for_pickup', 'completed'])],
            'version' => ['required', 'integer', 'min:0'],
            'carrier' => ['exclude_unless:action,shipped', 'required', 'string', 'max:100'],
            'tracking_number' => ['exclude_unless:action,shipped', 'required', 'string', 'max:100'],
            'pickup_location' => ['exclude_unless:action,ready_for_pickup', 'required', 'string', 'min:5', 'max:500'],
            'pickup_at' => ['exclude_unless:action,ready_for_pickup', 'required', 'date_format:Y-m-d\TH:i'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['carrier' => 'nama kurir', 'tracking_number' => 'nomor resi', 'pickup_location' => 'lokasi pengambilan', 'pickup_at' => 'jadwal pengambilan'];
    }
}
