<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return ['cancellation_reason' => ['required', 'string', 'min:5', 'max:1000']];
    }

    public function messages(): array
    {
        return ['cancellation_reason.required' => 'Alasan pembatalan wajib diisi.', 'cancellation_reason.min' => 'Jelaskan alasan pembatalan minimal 5 karakter.'];
    }
}
