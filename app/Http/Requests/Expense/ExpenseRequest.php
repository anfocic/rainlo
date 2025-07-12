<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
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
            'vendor' => 'nullable|string|max:255',
            'receipt_url' => 'nullable|string|max:500',
            'tax_deductible' => 'boolean',
            'tax_category' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['amount'] = 'sometimes|' . $rules['amount'];
            $rules['date'] = 'sometimes|' . $rules['date'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'The expense amount is required.',
            'amount.min' => 'The expense amount must be at least $0.01.',
            'amount.max' => 'The expense amount cannot exceed $999,999,999.99.',
            'date.required' => 'The expense date is required.',
            'date.before_or_equal' => 'The expense date cannot be in the future.',
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => 'expense amount',
            'date' => 'expense date',
            'is_business' => 'business expense status',
            'recurring' => 'recurring status',
        ];
    }

    //cleans data from frontend for validation
    protected function prepareForValidation(): void
    {
        // Convert string boolean to actual boolean
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

        if ($this->has('tax_deductible')) {
            $this->merge([
                'tax_deductible' => filter_var($this->tax_deductible, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }

        // Ensure amount is properly formatted
        if ($this->has('amount')) {
            $this->merge([
                'amount' => (float) str_replace(',', '', $this->amount),
            ]);
        }
    }
}
