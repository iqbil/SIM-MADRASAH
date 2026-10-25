<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicDataResource;
use App\Models\LessonSlot;
use App\Models\Meeting;
use App\Models\ScheduleEntryVersion;
use App\Models\SubstituteAssignment;
use App\Models\Timetable;
use App\Models\TimetableVersion;
use App\Services\AcademicDataAccess;
use App\Services\ScheduleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Endpoint jadwal pelajaran, pertemuan, dan guru pengganti (FR-04).
 * Pembacaan dibatasi cakupan guru/wali kelas sesuai Bab II SRS.
 */
class AcademicScheduleController extends Controller
{
    public function indexTimetables(Request $request): AnonymousResourceCollection
    {
        AcademicDataAccess::authorize($request->user(), 'schedules');
        $data = $request->validate(['semester_id' => ['nullable', 'integer'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = Timetable::query()->with('semester.academicYear')->withCount('versions')->orderByDesc('id');
        if (isset($data['semester_id'])) {
            $query->where('semester_id', $data['semester_id']);
        }

        return AcademicDataResource::collection($query->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function storeTimetable(Request $request, ScheduleService $service): JsonResponse
    {
        return (new AcademicDataResource($service->saveTimetable($request->user(), null, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function showTimetable(Request $request, int $timetable): AcademicDataResource
    {
        AcademicDataAccess::authorize($request->user(), 'schedules');
        $record = Timetable::query()->with('semester.academicYear')->withCount('versions')->findOrFail($timetable);

        return new AcademicDataResource($record);
    }

    public function updateTimetable(Request $request, int $timetable, ScheduleService $service): AcademicDataResource
    {
        return new AcademicDataResource($service->saveTimetable($request->user(), $timetable, $request->all(), $request));
    }

    public function indexVersions(Request $request, int $timetable): AnonymousResourceCollection
    {
        AcademicDataAccess::authorize($request->user(), 'schedules');
        Timetable::query()->findOrFail($timetable);
        $query = TimetableVersion::query()->where('timetable_id', $timetable)->withCount('entries')->orderByDesc('version_number');

        return AcademicDataResource::collection($query->paginate($request->integer('per_page', 25))->withQueryString());
    }

    public function storeVersion(Request $request, int $timetable, ScheduleService $service): JsonResponse
    {
        return (new AcademicDataResource($service->saveVersion($request->user(), $timetable, null, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function showVersion(Request $request, int $version): AcademicDataResource|JsonResponse
    {
        AcademicDataAccess::authorize($request->user(), 'schedules');
        $record = TimetableVersion::query()->with('timetable.semester.academicYear')->withCount('entries')->findOrFail($version);
        $this->scopeVersionForUser($record, $request);

        return new AcademicDataResource($record);
    }

    public function updateVersion(Request $request, int $version, ScheduleService $service): AcademicDataResource
    {
        $record = TimetableVersion::query()->findOrFail($version);

        return new AcademicDataResource($service->saveVersion($request->user(), $record->timetable_id, $version, $request->all(), $request));
    }

    public function publishVersion(Request $request, int $version, ScheduleService $service): AcademicDataResource
    {
        return new AcademicDataResource($service->publishVersion($request->user(), $version, $request->all(), $request));
    }

    public function indexEntries(Request $request, int $version): AnonymousResourceCollection
    {
        AcademicDataAccess::authorize($request->user(), 'schedules');
        TimetableVersion::query()->findOrFail($version);
        $query = ScheduleEntryVersion::query()->where('timetable_version_id', $version)
            ->with(['teachingAssignment.employee.person', 'teachingAssignment.subject', 'teachingAssignment.classPeriod.schoolClass', 'slots']);
        AcademicDataAccess::scopeEntryVersions($query, $request->user());

        return AcademicDataResource::collection($query->orderBy('day_of_week')->orderBy('start_time_snapshot')->paginate($request->integer('per_page', 100))->withQueryString());
    }

    public function storeEntry(Request $request, int $version, ScheduleService $service): JsonResponse
    {
        return (new AcademicDataResource($service->saveEntry($request->user(), $version, null, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function updateEntry(Request $request, int $entry, ScheduleService $service): AcademicDataResource
    {
        $record = ScheduleEntryVersion::query()->findOrFail($entry);

        return new AcademicDataResource($service->saveEntry($request->user(), $record->timetable_version_id, $entry, $request->all(), $request));
    }

    public function removeEntry(Request $request, int $entry, ScheduleService $service): JsonResponse
    {
        return response()->json(['data' => $service->removeEntry($request->user(), $entry, $request->all(), $request)]);
    }

    public function indexLessonSlots(Request $request): AnonymousResourceCollection
    {
        AcademicDataAccess::authorize($request->user(), 'schedules');
        $data = $request->validate(['semester_id' => ['required', 'integer'], 'day_of_week' => ['nullable', 'integer', 'between:1,7'], 'is_active' => ['nullable', 'boolean']]);
        $query = LessonSlot::query()->where('semester_id', $data['semester_id'])->orderBy('day_of_week')->orderBy('sequence');
        if (isset($data['day_of_week'])) {
            $query->where('day_of_week', $data['day_of_week']);
        }
        if (isset($data['is_active'])) {
            $query->where('is_active', $data['is_active']);
        }

        return AcademicDataResource::collection($query->paginate(100)->withQueryString());
    }

    public function storeLessonSlot(Request $request, ScheduleService $service): JsonResponse
    {
        return (new AcademicDataResource($service->saveLessonSlot($request->user(), null, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function updateLessonSlot(Request $request, int $slot, ScheduleService $service): AcademicDataResource
    {
        return new AcademicDataResource($service->saveLessonSlot($request->user(), $slot, $request->all(), $request));
    }

    public function indexMeetings(Request $request): AnonymousResourceCollection
    {
        AcademicDataAccess::authorize($request->user(), 'schedules');
        $data = $this->validateMeetingFilters($request);
        $query = $this->meetingQuery();
        foreach (['semester_id', 'class_period_id'] as $filter) {
            if (isset($data[$filter])) {
                $query->where($filter, $data[$filter]);
            }
        }
        if (isset($data['employee_id'])) {
            $query->where(fn (Builder $q): Builder => $q->where('scheduled_employee_id', $data['employee_id'])
                ->orWhereHas('substituteAssignments', fn (Builder $s): Builder => $s->whereNull('revoked_at')->where('substitute_employee_id', $data['employee_id'])));
        }
        if (isset($data['date_from'])) {
            $query->where('meeting_date', '>=', $data['date_from']);
        }
        if (isset($data['date_to'])) {
            $query->where('meeting_date', '<=', $data['date_to']);
        }
        if (isset($data['is_cancelled'])) {
            $query->where('is_cancelled', $data['is_cancelled']);
        }
        AcademicDataAccess::scopeMeetings($query, $request->user());

        return AcademicDataResource::collection($query->orderBy('meeting_date')->orderBy('start_time_snapshot')->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function showMeeting(Request $request, int $meeting): AcademicDataResource
    {
        AcademicDataAccess::authorize($request->user(), 'schedules');
        $record = AcademicDataAccess::scopeMeetings($this->meetingQuery(), $request->user())->findOrFail($meeting);

        return new AcademicDataResource($record);
    }

    public function generateMeetings(Request $request, int $version, ScheduleService $service): JsonResponse
    {
        return response()->json(['data' => $service->generateMeetings($request->user(), $version, $request->all(), $request)]);
    }

    public function cancelMeeting(Request $request, int $meeting, ScheduleService $service): AcademicDataResource
    {
        return new AcademicDataResource($service->cancelMeeting($request->user(), $meeting, $request->all(), $request));
    }

    public function indexSubstitutes(Request $request): AnonymousResourceCollection
    {
        AcademicDataAccess::authorize($request->user(), 'substitutes');
        $data = $request->validate(['employee_id' => ['nullable', 'integer'], 'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'], 'active_only' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = SubstituteAssignment::query()->with(['meeting.classPeriod.schoolClass', 'meeting.subject', 'meeting.scheduledEmployee.person', 'substituteEmployee.person'])
            ->whereHas('meeting', fn (Builder $m): Builder => AcademicDataAccess::scopeMeetings($m, $request->user()));
        if (isset($data['employee_id'])) {
            $query->where('substitute_employee_id', $data['employee_id']);
        }
        if (isset($data['active_only']) && $data['active_only']) {
            $query->whereNull('revoked_at');
        }
        if (isset($data['date_from'])) {
            $query->whereHas('meeting', fn (Builder $m): Builder => $m->where('meeting_date', '>=', $data['date_from']));
        }
        if (isset($data['date_to'])) {
            $query->whereHas('meeting', fn (Builder $m): Builder => $m->where('meeting_date', '<=', $data['date_to']));
        }

        return AcademicDataResource::collection($query->orderByDesc('assigned_at')->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function assignSubstitute(Request $request, int $meeting, ScheduleService $service): JsonResponse
    {
        return (new AcademicDataResource($service->assignSubstitute($request->user(), $meeting, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function revokeSubstitute(Request $request, int $substitute, ScheduleService $service): AcademicDataResource
    {
        return new AcademicDataResource($service->revokeSubstitute($request->user(), $substitute, $request->all(), $request));
    }

    /** @return array<string, mixed> */
    private function validateMeetingFilters(Request $request): array
    {
        return $request->validate(['semester_id' => ['nullable', 'integer'], 'class_period_id' => ['nullable', 'integer'],
            'employee_id' => ['nullable', 'integer'], 'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d'],
            'is_cancelled' => ['nullable', 'boolean'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
    }

    private function meetingQuery(): Builder
    {
        return Meeting::query()->with(['classPeriod.schoolClass', 'subject', 'scheduledEmployee.person', 'semester', 'activeSubstitute.substituteEmployee.person']);
    }

    /** Sembunyikan entri di luar cakupan guru/wali kelas pada versi terbit. */
    private function scopeVersionForUser(TimetableVersion $version, Request $request): void
    {
        if (AcademicDataAccess::classPeriodScope($request->user()) === null) {
            return;
        }
        $scoped = (clone $version->entries()->getQuery());
        AcademicDataAccess::scopeEntryVersions($scoped, $request->user());
        $version->setRelation('entries', $scoped->with('teachingAssignment.employee.person', 'teachingAssignment.subject', 'teachingAssignment.classPeriod.schoolClass')->get());
    }
}
