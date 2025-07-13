<?php

namespace App\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;

class ScenarioRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scenarios' => 'required|array|min:1|max:5',
            'scenarios.*annual_income' => 'required|numeric|min:0|max:10000000',
            'scenarios.*marital_status' => 'required|string|in:single,married,single_parent',
            'scenarios.*has_children' => 'sometimes|boolean',
            'scenarios.*spouse_income' => 'required|nullable|min:0|max:10000000',
            'scenarios.*.label' => 'sometimes|string|max:50',

        ];
    }
}
