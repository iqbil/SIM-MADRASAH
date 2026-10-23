<?php

namespace App\Http\Resources;

use App\Services\MasterDataAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterDataResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (! $request->attributes->has('master_can_manage')) {
            $request->attributes->set('master_can_manage', MasterDataAccess::allows($request->user(), 'students', 'view'));
        }
        $manager = $request->attributes->get('master_can_manage');
        $person = $this->resource->relationLoaded('person') ? $this->resource->person : null;
        if ($this->resource instanceof \App\Models\Student && ! $manager) {
            return ['id' => $this->id, 'internal_code' => $this->internal_code, 'nis' => $this->nis, 'status_id' => $this->status_id,
                'person' => ['full_name' => $person?->full_name, 'sex' => $person?->sex]];
        }
        $data = $this->resource->attributesToArray();
        unset($data['singleton_key'], $data['logo_file_id']);
        if ($person) {
            $data['person'] = $person->only(['id', 'full_name', 'sex', 'birth_place', 'birth_date', 'address', 'phone', 'contact_email', 'is_active', 'updated_at']);
            $data['person']['birth_date'] = $person->birth_date?->toDateString();
        }

        return $data;
    }
}
