<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterImportResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return $this->resource->only(['id', 'requested_by', 'module', 'source_system', 'template_version', 'status', 'operation_key',
            'total_rows', 'valid_rows', 'failed_rows', 'inserted_rows', 'updated_rows', 'atomicity_mode', 'confirmed_by', 'confirmed_at', 'started_at', 'finished_at', 'created_at', 'updated_at']);
    }
}
