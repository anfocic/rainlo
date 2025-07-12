<?php

namespace App\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;

class TaxCalculationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'annual_income' => [
                'required',
                'numeric',
                'min:0',
                'max:10000000',
            ],
            'marital_status' => [
                'required',
                'string',
                'in:single,married,single_parent',
            ],
            'has_children' => [
                'sometimes',
                'boolean',
            ],
            'spouse_income' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:10000000',
                'required_if:marital_status,married',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'annual_income.required' => 'Annual income is required.',
            'annual_income.numeric' => 'Annual income must be a valid number.',
            'annual_income.min' => 'Annual income cannot be negative.',
            'annual_income.max' => 'Annual income cannot exceed €10,000,000.',

            'marital_status.required' => 'Marital status is required.',
            'marital_status.in' => 'Marital status must be one of: single, married, single_parent.',

            'has_children.boolean' => 'Has children must be true or false.',

            'spouse_income.numeric' => 'Spouse income must be a valid number.',
            'spouse_income.min' => 'Spouse income cannot be negative.',
            'spouse_income.max' => 'Spouse income cannot exceed €10,000,000.',
            'spouse_income.required_if' => 'Spouse income is required when marital status is married.',
        ];
    }

    public function attributes(): array
    {
        return [
            'annual_income' => 'annual income',
            'marital_status' => 'marital status',
            'has_children' => 'has children',
            'spouse_income' => 'spouse income',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_children' => $this->boolean('has_children', false),
            'spouse_income' => $this->input('spouse_income') === '' ? null : $this->input('spouse_income'),
        ]);
    }


    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional validation logic
            if ($this->marital_status === 'married' && $this->spouse_income === null) {
                $validator->errors()->add('spouse_income', 'Spouse income is required for married status.');
            }

            // If single_parent, ensure has_children is true
            if ($this->marital_status === 'single_parent' && !$this->has_children) {
                $validator->errors()->add('has_children', 'Single parent status requires having children.');
            }
        });
    }
}
