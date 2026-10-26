<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeachingDataResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $data = $this->resource->attributesToArray();
        if ($this->resource->relationLoaded('meeting') && $this->resource->meeting !== null) {
            $meeting = $this->resource->meeting;
            $data['meeting'] = $meeting->only(['id', 'meeting_date', 'start_time_snapshot', 'end_time_snapshot',
                'planned_jp_snapshot', 'class_period_id', 'subject_id', 'scheduled_employee_id', 'is_cancelled']);
            if ($meeting->relationLoaded('classPeriod') && $meeting->classPeriod !== null) {
                $data['meeting']['classPeriod'] = $meeting->classPeriod->only(['id', 'display_name']);
            }
            if ($meeting->relationLoaded('subject') && $meeting->subject !== null) {
                $data['meeting']['subject'] = $meeting->subject->only(['id', 'code', 'name']);
            }
            if ($meeting->relationLoaded('scheduledEmployee') && $meeting->scheduledEmployee !== null) {
                $data['meeting']['scheduledEmployee'] = ['id' => $meeting->scheduledEmployee->id,
                    'person' => $meeting->scheduledEmployee->person?->only(['id', 'full_name'])];
            }
            if ($meeting->relationLoaded('activeSubstitute')) {
                $substitute = $meeting->activeSubstitute;
                $data['meeting']['activeSubstitute'] = $substitute === null ? null : [
                    'id' => $substitute->id, 'substitute_employee_id' => $substitute->substitute_employee_id,
                    'substitute' => $substitute->relationLoaded('substituteEmployee') && $substitute->substituteEmployee !== null
                        ? ['id' => $substitute->substituteEmployee->id, 'person' => $substitute->substituteEmployee->person?->only(['id', 'full_name'])]
                        : null,
                ];
            }
        }
        foreach (['actualEmployee', 'submitter'] as $relation) {
            if ($this->resource->relationLoaded($relation) && $this->resource->{$relation} !== null) {
                $related = $this->resource->{$relation};
                $data[$relation] = ['id' => $related->id, 'person' => $related->person?->only(['id', 'full_name'])];
            }
        }
        if ($this->resource->relationLoaded('substituteAssignment')) {
            $substitute = $this->resource->substituteAssignment;
            $data['substituteAssignment'] = $substitute === null ? null : $substitute->only(['id', 'substitute_employee_id', 'reason']);
        }
        if ($this->resource->relationLoaded('verification')) {
            $verification = $this->resource->verification;
            $data['verification'] = $verification === null ? null : [...$verification->only(['id', 'action', 'reason', 'verified_at']),
                'verifier' => $verification->relationLoaded('verifier') && $verification->verifier !== null
                    ? ['id' => $verification->verifier->id, 'person' => $verification->verifier->person?->only(['id', 'full_name'])]
                    : null,
            ];
        }
        if ($this->resource->relationLoaded('realization') && $this->resource->realization !== null) {
            $data['realization'] = $this->resource->realization->only(['id', 'meeting_id', 'current_revision_id', 'lock_version']);
        }
        if ($this->resource->relationLoaded('currentRevision') && $this->resource->currentRevision !== null) {
            $data['currentRevision'] = $this->resource->currentRevision->only(['id', 'revision_number', 'execution_status',
                'verification_status', 'actual_jp', 'submitted_at']);
        }

        return $data;
    }
}
