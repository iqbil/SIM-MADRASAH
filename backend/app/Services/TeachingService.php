<?php

namespace App\Services;

use App\Http\Resources\TeachingDataResource;
use App\Models\Meeting;
use App\Models\RealizationRevision;
use App\Models\RealizationVerification;
use App\Models\SubstituteAssignment;
use App\Models\TeachingRealization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Realisasi mengajar (SRS v1.3 FR-06). Satu realisasi per pertemuan (UK);
 * isi diajukan immutable — perubahan membuat revisi baru (BR-04); status
 * pelaksanaan dan verifikasi terpisah (FR-06-03); keputusan verifikasi satu
 * per revisi oleh pemegang mandat yang berbeda dari pengaju (Bab II).
 */
class TeachingService
{
    public function __construct(private AccountManagementService $audit) {}

    private const LOAD = ['meeting.classPeriod.schoolClass', 'meeting.subject', 'meeting.scheduledEmployee.person',
        'actualEmployee.person', 'substituteAssignment', 'submitter.person', 'verification.verifier.person'];

    /** @param array<string, mixed> $data */
    public function saveRevision(User $actor, int $meetingId, array $data, Request $request): RealizationRevision
    {
        return DB::transaction(function () use ($actor, $meetingId, $data, $request): RealizationRevision {
            TeachingDataAccess::authorizeOwn($actor);
            $meeting = Meeting::query()->lockForUpdate()->findOrFail($meetingId);
            TeachingDataAccess::assertMeetingDuty($meeting, $actor);
            $this->assertEligibleMeeting($meeting);

            $employeeId = AcademicDataAccess::employeeId($actor);
            $substitute = SubstituteAssignment::query()->where('meeting_id', $meeting->id)->whereNull('revoked_at')
                ->where('substitute_employee_id', $employeeId)->first();
            $creating = ! TeachingRealization::query()->where('meeting_id', $meeting->id)->exists();
            $values = Validator::make($data, [
                'execution_status' => ['required', 'in:held,not_held'],
                'material' => ['required_if:execution_status,held', 'nullable', 'string', 'max:5000'],
                'actual_jp' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:99'],
                'actual_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
                'actual_started_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
                'actual_ended_at' => ['nullable', 'date_format:Y-m-d H:i:s', 'after:actual_started_at'],
                'nonexecution_reason' => ['required_if:execution_status,not_held', 'nullable', 'string', 'min:10', 'max:1000'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'revision_reason' => [$creating ? 'prohibited' : 'required', 'nullable', 'string', 'min:10', 'max:1000'],
            ])->validate();
            if ($values['execution_status'] === 'not_held') {
                $values['actual_jp'] = 0;
                $values['actual_minutes'] = 0;
            } else {
                $values['nonexecution_reason'] = null;
                $values['actual_minutes'] ??= $meeting->planned_minutes_snapshot;
                $this->validateQuantity($meeting, $values['actual_jp'], $values['actual_minutes']);
            }

            if ($creating) {
                $realization = new TeachingRealization(['meeting_id' => $meeting->id]);
                $realization->forceFill(['created_by' => $actor->id])->save();
                $number = 1;
            } else {
                $realization = TeachingRealization::query()->where('meeting_id', $meeting->id)->lockForUpdate()->firstOrFail();
                $current = $realization->currentRevision()->lockForUpdate()->firstOrFail();
                abort_unless($current->actual_employee_id === $employeeId, 422,
                    'Realisasi pertemuan ini dicatat oleh guru pelaksana lain.');
                // Isi diajukan immutable; revisi baru hanya saat belum diajukan atau ditolak (BR-04).
                abort_unless(in_array($current->verification_status, ['not_submitted', 'rejected'], true), 422,
                    'Revisi hanya dapat dibuat sebelum diajukan atau setelah ditolak. Status saat ini: '.$current->verification_status.'.');
                $number = $current->revision_number + 1;
            }

            $revision = new RealizationRevision([
                'realization_id' => $realization->id, 'revision_number' => $number,
                'actual_employee_id' => $employeeId, 'substitute_assignment_id' => $substitute?->id,
                'execution_status' => $values['execution_status'], 'verification_status' => 'not_submitted',
                'material' => $values['material'] ?? null, 'actual_jp' => $values['actual_jp'],
                'actual_minutes' => $values['actual_minutes'], 'actual_started_at' => $values['actual_started_at'] ?? null,
                'actual_ended_at' => $values['actual_ended_at'] ?? null, 'nonexecution_reason' => $values['nonexecution_reason'] ?? null,
                'notes' => $values['notes'] ?? null, 'revision_reason' => $values['revision_reason'] ?? null,
            ]);
            $revision->forceFill(['created_by' => $actor->id])->save();
            // Pointer komposit current_revision_id diperbarui dalam transaksi yang sama.
            $realization->forceFill(['current_revision_id' => $revision->id])->save();
            $this->audit->audit($actor, 'teaching.realization_saved', 'realization_revision', $revision->id,
                $values['revision_reason'] ?? 'Pencatatan realisasi pertemuan terjadwal.', null,
                ['meeting_id' => $meeting->id, 'revision_number' => $number, 'execution_status' => $values['execution_status']], $request);

            return $revision->fresh(self::LOAD);
        });
    }

    /** @param array<string, mixed> $data */
    public function submitRevision(User $actor, int $revisionId, array $data, Request $request): RealizationRevision
    {
        return DB::transaction(function () use ($actor, $revisionId, $data, $request): RealizationRevision {
            TeachingDataAccess::authorizeOwn($actor);
            $values = Validator::make($data, ['reason' => ['required', 'string', 'min:10', 'max:1000']])->validate();
            $revision = $this->lockCurrentRevision($revisionId);
            $this->assertOwnedRevision($revision, $actor);
            $this->assertEligibleMeeting($revision->realization->meeting);
            if ($revision->execution_status === 'held') {
                $this->validateQuantity($revision->realization->meeting, $revision->actual_jp, $revision->actual_minutes);
            }
            abort_unless($revision->verification_status === 'not_submitted', 422,
                'Revisi ini sudah diajukan atau sudah diputuskan.');
            $revision->forceFill(['verification_status' => 'pending', 'submitted_at' => now('UTC'), 'submitted_by' => $actor->id])->save();
            $this->audit->audit($actor, 'teaching.realization_submitted', 'realization_revision', $revision->id, $values['reason'],
                ['verification_status' => 'not_submitted'], ['verification_status' => 'pending'], $request);

            return $revision->fresh(self::LOAD);
        });
    }

    /** @param array<string, mixed> $data */
    public function decideRevision(User $actor, int $revisionId, array $data, Request $request): RealizationRevision
    {
        return DB::transaction(function () use ($actor, $revisionId, $data, $request): RealizationRevision {
            TeachingDataAccess::authorizeVerify($actor);
            $values = Validator::make($data, [
                'action' => ['required', 'in:approve,reject'],
                'reason' => ['required_if:action,reject', 'nullable', 'string', 'min:10', 'max:1000'],
            ])->validate();
            $revision = $this->lockCurrentRevision($revisionId);
            $meeting = $revision->realization->meeting;
            $mandate = TeachingDataAccess::authorizeVerify($actor, $meeting, $revision->actual_employee_id);
            abort_unless($revision->verification_status === 'pending', 422, 'Revisi ini tidak menunggu keputusan.');
            abort_if($revision->submitted_by === $actor->id, 422, 'Verifikator tidak boleh sama dengan pengaju.');
            if ($values['action'] === 'approve') {
                $this->assertEligibleMeeting($meeting);
                if ($revision->execution_status === 'held') {
                    $this->validateQuantity($meeting, $revision->actual_jp, $revision->actual_minutes);
                }
            }

            $verification = new RealizationVerification([
                'realization_revision_id' => $revision->id, 'action' => $values['action'],
                'mandate_id' => $mandate['id'], 'reason' => $values['reason'] ?? null,
                'operation_key' => 'realization-verification:'.Str::uuid(),
            ]);
            $verification->forceFill(['verifier_user_id' => $actor->id, 'verified_at' => now('UTC')])->save();
            $revision->forceFill(['verification_status' => $values['action'] === 'approve' ? 'approved' : 'rejected'])->save();
            $this->audit->audit($actor, 'teaching.realization_'.$values['action'], 'realization_revision', $revision->id,
                $values['reason'] ?? 'Persetujuan realisasi mengajar.', ['verification_status' => 'pending'],
                ['verification_status' => $revision->verification_status, 'verification_id' => $verification->id], $request);

            return $revision->fresh(self::LOAD);
        });
    }

    /** Riwayat realisasi milik guru pelaksana beserta total JP disetujui. @param array<string, mixed> $params @return array<string, mixed> */
    public function ownHistory(User $actor, array $params, Request $request): array
    {
        TeachingDataAccess::authorizeOwn($actor);
        $employeeId = AcademicDataAccess::employeeId($actor) ?? -1;
        $data = Validator::make($params, [
            'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'verification_status' => ['nullable', 'in:not_submitted,pending,approved,rejected'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ])->validate();
        // Daftar menampilkan revisi terkini per realisasi (kondisi sekarang);
        // riwayat revisi lama tetap tersimpan dan diaudit.
        $latestIds = TeachingRealization::query()->whereNotNull('current_revision_id')->pluck('current_revision_id')->all();
        $query = RealizationRevision::query()->where('actual_employee_id', $employeeId)
            ->whereIn('id', $latestIds)
            ->whereHas('realization.meeting', fn (Builder $m): Builder => $m
                ->when($data['date_from'] ?? null, fn (Builder $q, string $from): Builder => $q->where('meeting_date', '>=', $from))
                ->when($data['date_to'] ?? null, fn (Builder $q, string $to): Builder => $q->where('meeting_date', '<=', $to)))
            ->with(self::LOAD);
        if (isset($data['verification_status'])) {
            $query->where('verification_status', $data['verification_status']);
        }
        $totals = (clone $query)->where('verification_status', 'approved')
            ->where('execution_status', 'held')
            ->whereRaw('actual_jp <= FLOOR(actual_minutes * 1000 / 40) / 1000')
            ->whereHas('meeting', fn (Builder $m): Builder => TeachingDataAccess::scopeEligibleMeetings($m)
                ->whereColumn('realization_revisions.actual_jp', '<=', 'meetings.planned_jp_snapshot'))
            ->selectRaw('COALESCE(SUM(actual_jp),0) as jp, COUNT(*) as meetings')->first();
        $page = $query->orderByDesc('id')->paginate($data['per_page'] ?? 25)->withQueryString();

        $rows = $page->toArray();
        $rows['data'] = TeachingDataResource::collection($page->getCollection())->resolve($request);
        $rows['meta'] = [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ];

        return ['rows' => $rows, 'totals' => ['approved_jp' => (float) $totals->jp, 'approved_meetings' => (int) $totals->meetings]];
    }

    /** Urutan penguncian sama dengan penyimpanan: pertemuan → realisasi → revisi. */
    private function lockCurrentRevision(int $revisionId): RealizationRevision
    {
        $reference = RealizationRevision::query()->findOrFail($revisionId);
        $realizationId = $reference->realization_id;
        $meetingId = TeachingRealization::query()->findOrFail($realizationId)->meeting_id;
        $meeting = Meeting::query()->lockForUpdate()->findOrFail($meetingId);
        $realization = TeachingRealization::query()->lockForUpdate()->findOrFail($realizationId);
        $revision = RealizationRevision::query()->lockForUpdate()->findOrFail($revisionId);
        abort_unless($realization->current_revision_id === $revision->id, 409, 'Revisi ini sudah digantikan. Muat ulang dan gunakan revisi terkini.');
        $realization->setRelation('meeting', $meeting);
        $revision->setRelation('realization', $realization);

        return $revision;
    }

    private function assertEligibleMeeting(Meeting $meeting): void
    {
        abort_unless(TeachingDataAccess::scopeEligibleMeetings(Meeting::query()->whereKey($meeting->id))->exists(), 422,
            'Pertemuan dibatalkan atau terhalang kalender akademik; realisasi tidak dapat diajukan/disetujui.');
    }

    private function validateQuantity(Meeting $meeting, string|int|float $jp, ?int $minutes): void
    {
        $units = (int) round((float) $jp * 1000);
        $maximum = min((int) round((float) $meeting->planned_jp_snapshot * 1000), intdiv(($minutes ?? 0) * 1000, 40));
        if ($units <= 0 || $units > $maximum) {
            throw ValidationException::withMessages(['actual_jp' => 'JP aktual harus lebih dari nol dan tidak melebihi JP rencana atau durasi aktual (1 JP = 40 menit).']);
        }
    }

    /** Revisi hanya dapat dikirim pelaksana yang mencatatnya dan yang masih bertugas pada pertemuan itu. */
    private function assertOwnedRevision(RealizationRevision $revision, User $actor): void
    {
        $meeting = $revision->realization?->meeting;
        abort_unless($meeting !== null, 404);
        $employeeId = AcademicDataAccess::employeeId($actor);
        abort_unless($employeeId !== null && $revision->actual_employee_id === $employeeId, 422,
            'Revisi ini dicatat oleh guru pelaksana lain.');
        TeachingDataAccess::assertMeetingDuty($meeting, $actor);
    }
}
