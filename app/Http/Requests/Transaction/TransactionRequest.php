<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'description' => 'required|string|max:1000',
            'category' => 'nullable|string|max:100',
            'date' => 'required|date|before_or_equal:today',
            'is_business' => 'boolean',
            'recurring' => 'boolean',
            'tax_category' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'receipt_url' => 'nullable|string|max:255',
        ];

        if ($this->type === 'expense') {
            $rules['vendor'] = 'nullable|string|max:255';
        }

        if ($this->type === 'income') {
            $rules['source'] = 'nullable|string|max:255';
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['type'] = 'sometimes|' . $rules['type'];
            $rules['amount'] = 'sometimes|' . $rules['amount'];
            $rules['description'] = 'sometimes|' . $rules['description'];
            $rules['date'] = 'sometimes|' . $rules['date'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Transaction type is required.',
            'type.in' => 'Transaction type must be either income or expense.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be at least €0.01.',
            'amount.max' => 'Amount cannot exceed €999,999,999.99.',
            'description.required' => 'Description is required.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'date.required' => 'Date is required.',
            'date.date' => 'Please provide a valid date.',
            'date.before_or_equal' => 'Date cannot be in the future.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'type' => [
                'description' => 'The type of transaction',
                'example' => 'expense',
                'enum' => ['income', 'expense'],
            ],
            'amount' => [
                'description' => 'The transaction amount in euros',
                'example' => 125.50,
            ],
            'description' => [
                'description' => 'A description of the transaction',
                'example' => 'Grocery shopping at SuperValu',
            ],
            'category' => [
                'description' => 'The category of the transaction',
                'example' => 'Food & Dining',
            ],
            'date' => [
                'description' => 'The date of the transaction (YYYY-MM-DD)',
                'example' => '2025-01-13',
            ],
            'is_business' => [
                'description' => 'Whether this is a business-related transaction',
                'example' => false,
            ],
            'recurring' => [
                'description' => 'Whether this is a recurring transaction',
                'example' => false,
            ],
            'tax_category' => [
                'description' => 'Tax category for the transaction',
                'example' => 'Standard Rate',
            ],
            'notes' => [
                'description' => 'Additional notes about the transaction',
                'example' => 'Weekly grocery shopping',
            ],
            'receipt_url' => [
                'description' => 'URL to the receipt image or document',
                'example' => 'https://example.com/receipts/receipt-123.pdf',
            ],
            'vendor' => [
                'description' => 'The vendor/merchant name (for expenses only)',
                'example' => 'SuperValu',
            ],
            'source' => [
                'description' => 'The income source (for income only)',
                'example' => 'Salary - Tech Company Ltd',
            ],
        ];
    }
}
