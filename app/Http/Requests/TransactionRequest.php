<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBackOffice() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items');
        if (is_array($items)) {
            // Ignore completely blank UI rows, but validate half-filled rows.
            $this->merge(['items' => array_filter($items, fn ($item) =>
                ! is_array($item) || filled($item['product_id'] ?? null) || filled($item['quantity'] ?? null)
            )]);
        }
    }

    public function rules(): array
    {
        return [
            'supplier_id' => $this->routeIs('purchases.store') ? ['required', 'exists:suppliers,id'] : ['prohibited'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*' => ['required', 'array:product_id,quantity'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Tambahkan minimal satu produk.',
            'items.*.quantity.required' => 'Isi jumlah untuk setiap produk yang dipilih.',
            'items.*.product_id.required' => 'Pilih produk untuk setiap jumlah yang diisi.',
            'items.*.product_id.exists' => 'Produk tidak tersedia atau sudah diarsipkan.',
            'items.*.product_id.distinct' => 'Gabungkan jumlah produk yang sama dalam satu baris.',
        ];
    }
}
