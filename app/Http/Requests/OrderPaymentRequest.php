<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBackOffice() ?? false;
    }

    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:0'], 'reference' => ['required', 'string', 'min:3', 'max:80'], 'note' => ['nullable', 'string', 'max:500']];
    }
}
