<?php

namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBackOffice() ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['completed', 'cancelled'])],
            'fulfillment' => $this->routeIs('sales.index') ? ['nullable', Rule::in(array_keys(Sale::FULFILLMENT_LABELS))] : ['prohibited'],
            'payment' => $this->routeIs('sales.index') ? ['nullable', Rule::in(['pending', 'paid', 'refunded'])] : ['prohibited'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('from'), ['after_or_equal:from'])],
        ];
    }
}
