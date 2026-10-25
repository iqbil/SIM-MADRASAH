<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicDataResource;
use App\Models\AcademicCalendarEvent;
use App\Services\AcademicDataAccess;
use App\Services\AcademicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AcademicCalendarController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        AcademicDataAccess::authorize($request->user(), 'calendar');
        $data = $request->validate(['academic_year_id' => ['nullable', 'integer'], 'semester_id' => ['nullable', 'integer'],
            'class_period_id' => ['nullable', 'integer'], 'event_type' => ['nullable', 'in:holiday,activity,exam,other'],
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = AcademicCalendarEvent::query()->orderByDesc('start_date');
        foreach (['academic_year_id', 'semester_id', 'class_period_id', 'event_type'] as $filter) {
            if (isset($data[$filter])) {
                $query->where($filter, $data[$filter]);
            }
        }
        if (isset($data['from'])) {
            $query->where('end_date', '>', $data['from']);
        }
        if (isset($data['to'])) {
            $query->where('start_date', '<', $data['to']);
        }

        return AcademicDataResource::collection($query->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function store(Request $request, AcademicService $service): JsonResponse
    {
        return (new AcademicDataResource($service->saveCalendarEvent($request->user(), null, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $event, AcademicService $service): AcademicDataResource
    {
        return new AcademicDataResource($service->saveCalendarEvent($request->user(), $event, $request->all(), $request));
    }

    public function show(Request $request, int $event): AcademicDataResource
    {
        AcademicDataAccess::authorize($request->user(), 'calendar');

        return new AcademicDataResource(AcademicCalendarEvent::query()->findOrFail($event));
    }
}
