<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicDataResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $data = $this->resource->attributesToArray();
        unset($data['singleton_key']);
        foreach (['activeAcademicYear', 'activeSemester', 'semester', 'schoolClass', 'classPeriod', 'student', 'employee', 'subject', 'scheduledEmployee', 'substituteEmployee'] as $relation) {
            if ($this->resource->relationLoaded($relation) && $this->resource->{$relation} !== null) {
                $related = $this->resource->{$relation};
                $data[$relation] = $related->relationLoaded('person')
                    ? [...$related->only(['id', 'code', 'name', 'internal_code', 'employee_number']), 'person' => $related->person?->only(['id', 'full_name'])]
                    : $related->only(['id', 'academic_year_id', 'class_id', 'code', 'name', 'display_name', 'grade', 'grade_snapshot', 'number', 'start_date', 'end_date', 'status']);
            }
        }
        if ($this->resource->relationLoaded('timetable') && $this->resource->timetable !== null) {
            $data['timetable'] = $this->resource->timetable->only(['id', 'semester_id', 'code', 'name']);
        }
        if ($this->resource->relationLoaded('teachingAssignment') && $this->resource->teachingAssignment !== null) {
            $assignment = $this->resource->teachingAssignment;
            $data['teachingAssignment'] = $assignment->only(['id', 'employee_id', 'subject_id', 'class_period_id', 'semester_id', 'planned_weekly_jp', 'effective_from', 'effective_to', 'is_active']);
            if ($assignment->relationLoaded('employee') && $assignment->employee !== null) {
                $data['teachingAssignment']['employee'] = ['id' => $assignment->employee->id, 'employee_number' => $assignment->employee->employee_number,
                    'person' => $assignment->employee->person?->only(['id', 'full_name'])];
            }
            if ($assignment->relationLoaded('subject') && $assignment->subject !== null) {
                $data['teachingAssignment']['subject'] = $assignment->subject->only(['id', 'code', 'name']);
            }
            if ($assignment->relationLoaded('classPeriod') && $assignment->classPeriod !== null) {
                $data['teachingAssignment']['classPeriod'] = $assignment->classPeriod->only(['id', 'class_id', 'display_name', 'grade', 'number', 'status']);
            }
        }
        if ($this->resource->relationLoaded('activeSubstitute')) {
            $substitute = $this->resource->activeSubstitute;
            $data['activeSubstitute'] = $substitute === null ? null : [...$substitute->only(['id', 'meeting_id', 'substitute_employee_id', 'assigned_at', 'reason']),
                'substitute' => $substitute->relationLoaded('substituteEmployee') && $substitute->substituteEmployee !== null
                    ? ['id' => $substitute->substituteEmployee->id, 'employee_number' => $substitute->substituteEmployee->employee_number,
                        'person' => $substitute->substituteEmployee->person?->only(['id', 'full_name'])]
                    : null,
            ];
        }
        if ($this->resource->relationLoaded('meeting') && $this->resource->meeting !== null) {
            $meeting = $this->resource->meeting;
            $data['meeting'] = $meeting->only(['id', 'meeting_date', 'start_time_snapshot', 'end_time_snapshot', 'planned_jp_snapshot', 'class_period_id', 'subject_id', 'scheduled_employee_id', 'is_cancelled']);
            if ($meeting->relationLoaded('classPeriod') && $meeting->classPeriod !== null) {
                $data['meeting']['classPeriod'] = $meeting->classPeriod->only(['id', 'display_name', 'grade', 'number']);
            }
            if ($meeting->relationLoaded('subject') && $meeting->subject !== null) {
                $data['meeting']['subject'] = $meeting->subject->only(['id', 'code', 'name']);
            }
        }

        return $data;
    }
}
