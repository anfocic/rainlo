<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'email' => 'required|string|lowercase|email|max:50|unique:users',
            'password' => 'required|string|confirmed|min:8',
        ];
    }
}
