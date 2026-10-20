<?php

namespace App\Http\Requests\Master;

use App\Services\MasterDataAccess;
use Illuminate\Foundation\Http\FormRequest;

class MasterStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        MasterDataAccess::authorize($this->user(), $this->route('resource', 'imports'), true);

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:1000'], 'expected_updated_at' => ['required', 'date'],
            ...($this->route('resource') ? ['is_active' => ['required', 'boolean']] : [])];
    }
}
