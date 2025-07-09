<?php

namespace App\Http\Requests\Income;

use Illuminate\Foundation\Http\FormRequest;

class IncomeFilterRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'category' => 'nullable|string|max:100',
            'is_business' => 'nullable|boolean',
            'recurring' => 'nullable|boolean',
            'min' => 'nullable|numeric|min:0',
            'max' => 'nullable|numeric|gte:min',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|in:date,amount,category,description',
            'sort_direction' => 'nullable|string|in:asc,desc',
        ];
    }

    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'The end date must be after or equal to the start date.',
            'amount_max.gte' => 'The maximum amount must be greater than or equal to the minimum amount.',
            'per_page.max' => 'You cannot request more than 100 items per page.',
            'sort_by.in' => 'You can only sort by date, amount, source, or category.',
            'sort_direction.in' => 'Sort direction must be either ascending (asc) or descending (desc).',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Set default sorting
        $this->merge([
            'sort_by' => $this->sort_by ?? 'date',
            'sort_direction' => $this->sort_direction ?? 'desc',
        ]);

        // Convert string boolean to actual boolean
        if ($this->has('is_business')) {
            $this->merge([
                'is_business' => filter_var($this->is_business, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        if ($this->has('recurring')) {
            $this->merge([
                'recurring' => filter_var($this->recurring, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
