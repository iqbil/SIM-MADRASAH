<?php

namespace App\Http\Requests\Master;

use App\Services\MasterDataAccess;
use App\Services\MasterDataCatalog;
use Illuminate\Foundation\Http\FormRequest;

class SaveMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        MasterDataAccess::authorize($this->user(), $this->route('resource', 'institution'), true);

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $resource = $this->route('resource', 'institution');
        $model = $resource === 'institution' ? MasterDataCatalog::model($resource)->newQuery()->first() :
            ($this->route('record') ? MasterDataCatalog::model($resource)->newQuery()->findOrFail($this->route('record')) : null);

        return [...MasterDataCatalog::rules($resource, $model),
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'duplicate_confirmation' => ['nullable', 'string', 'min:20', 'max:1000'],
            'expected_updated_at' => [$model ? 'required' : 'prohibited', 'date'],
            'expected_person_updated_at' => [$model && $this->has('person') && MasterDataCatalog::hasPerson($resource) ? 'required' : 'prohibited', 'date'],
            'status_id' => ['prohibited'], 'archived_at' => ['prohibited'], 'is_active' => ['prohibited'],
            'logo_file_id' => ['prohibited'], 'singleton_key' => ['prohibited'],
            ...($model ? ['person_id' => ['prohibited']] : []),
        ];
    }
}
