<?php

namespace App\Http\Requests\Income;

use Illuminate\Foundation\Http\FormRequest;

class
IncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'date' => 'required|date|before_or_equal:today',
            'is_business' => 'boolean',
            'recurring' => 'boolean',
        ];

        // For updates, make some fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['amount'] = 'sometimes|' . $rules['amount'];
            $rules['date'] = 'sometimes|' . $rules['date'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'The income amount is required.',
            'amount.min' => 'The income amount must be at least $0.01.',
            'amount.max' => 'The income amount cannot exceed $999,999,999.99.',
            'date.required' => 'The income date is required.',
            'date.before_or_equal' => 'The income date cannot be in the future.',
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => 'income amount',
            'source' => 'income source',
            'date' => 'income date',
            'is_recurring' => 'recurring status',
            'recurring_frequency' => 'recurring frequency',
            'recurring_end_date' => 'recurring end date',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_business')) {
            $this->merge([
                'is_business' => filter_var($this->is_business, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }

        if ($this->has('recurring')) {
            $this->merge([
                'recurring' => filter_var($this->recurring, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }

        if ($this->has('amount')) {
            $this->merge([
                'amount' => (float) str_replace(',', '', $this->amount),
            ]);
        }
    }
}
