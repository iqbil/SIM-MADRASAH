<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RefreshTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['refresh_token' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]{64}\z/']];
    }
}
