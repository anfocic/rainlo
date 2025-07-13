<?php

namespace App\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;

class MarginalRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'annual_income' => 'required|numeric|min:0|max:10000000',
            'marital_status' => 'required|string|in:single,married,single_parent',
            'spouse_income' => 'sometimes|nullable|numeric|min:0|max:10000000',
        ];
    }
}
