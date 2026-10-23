<?php

namespace App\Http\Requests\Master;

use App\Services\MasterDataAccess;
use Illuminate\Foundation\Http\FormRequest;

class MasterStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->route('resource')) {
            MasterDataAccess::authorize($this->user(), $this->route('resource'), true, 'status');
        } else {
            $batch = \App\Models\ImportBatche::query()->where('module', 'like', 'master.%')->findOrFail($this->route('batch'));
            MasterDataAccess::authorizeImport($this->user(), substr($batch->module, 7));
        }

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:1000'], 'expected_updated_at' => ['required', 'date'],
            ...($this->route('resource') ? ['is_active' => ['required', 'boolean']] : [])];
    }
}
