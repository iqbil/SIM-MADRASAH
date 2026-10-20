<?php

namespace App\Http\Requests\Accounts;

use App\Services\AccountAccess;
use Illuminate\Foundation\Http\FormRequest;

class AccountActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = match ($this->route('action')) {
            'status' => 'accounts.status', 'reset-password' => 'accounts.reset', 'verify-email' => 'accounts.verify', default => 'accounts.sessions'
        };
        AccountAccess::authorize($this->user(), $permission);

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:1000'],
            'status' => ['sometimes', 'required', 'in:active,disabled'],
            'identity_confirmed' => ['sometimes', 'accepted'],
            'lock_version' => ['required', 'integer', 'min:0']];
    }
}
