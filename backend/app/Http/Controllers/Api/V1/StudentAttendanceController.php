<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\StudentAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint absensi siswa harian (FR-07). Pencatat = guru/wali kelas pada
 * kelas penugasannya (students.attendance); koreksi disetujui pemegang
 * mandat attendance.correct yang berbeda dari pemohon.
 */
class StudentAttendanceController extends Controller
{
    public function classes(Request $request, StudentAttendanceService $service): JsonResponse
    {
        return response()->json(['data' => $service->classes($request->user())]);
    }

    public function roster(Request $request, StudentAttendanceService $service): JsonResponse
    {
        return response()->json(['data' => $service->roster($request->user(), $request->all())]);
    }

    public function record(Request $request, StudentAttendanceService $service): JsonResponse
    {
        return response()->json(['data' => $service->record($request->user(), $request->all(), $request)]);
    }

    public function recap(Request $request, StudentAttendanceService $service): JsonResponse
    {
        return response()->json(['data' => $service->recap($request->user(), $request->all())]);
    }

    public function corrections(Request $request, StudentAttendanceService $service): JsonResponse
    {
        return response()->json(['data' => $service->corrections($request->user(), $request->all())]);
    }

    public function storeCorrection(Request $request, StudentAttendanceService $service): JsonResponse
    {
        return response()->json(['data' => $service->requestCorrection($request->user(), $request->all(), $request)], 201);
    }

    public function decideCorrection(Request $request, int $correction, StudentAttendanceService $service): JsonResponse
    {
        return response()->json(['data' => $service->decideCorrection($request->user(), $correction, $request->all(), $request)]);
    }
}
