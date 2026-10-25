<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicDataResource;
use App\Models\HomeroomAssignment;
use App\Models\TeachingAssignment;
use App\Services\AcademicDataAccess;
use App\Services\AcademicService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AcademicAssignmentController extends Controller
{
    public function indexTeaching(Request $request): AnonymousResourceCollection
    {
        AcademicDataAccess::authorize($request->user(), 'assignments');
        $data = $request->validate(['semester_id' => ['nullable', 'integer'], 'class_period_id' => ['nullable', 'integer'],
            'employee_id' => ['nullable', 'integer'], 'subject_id' => ['nullable', 'integer'], 'active_only' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = TeachingAssignment::query()->with(['employee.person', 'subject', 'classPeriod.schoolClass', 'semester.academicYear'])->orderByDesc('effective_from');
        foreach (['semester_id', 'class_period_id', 'employee_id', 'subject_id'] as $filter) {
            if (isset($data[$filter])) {
                $query->where($filter, $data[$filter]);
            }
        }
        if (($data['active_only'] ?? false) && ! isset($data['employee_id'])) {
            $today = today('Asia/Jakarta')->toDateString();
            $query->where('is_active', true)->where('effective_from', '<=', $today)
                ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>', $today));
        }
        AcademicDataAccess::scopeAssignments($query, $request->user());

        return AcademicDataResource::collection($query->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function storeTeaching(Request $request, AcademicService $service): JsonResponse
    {
        return (new AcademicDataResource($service->saveTeachingAssignment($request->user(), null, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function updateTeaching(Request $request, int $assignment, AcademicService $service): AcademicDataResource
    {
        return new AcademicDataResource($service->saveTeachingAssignment($request->user(), $assignment, $request->all(), $request));
    }

    public function showTeaching(Request $request, int $assignment): AcademicDataResource
    {
        AcademicDataAccess::authorize($request->user(), 'assignments');
        $record = TeachingAssignment::query()->with(['employee.person', 'subject', 'classPeriod.schoolClass', 'semester.academicYear'])->findOrFail($assignment);
        $scope = AcademicDataAccess::classPeriodScope($request->user());
        abort_if($scope !== null && ! in_array($record->class_period_id, $scope, true), 404);

        return new AcademicDataResource($record);
    }

    public function indexHomeroom(Request $request): AnonymousResourceCollection
    {
        AcademicDataAccess::authorize($request->user(), 'assignments');
        $data = $request->validate(['class_period_id' => ['nullable', 'integer'], 'semester_id' => ['nullable', 'integer'],
            'employee_id' => ['nullable', 'integer'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = HomeroomAssignment::query()->with(['employee.person', 'classPeriod.schoolClass', 'semester'])->orderByDesc('effective_from');
        foreach (['class_period_id', 'semester_id', 'employee_id'] as $filter) {
            if (isset($data[$filter])) {
                $query->where($filter, $data[$filter]);
            }
        }
        AcademicDataAccess::scopeAssignments($query, $request->user());

        return AcademicDataResource::collection($query->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function storeHomeroom(Request $request, AcademicService $service): JsonResponse
    {
        return (new AcademicDataResource($service->saveHomeroomAssignment($request->user(), null, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function updateHomeroom(Request $request, int $assignment, AcademicService $service): AcademicDataResource
    {
        return new AcademicDataResource($service->saveHomeroomAssignment($request->user(), $assignment, $request->all(), $request));
    }

    public function showHomeroom(Request $request, int $assignment): AcademicDataResource
    {
        AcademicDataAccess::authorize($request->user(), 'assignments');
        $record = HomeroomAssignment::query()->with(['employee.person', 'classPeriod.schoolClass', 'semester'])->findOrFail($assignment);
        $scope = AcademicDataAccess::classPeriodScope($request->user());
        abort_if($scope !== null && ! in_array($record->class_period_id, $scope, true), 404);

        return new AcademicDataResource($record);
    }
}
