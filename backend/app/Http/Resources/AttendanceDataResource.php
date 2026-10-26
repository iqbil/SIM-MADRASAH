<?php

namespace App\Http\Resources;

use App\Models\AttendanceCorrection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceDataResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $data = $this->resource->attributesToArray();
        foreach (['employee', 'requester', 'approver', 'recorder'] as $relation) {
            if ($this->resource->relationLoaded($relation) && $this->resource->{$relation} !== null) {
                $related = $this->resource->{$relation};
                $data[$relation] = ['id' => $related->id, 'employee_number' => $related->employee_number ?? null,
                    'person' => $related->person?->only(['id', 'full_name'])];
            }
        }
        if ($this->resource->relationLoaded('workPattern') && $this->resource->workPattern !== null) {
            $data['workPattern'] = $this->resource->workPattern->only(['id', 'code', 'name', 'is_active']);
        }
        if ($this->resource->relationLoaded('calendarEvent') && $this->resource->calendarEvent !== null) {
            $data['calendarEvent'] = $this->resource->calendarEvent->only(['id', 'title', 'start_date', 'end_date']);
        }
        if ($this->resource->relationLoaded('attendanceStatus') && $this->resource->attendanceStatus !== null) {
            $data['attendanceStatus'] = $this->resource->attendanceStatus->only(['id', 'domain', 'code', 'name']);
        }
        if ($this->resource->relationLoaded('workDay') && $this->resource->workDay !== null) {
            $day = $this->resource->workDay;
            $data['workDay'] = $day->only(['id', 'employee_id', 'work_date', 'shift_number', 'scheduled_start_at', 'scheduled_end_at',
                'tolerance_minutes_snapshot', 'is_work_required', 'calendar_event_id']);
            if ($day->relationLoaded('employee') && $day->employee !== null) {
                $data['workDay']['employee'] = ['id' => $day->employee->id, 'employee_number' => $day->employee->employee_number,
                    'person' => $day->employee->person?->only(['id', 'full_name'])];
            }
            if ($day->relationLoaded('calendarEvent') && $day->calendarEvent !== null) {
                $data['workDay']['calendarEvent'] = $day->calendarEvent->only(['id', 'title']);
            }
        }
        if ($this->resource instanceof AttendanceCorrection && $this->resource->relationLoaded('employeeAttendance') && $this->resource->employeeAttendance !== null) {
            $attendance = $this->resource->employeeAttendance;
            $data['attendance'] = $attendance->only(['id', 'check_in_at', 'check_out_at', 'late_minutes', 'entry_mode', 'notes', 'lock_version', 'updated_at']);
            if ($attendance->relationLoaded('attendanceStatus') && $attendance->attendanceStatus !== null) {
                $data['attendance']['status'] = $attendance->attendanceStatus->only(['code', 'name']);
            }
        }
        if ($this->resource->relationLoaded('captures')) {
            $data['captures'] = $this->resource->captures->map(fn ($capture): array => $capture->only([
                'id', 'event_type', 'latitude', 'longitude', 'accuracy_meters', 'photo_taken_at', 'location_captured_at', 'recorded_at',
            ]) + ['photo_file_id' => $capture->photo_file_id])->all();
        }
        if ($this->resource->relationLoaded('versions')) {
            $data['versions'] = $this->resource->versions->map(fn ($version): array => $version->only([
                'id', 'version_number', 'day_of_week', 'start_time', 'end_time', 'late_tolerance_minutes', 'effective_from', 'effective_to',
            ]))->all();
        }
        if ($this->resource->relationLoaded('employeeWorkAssignments')) {
            $data['assignments'] = $this->resource->employeeWorkAssignments->map(fn ($assignment): array => [...$assignment->only([
                'id', 'employee_id', 'work_pattern_id', 'effective_from', 'effective_to',
            ]), 'employee' => $assignment->employee?->only(['id', 'employee_number']) + ['person' => $assignment->employee?->person?->only(['id', 'full_name'])]])->all();
        }
        if ($this->resource->relationLoaded('photoFile') && $this->resource->photoFile !== null) {
            $data['photoFile'] = $this->resource->photoFile->only(['id', 'mime_type', 'size_bytes', 'original_name', 'created_at']);
        }

        return $data;
    }
}
