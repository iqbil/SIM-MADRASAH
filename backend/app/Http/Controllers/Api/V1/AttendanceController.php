<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceDataResource;
use App\Models\AttendanceCorrection;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendancePolicy;
use App\Models\EmployeeWorkAssignment;
use App\Models\EmployeeWorkDay;
use App\Models\WorkPattern;
use App\Services\AttendanceDataAccess;
use App\Services\AttendanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Endpoint absensi pegawai (FR-05). Aksi mandiri dibatasi pegawai aktif
 * terhubung akun; pengelolaan izin/sakit/cuti/dinas dan koreksi oleh petugas
 * bermandat; rekap dapat dibaca kepala madrasah (Bab II).
 */
class AttendanceController extends Controller
{
    public function status(Request $request, AttendanceService $service): JsonResponse
    {
        return response()->json(['data' => $service->status($request->user())]);
    }

    public function clock(Request $request, AttendanceService $service): JsonResponse
    {
        return response()->json(['data' => $service->clock($request->user(), $request->all(), $request)]);
    }

    public function me(Request $request, AttendanceService $service): JsonResponse
    {
        return response()->json(['data' => $service->ownHistory($request->user(), $request->all())]);
    }

    public function policy(Request $request, AttendanceService $service): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->canSignIn() && ! $user->must_change_password, 403);
        // Pegawai melihat kebijakan untuk aksi mandiri; petugas untuk halaman kelola.
        abort_unless(AttendanceDataAccess::allows($user, 'attendance.manage') || AttendanceDataAccess::employee($user) !== null, 403,
            'Akses kebijakan absensi tidak diberikan untuk peran Anda.');
        $policy = $service->effectivePolicy($user);

        return response()->json(['data' => $policy->only(EmployeeAttendancePolicy::PUBLIC_FIELDS),
            'can_manage' => AttendanceDataAccess::canManagePolicy($user),
            'scheduled' => AttendanceDataAccess::canManagePolicy($user)
                ? EmployeeAttendancePolicy::query()->where('code', $policy->code)->where('effective_from', '>', today('Asia/Jakarta')->toDateString())->orderBy('effective_from')->get()->map(fn (EmployeeAttendancePolicy $item): array => $item->only(EmployeeAttendancePolicy::PUBLIC_FIELDS))->all()
                : [],
        ]);
    }

    public function newPolicyVersion(Request $request, AttendanceService $service): AttendanceDataResource
    {
        return new AttendanceDataResource($service->newPolicyVersion($request->user(), $request->all(), $request));
    }

    public function indexRecords(Request $request): AnonymousResourceCollection
    {
        AttendanceDataAccess::authorizeManage($request->user());
        $data = $request->validate([
            'employee_id' => ['nullable', 'integer'], 'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'], 'presence' => ['nullable', 'in:all,recorded,missing'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $from = $data['date_from'] ?? today('Asia/Jakarta')->toDateString();
        $to = $data['date_to'] ?? $from;
        $query = EmployeeWorkDay::query()->with(['attendance.attendanceStatus', 'attendance.captures', 'employee.person', 'calendarEvent', 'workPatternVersion'])
            ->whereBetween('work_date', [$from, $to])->orderByDesc('work_date')->orderBy('employee_id');
        if (isset($data['employee_id'])) {
            $query->where('employee_id', $data['employee_id']);
        }
        match ($data['presence'] ?? 'all') {
            'recorded' => $query->whereHas('attendance'),
            'missing' => $query->where('is_work_required', true)->whereDoesntHave('attendance'),
            default => null,
        };

        return AttendanceDataResource::collection($query->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function storeRecord(Request $request, AttendanceService $service): JsonResponse
    {
        return (new AttendanceDataResource($service->storeRecord($request->user(), $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function recap(Request $request): JsonResponse
    {
        AttendanceDataAccess::authorizeRead($request->user());
        $data = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d'],
            'employee_id' => ['nullable', 'integer'],
        ]);
        $from = $data['date_from'] ?? now('Asia/Jakarta')->startOfMonth()->toDateString();
        $to = $data['date_to'] ?? now('Asia/Jakarta')->endOfMonth()->toDateString();
        $days = EmployeeWorkDay::query()->with(['attendance.attendanceStatus', 'employee.person'])
            ->whereBetween('work_date', [$from, $to])
            ->when($data['employee_id'] ?? null, fn (Builder $q, int $id): Builder => $q->where('employee_id', $id))
            ->orderBy('employee_id')->orderBy('work_date')->get();
        $summary = [];
        foreach ($days as $day) {
            $key = $day->employee_id;
            $summary[$key] ??= ['employee_id' => $key, 'employee' => ['id' => $key, 'employee_number' => $day->employee?->employee_number,
                'person' => $day->employee?->person?->only(['id', 'full_name'])], 'work_days' => 0, 'hadir' => 0, 'terlambat' => 0,
                'izin' => 0, 'sakit' => 0, 'cuti' => 0, 'dinas_luar' => 0, 'tanpa_catatan' => 0];
            if (! $day->is_work_required) {
                continue;
            }
            $summary[$key]['work_days']++;
            $status = $day->attendance?->attendanceStatus?->code;
            if ($status === null) {
                $summary[$key]['tanpa_catatan']++;

                continue;
            }
            if ($status === 'hadir') {
                $summary[$key]['hadir']++;
                if ((int) ($day->attendance->late_minutes ?? 0) > 0) {
                    $summary[$key]['terlambat']++;
                }
            } elseif (array_key_exists($status, ['izin' => 1, 'sakit' => 1, 'cuti' => 1, 'dinas_luar' => 1])) {
                $summary[$key][$status]++;
            }
        }
        $daily = null;
        if (isset($data['employee_id'])) {
            $daily = $days->map(fn (EmployeeWorkDay $day): array => [
                'work_date' => $day->work_date->toDateString(), 'is_work_required' => $day->is_work_required,
                'status' => $day->attendance?->attendanceStatus?->code,
                'check_in_at' => $day->attendance?->check_in_at?->toIso8601String(), 'check_out_at' => $day->attendance?->check_out_at?->toIso8601String(),
                'late_minutes' => $day->attendance?->late_minutes, 'calendar_event_id' => $day->calendar_event_id,
            ])->values();
        }

        return response()->json(['data' => ['range' => ['from' => $from, 'to' => $to], 'summary' => array_values($summary), 'daily' => $daily]]);
    }

    public function indexCorrections(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        abort_unless(AttendanceDataAccess::allows($user, 'attendance.manage') || AttendanceDataAccess::allows($user, 'attendance.correct'), 403);
        $data = $request->validate(['status' => ['nullable', 'in:pending,applied,rejected'], 'employee_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = AttendanceCorrection::query()
            ->whereNotNull('employee_attendance_id')
            ->with(['employeeAttendance.workDay.employee.person', 'employeeAttendance.attendanceStatus', 'requester.person', 'approver.person'])
            ->orderByDesc('requested_at');
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (isset($data['employee_id'])) {
            $query->whereHas('employeeAttendance.workDay', fn (Builder $q): Builder => $q->where('employee_id', $data['employee_id']));
        }
        AttendanceDataAccess::scopeCorrections($query, $user, false);

        return AttendanceDataResource::collection($query->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function storeCorrection(Request $request, AttendanceService $service): JsonResponse
    {
        return (new AttendanceDataResource($service->requestCorrection($request->user(), $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function decideCorrection(Request $request, int $correction, AttendanceService $service): AttendanceDataResource
    {
        return new AttendanceDataResource($service->decideCorrection($request->user(), $correction, $request->all(), $request));
    }

    public function indexWorkPatterns(Request $request): AnonymousResourceCollection
    {
        AttendanceDataAccess::authorizeManage($request->user());
        $query = WorkPattern::query()->with([
            'versions' => fn ($versions) => $versions->orderByDesc('version_number')->orderBy('day_of_week'),
            'employeeWorkAssignments.employee.person',
        ])->withCount('employeeWorkAssignments')->orderBy('code');

        return AttendanceDataResource::collection($query->paginate($request->integer('per_page', 25))->withQueryString());
    }

    public function storeWorkPattern(Request $request, AttendanceService $service): JsonResponse
    {
        return (new AttendanceDataResource($service->saveWorkPattern($request->user(), null, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function updateWorkPattern(Request $request, int $pattern, AttendanceService $service): AttendanceDataResource
    {
        return new AttendanceDataResource($service->saveWorkPattern($request->user(), $pattern, $request->all(), $request));
    }

    public function newPatternVersion(Request $request, int $pattern, AttendanceService $service): AttendanceDataResource
    {
        return new AttendanceDataResource($service->newPatternVersion($request->user(), $pattern, $request->all(), $request));
    }

    public function assignPattern(Request $request, int $pattern, AttendanceService $service): JsonResponse
    {
        return (new AttendanceDataResource($service->assignPattern($request->user(), $pattern, $request->all(), $request)))->response()->setStatusCode(201);
    }

    public function indexAssignments(Request $request): AnonymousResourceCollection
    {
        AttendanceDataAccess::authorizeManage($request->user());
        $query = EmployeeWorkAssignment::query()->with(['employee.person', 'workPattern'])->orderByDesc('effective_from');

        return AttendanceDataResource::collection($query->paginate($request->integer('per_page', 50))->withQueryString());
    }

    public function generateWorkDays(Request $request, AttendanceService $service): JsonResponse
    {
        return response()->json(['data' => $service->generateWorkDays($request->user(), $request->all(), $request)]);
    }

    public function showCapture(Request $request, int $capture, AttendanceService $service): AttendanceDataResource
    {
        return new AttendanceDataResource($service->capture($request->user(), $capture));
    }

    public function capturePhoto(Request $request, int $capture, AttendanceService $service): Response
    {
        $file = $service->capturePhoto($request->user(), $capture);
        $bytes = Storage::disk($file->storage_disk)->get($file->storage_path);
        abort_unless($bytes !== null, 404, 'Berkas bukti tidak ditemukan.');

        return response($bytes, 200, ['Content-Type' => $file->mime_type, 'Content-Length' => (string) strlen($bytes),
            'Content-Disposition' => 'inline; filename="'.$file->original_name.'"',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function showAttendance(Request $request, int $attendance): AttendanceDataResource
    {
        $user = $request->user();
        $record = EmployeeAttendance::query()->with(['workDay.employee.person', 'workDay.calendarEvent', 'attendanceStatus', 'captures.photoFile'])->findOrFail($attendance);
        if (! AttendanceDataAccess::allows($user, 'attendance.manage')) {
            $employee = AttendanceDataAccess::employee($user);
            abort_unless($employee !== null && $record->workDay?->employee_id === $employee->id, 404);
        }

        return new AttendanceDataResource($record);
    }
}
