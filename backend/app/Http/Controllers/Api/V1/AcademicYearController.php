<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicDataResource;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Services\AcademicDataAccess;
use App\Services\AcademicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AcademicYearController extends Controller
{
    public function indexYears(Request $request): AnonymousResourceCollection
    {
        AcademicDataAccess::authorize($request->user(), 'years');
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:planned,open,closed'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = AcademicYear::query()->withCount('semesters')->orderByDesc('start_date');
        if (filled($data['q'] ?? null)) {
            $search = '%'.addcslashes($data['q'], '%_\\').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('code', 'like', $search));
        }
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        return AcademicDataResource::collection($query->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function storeYear(Request $request, AcademicService $service): JsonResponse
    {
        return (new AcademicDataResource($service->saveYear($request->user(), null, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function showYear(Request $request, int $year): AcademicDataResource
    {
        AcademicDataAccess::authorize($request->user(), 'years');

        return new AcademicDataResource(AcademicYear::query()->withCount('semesters')->findOrFail($year));
    }

    public function updateYear(Request $request, int $year, AcademicService $service): AcademicDataResource
    {
        return new AcademicDataResource($service->saveYear($request->user(), $year, $request->all(), $request));
    }

    public function showSemester(Request $request, int $semester): AcademicDataResource
    {
        AcademicDataAccess::authorize($request->user(), 'years');

        return new AcademicDataResource(Semester::query()->findOrFail($semester));
    }

    public function indexSemesters(Request $request): AnonymousResourceCollection
    {
        AcademicDataAccess::authorize($request->user(), 'years');
        $data = $request->validate(['academic_year_id' => ['required', 'integer'], 'status' => ['nullable', 'in:planned,open,closed'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = Semester::query()->where('academic_year_id', $data['academic_year_id'])->orderBy('number');
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        return AcademicDataResource::collection($query->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function storeSemester(Request $request, AcademicService $service): JsonResponse
    {
        return (new AcademicDataResource($service->saveSemester($request->user(), null, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function updateSemester(Request $request, int $semester, AcademicService $service): AcademicDataResource
    {
        return new AcademicDataResource($service->saveSemester($request->user(), $semester, $request->all(), $request));
    }
}
