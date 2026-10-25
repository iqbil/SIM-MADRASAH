<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AcademicDataAccess;
use App\Services\AcademicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicContextController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        AcademicDataAccess::authorize($request->user(), 'years');

        return response()->json(['data' => app(AcademicService::class)->context()]);
    }

    public function update(Request $request, AcademicService $service): JsonResponse
    {
        return response()->json(['data' => $service->setContext($request->user(), $request->all(), $request)]);
    }
}
