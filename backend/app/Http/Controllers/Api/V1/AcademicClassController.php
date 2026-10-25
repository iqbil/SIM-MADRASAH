<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicDataResource;
use App\Models\ClassPeriod;
use App\Models\SchoolClass;
use App\Models\StudentEnrollment;
use App\Services\AcademicDataAccess;
use App\Services\AcademicService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AcademicClassController extends Controller
{
    public function indexClasses(Request $request): AnonymousResourceCollection
    {
        AcademicDataAccess::authorize($request->user(), 'classes');
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'grade' => ['nullable', 'in:7,8,9'],
            'is_active' => ['nullable', 'boolean'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = SchoolClass::query()->withCount('periods')->orderBy('grade')->orderBy('code');
        $this->filterClasses($query, $data);
        $scope = AcademicDataAccess::classPeriodScope($request->user());
        if ($scope !== null) {
            $query->whereHas('periods', fn ($q) => $q->whereIn('id', $scope));
        }

        return AcademicDataResource::collection($query->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function storeClass(Request $request, AcademicService $service): JsonResponse
    {
        return (new AcademicDataResource($service->saveClass($request->user(), null, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function updateClass(Request $request, int $class, AcademicService $service): AcademicDataResource
    {
        return new AcademicDataResource($service->saveClass($request->user(), $class, $request->all(), $request));
    }

    public function showClass(Request $request, int $class): AcademicDataResource
    {
        AcademicDataAccess::authorize($request->user(), 'classes');
        $record = SchoolClass::query()->withCount('periods')->findOrFail($class);
        $scope = AcademicDataAccess::classPeriodScope($request->user());
        abort_if($scope !== null && ClassPeriod::query()->whereIn('id', $scope)->where('class_id', $record->id)->doesntExist(), 404);

        return new AcademicDataResource($record);
    }

    public function indexPeriods(Request $request): AnonymousResourceCollection
    {
        AcademicDataAccess::authorize($request->user(), 'classes');
        $data = $request->validate(['academic_year_id' => ['nullable', 'integer'], 'status' => ['nullable', 'in:planned,open,closed'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = ClassPeriod::query()->with(['schoolClass'])->withCount('enrollments')->orderByDesc('academic_year_id')->orderBy('grade_snapshot');
        if (isset($data['academic_year_id'])) {
            $query->where('academic_year_id', $data['academic_year_id']);
        }
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }
        $scope = AcademicDataAccess::classPeriodScope($request->user());
        if ($scope !== null) {
            $query->whereIn('id', $scope);
        }

        return AcademicDataResource::collection($query->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function storePeriod(Request $request, AcademicService $service): JsonResponse
    {
        return (new AcademicDataResource($service->saveClassPeriod($request->user(), null, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function updatePeriod(Request $request, int $period, AcademicService $service): AcademicDataResource
    {
        return new AcademicDataResource($service->saveClassPeriod($request->user(), $period, $request->all(), $request));
    }

    public function showPeriod(Request $request, int $period): AcademicDataResource
    {
        AcademicDataAccess::authorize($request->user(), 'classes');
        $record = ClassPeriod::query()->with(['schoolClass'])->withCount('enrollments')->findOrFail($period);
        $scope = AcademicDataAccess::classPeriodScope($request->user());
        abort_if($scope !== null && ! in_array($record->id, $scope, true), 404);

        return new AcademicDataResource($record);
    }

    public function showEnrollment(Request $request, int $enrollment): AcademicDataResource
    {
        AcademicDataAccess::authorize($request->user(), 'enrollments');
        $query = StudentEnrollment::query()->with(['student.person', 'classPeriod.schoolClass']);
        $record = $query->findOrFail($enrollment);
        $scope = AcademicDataAccess::classPeriodScope($request->user());
        abort_if($scope !== null && ! in_array($record->class_period_id, $scope, true), 404);

        return new AcademicDataResource($record);
    }

    public function indexEnrollments(Request $request): AnonymousResourceCollection
    {
        AcademicDataAccess::authorize($request->user(), 'enrollments');
        $data = $request->validate(['student_id' => ['nullable', 'integer'], 'class_period_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date_format:Y-m-d'], 'active_only' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = StudentEnrollment::query()->with(['student.person', 'classPeriod.schoolClass'])->orderByDesc('effective_from');
        if (isset($data['student_id'])) {
            $query->where('student_id', $data['student_id']);
        }
        if (isset($data['class_period_id'])) {
            $query->where('class_period_id', $data['class_period_id']);
        }
        if (isset($data['date'])) {
            $date = $data['date'];
            $query->where('effective_from', '<=', $date)
                ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>', $date));
        } elseif (($data['active_only'] ?? false) && ! isset($data['student_id'])) {
            $query->whereNull('effective_to');
        }
        AcademicDataAccess::scopeEnrollments($query, $request->user());

        return AcademicDataResource::collection($query->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function storeEnrollment(Request $request, AcademicService $service): JsonResponse
    {
        return (new AcademicDataResource($service->saveEnrollment($request->user(), null, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function updateEnrollment(Request $request, int $enrollment, AcademicService $service): AcademicDataResource
    {
        return new AcademicDataResource($service->saveEnrollment($request->user(), $enrollment, $request->all(), $request));
    }

    public function moveEnrollment(Request $request, AcademicService $service): JsonResponse
    {
        return (new AcademicDataResource($service->moveEnrollment($request->user(), $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function transitionPreview(Request $request, AcademicService $service): JsonResponse
    {
        return response()->json(['data' => $service->transitionPreview($request->user(), $request->all())]);
    }

    public function transitionConfirm(Request $request, AcademicService $service): JsonResponse
    {
        return response()->json(['data' => $service->transitionConfirm($request->user(), $request->all(), $request)]);
    }

    /** @param array<string, mixed> $data */
    private function filterClasses(Builder $query, array $data): void
    {
        if (filled($data['q'] ?? null)) {
            $search = '%'.addcslashes($data['q'], '%_\\').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('code', 'like', $search));
        }
        if (isset($data['grade'])) {
            $query->where('grade', $data['grade']);
        }
        if (isset($data['is_active'])) {
            $query->where('is_active', $data['is_active']);
        }
    }
}
