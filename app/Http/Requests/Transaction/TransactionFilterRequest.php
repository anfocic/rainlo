<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class TransactionFilterRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|in:income,expense',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'category' => 'nullable|string|max:100',
            'is_business' => 'nullable|boolean',
            'recurring' => 'nullable|boolean',
            'min' => 'nullable|numeric|min:0',
            'max' => 'nullable|numeric|min:0|gte:min',
            'vendor' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:date,amount,description,category,created_at',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Type must be either income or expense.',
            'date_from.date' => 'From date must be a valid date.',
            'date_to.date' => 'To date must be a valid date.',
            'date_to.after_or_equal' => 'To date must be after or equal to from date.',
            'min.numeric' => 'Minimum amount must be a number.',
            'max.numeric' => 'Maximum amount must be a number.',
            'max.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
            'sort_by.in' => 'Sort by must be one of: date, amount, description, category, created_at.',
            'sort_direction.in' => 'Sort direction must be either asc or desc.',
            'per_page.integer' => 'Per page must be an integer.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page cannot exceed 100.',
        ];
    }
}
