<?php

namespace App\Http\Requests\Accounts;

use App\Services\AccountAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        AccountAccess::authorize($this->user(), $this->isMethod('POST') ? 'accounts.create' : 'accounts.update');

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name')), 'username' => trim((string) $this->input('username')),
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:200'],
            'username' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('users', 'username')->ignore($user)],
            'email' => ['nullable', 'email:rfc', 'max:254', Rule::unique('users', 'email')->ignore($user)],
            'person_id' => [$this->isMethod('POST') ? 'nullable' : 'prohibited', 'integer', Rule::exists('persons', 'id'), Rule::unique('users', 'person_id')],
            'lock_version' => ['sometimes', 'integer', 'min:0'],
            'role_ids' => ['sometimes', 'array', 'max:20'], 'role_ids.*' => ['integer', 'distinct', Rule::exists('roles', 'id')->where('is_active', true)],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
