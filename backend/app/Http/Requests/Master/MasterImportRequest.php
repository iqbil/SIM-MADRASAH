<?php

namespace App\Http\Requests\Master;

use App\Services\MasterDataAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        MasterDataAccess::authorize($this->user(), 'imports', true);

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['resource' => ['required', Rule::in(['students', 'guardians', 'employees', 'subjects'])],
            'source_system' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'operation_key' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_.:-]+$/'],
            'file' => ['required', 'file', 'max:10240', 'extensions:csv'],
            'reason' => ['required', 'string', 'min:10', 'max:1000']];
    }
}
