<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('login'))) {
            $this->merge(['login' => trim($this->input('login'))]);
        }
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['login' => ['required', 'string', 'max:254'], 'password' => ['required', 'string', 'max:255']];
    }
}
