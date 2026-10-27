<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeachingDataResource;
use App\Models\Meeting;
use App\Models\RealizationRevision;
use App\Models\TeachingRealization;
use App\Services\AcademicDataAccess;
use App\Services\TeachingDataAccess;
use App\Services\TeachingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Endpoint realisasi mengajar (FR-06). Guru pelaksana mengajukan realisasi
 * pertemuan tugasnya; verifikasi oleh pemegang mandat realizations.verify
 * yang berbeda dari pengaju (Bab II).
 */
class TeachingController extends Controller
{
    /** Pertemuan yang menjadi tugas guru: terjadwal atau mengganti. */
    public function indexMeetings(Request $request): AnonymousResourceCollection
    {
        TeachingDataAccess::authorizeOwn($request->user());
        $data = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d'],
            'semester_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = Meeting::query()
            ->with(['classPeriod.schoolClass', 'subject', 'scheduledEmployee.person', 'activeSubstitute.substituteEmployee.person',
                'realization.currentRevision'])
            ->when($data['date_from'] ?? null, fn (Builder $q, string $from): Builder => $q->where('meeting_date', '>=', $from))
            ->when($data['date_to'] ?? null, fn (Builder $q, string $to): Builder => $q->where('meeting_date', '<=', $to))
            ->when($data['semester_id'] ?? null, fn (Builder $q, int $semester): Builder => $q->where('semester_id', $semester));
        TeachingDataAccess::scopeOwnMeetings($query, $request->user());

        return TeachingDataResource::collection($query->orderByDesc('meeting_date')->orderBy('start_time_snapshot')->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function showMeeting(Request $request, int $meeting): TeachingDataResource
    {
        TeachingDataAccess::authorizeOwn($request->user());
        $record = Meeting::query()->with(['classPeriod.schoolClass', 'subject', 'scheduledEmployee.person', 'activeSubstitute.substituteEmployee.person', 'realization.currentRevision'])->findOrFail($meeting);
        TeachingDataAccess::assertMeetingDuty($record, $request->user());

        return new TeachingDataResource($record);
    }

    /** Riwayat realisasi + total JP disetujui milik guru pelaksana. */
    public function ownRealizations(Request $request, TeachingService $service): JsonResponse
    {
        return response()->json(['data' => $service->ownHistory($request->user(), $request->all(), $request)]);
    }

    public function storeRevision(Request $request, int $meeting, TeachingService $service): JsonResponse
    {
        return (new TeachingDataResource($service->saveRevision($request->user(), $meeting, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function submitRevision(Request $request, int $revision, TeachingService $service): TeachingDataResource
    {
        return new TeachingDataResource($service->submitRevision($request->user(), $revision, $request->all(), $request));
    }

    /** Daftar revisi untuk verifikator (bawaan: menunggu keputusan). */
    public function indexVerifications(Request $request): AnonymousResourceCollection
    {
        TeachingDataAccess::authorizeVerify($request->user());
        $data = $request->validate([
            'verification_status' => ['nullable', 'in:not_submitted,pending,approved,rejected'],
            'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $latestIds = TeachingRealization::query()->whereNotNull('current_revision_id')->pluck('current_revision_id');
        $query = RealizationRevision::query()->whereIn('id', $latestIds)
            ->with(['meeting.classPeriod.schoolClass', 'meeting.subject', 'actualEmployee.person', 'submitter.person', 'verification.verifier.person'])
            ->whereHas('realization.meeting', fn (Builder $m): Builder => $m
                ->when($data['date_from'] ?? null, fn (Builder $q, string $from): Builder => $q->where('meeting_date', '>=', $from))
                ->when($data['date_to'] ?? null, fn (Builder $q, string $to): Builder => $q->where('meeting_date', '<=', $to)));
        $query->where('verification_status', $data['verification_status'] ?? 'pending');
        TeachingDataAccess::scopeVerifications($query, $request->user());

        return TeachingDataResource::collection($query->orderByDesc('id')->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function decideRevision(Request $request, int $revision, TeachingService $service): TeachingDataResource
    {
        return new TeachingDataResource($service->decideRevision($request->user(), $revision, $request->all(), $request));
    }

    public function showRevision(Request $request, int $revision): TeachingDataResource
    {
        $user = $request->user();
        $record = RealizationRevision::query()->with(['meeting.classPeriod.schoolClass', 'meeting.subject', 'actualEmployee.person',
            'substituteAssignment', 'submitter.person', 'verification.verifier.person'])->findOrFail($revision);
        $mayVerify = TeachingDataAccess::verifyMandate($user, $record->meeting, $record->actual_employee_id) !== null;
        if (! $mayVerify) {
            TeachingDataAccess::authorizeOwn($user);
            $employeeId = AcademicDataAccess::employeeId($user);
            abort_unless($employeeId !== null && $record->actual_employee_id === $employeeId, 404);
        }

        return new TeachingDataResource($record);
    }
}
