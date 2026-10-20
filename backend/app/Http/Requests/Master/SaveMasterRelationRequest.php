<?php

namespace App\Http\Requests\Master;

use App\Services\MasterDataAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMasterRelationRequest extends FormRequest
{
    public function authorize(): bool
    {
        MasterDataAccess::authorize($this->user(), 'relations', true);

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = ['reason' => ['required', 'string', 'min:10', 'max:1000'], 'expected_updated_at' => ['required', 'date']];
        if ($this->route('link')) {
            $rules['expected_link_updated_at'] = ['required', 'date'];

            return [...$rules, ...match ($this->route('action')) {
                'verify' => ['identity_confirmation' => ['required', 'accepted']],
                'end' => ['effective_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.today('Asia/Jakarta')->toDateString()]],
                default => [],
            }];
        }
        $rules = [...$rules, 'effective_from' => ['required', 'date_format:Y-m-d'], 'effective_to' => ['nullable', 'date_format:Y-m-d', 'after:effective_from']];

        return [...$rules, ...match ($this->route('relation')) {
            'guardian-links' => ['guardian_id' => ['required', 'integer', Rule::exists('guardians', 'id')->where('is_active', true)],
                'relationship' => ['required', Rule::in(['father', 'mother', 'legal_guardian', 'other'])], 'is_primary' => ['required', 'boolean']],
            'categories' => ['category_id' => ['required', 'integer', Rule::exists('employee_categories', 'id')->where('is_active', true)]],
            'positions' => ['position_id' => ['required', 'integer', Rule::exists('positions', 'id')->where('is_active', true)], 'appointment_number' => ['nullable', 'string', 'max:100']],
            default => [],
        }];
    }
}
