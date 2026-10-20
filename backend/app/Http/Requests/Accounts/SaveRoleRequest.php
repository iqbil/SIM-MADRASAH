<?php

namespace App\Http\Requests\Accounts;

use App\Services\AccountAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        AccountAccess::authorize($this->user(), 'accounts.roles');

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('roles', 'code')->ignore($this->route('role'))],
            'name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'], 'permission_codes' => ['present', 'array'],
            'permission_codes.*' => ['string', 'distinct', Rule::in(array_keys(AccountAccess::PERMISSIONS))],
            'reason' => ['required', 'string', 'min:10', 'max:1000']];
    }
}
