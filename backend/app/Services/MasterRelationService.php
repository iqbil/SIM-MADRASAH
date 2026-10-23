<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeCategoryAssignment;
use App\Models\EmployeePosition;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterRelationService
{
    public function __construct(private AccountManagementService $audit) {}

    public static function owner(string $relation, int $id, bool $lock = false): Model
    {
        $query = $relation === 'guardian-links' ? Student::query() : Employee::query();

        return ($lock ? $query->lockForUpdate() : $query)->findOrFail($id);
    }

    public static function query(string $relation, int $owner): Builder
    {
        return match ($relation) {
            'guardian-links' => StudentGuardian::query()->where('student_id', $owner),
            'categories' => EmployeeCategoryAssignment::query()->where('employee_id', $owner),
            'positions' => EmployeePosition::query()->where('employee_id', $owner),
        };
    }

    /** @param array<string, mixed> $data */
    public function save(User $actor, string $relation, int $id, ?int $linkId, ?string $action, array $data, Request $request): Model
    {
        return DB::transaction(function () use ($actor, $relation, $id, $linkId, $action, $data, $request): Model {
            MasterDataAccess::authorizeRelation($actor, $relation, $action, true);
            $owner = self::owner($relation, $id, true);
            MasterDataService::checkVersion($owner, $data['expected_updated_at']);
            $query = self::query($relation, $id);
            $before = null;
            if (! $linkId) {
                abort_unless($relation === 'guardian-links' ? ! $owner->archived_at : $owner->is_active, 422, 'Aktifkan data induk sebelum menambah hubungan.');
                $targetField = match ($relation) {
                    'guardian-links' => 'guardian_id', 'categories' => 'category_id', 'positions' => 'position_id'
                };
                if ($relation !== 'categories') {
                    $query->where($targetField, $data[$targetField]);
                }
                if ((clone $query)->where('effective_from', $data['effective_from'])->exists()) {
                    throw ValidationException::withMessages(['effective_from' => 'Hubungan pada tanggal awal ini sudah tercatat, termasuk riwayat yang dicabut.']);
                }
                $overlap = $query->where('effective_from', '<', $data['effective_to'] ?? '9999-12-31')
                    ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>', $data['effective_from']));
                if ($relation === 'guardian-links') {
                    $overlap->whereNull('revoked_at')->whereNotIn('verification_status', ['rejected', 'revoked']);
                }
                if ($overlap->exists()) {
                    throw ValidationException::withMessages(['effective_from' => 'Rentang penugasan atau hubungan bertumpang tindih dengan riwayat yang ada.']);
                }
                $values = Arr::only($data, [$targetField, 'effective_from', 'effective_to', 'relationship', 'is_primary', 'appointment_number']);
                $values[$relation === 'guardian-links' ? 'student_id' : 'employee_id'] = $id;
                $values['created_by'] = $actor->id;
                if ($relation === 'guardian-links') {
                    $values['verification_status'] = 'pending';
                }
                if ($relation !== 'positions') {
                    $values['reason'] = $data['reason'];
                }
                $link = self::query($relation, $id)->getModel()->newInstance()->forceFill($values);
                $link->save();
            } else {
                $link = $query->lockForUpdate()->findOrFail($linkId);
                MasterDataService::checkVersion($link, $data['expected_link_updated_at']);
                $before = $link->only(['verification_status', 'effective_from', 'effective_to', 'revoked_at']);
                if ($action === 'verify') {
                    abort_unless($relation === 'guardian-links' && $link->verification_status === 'pending' && ! $link->revoked_at, 422, 'Hanya hubungan wali pending yang dapat diverifikasi.');
                    abort_unless($link->guardian->is_active && $link->guardian->person->is_active && ! $owner->archived_at, 422, 'Identitas wali dan siswa harus aktif.');
                    if ($link->is_primary && self::query($relation, $id)->whereKeyNot($link->id)->where('is_primary', true)->where('verification_status', 'verified')->whereNull('revoked_at')
                        ->where('effective_from', '<', $link->effective_to?->toDateString() ?? '9999-12-31')
                        ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>', $link->effective_from))->exists()) {
                        throw ValidationException::withMessages(['is_primary' => 'Sudah ada wali utama terverifikasi pada rentang ini.']);
                    }
                    $link->forceFill(['verification_status' => 'verified', 'verified_by' => $actor->id, 'verified_at' => now('UTC'), 'reason' => $data['reason']]);
                } elseif ($action === 'revoke') {
                    abort_unless($relation === 'guardian-links' && ! $link->revoked_at, 422);
                    $link->forceFill(['verification_status' => 'revoked', 'revoked_by' => $actor->id, 'revoked_at' => now('UTC'), 'reason' => $data['reason']]);
                } else {
                    abort_unless($action === 'end' && $data['effective_to'] > $link->effective_from->toDateString() && ! $link->effective_to, 422, 'Riwayat hanya dapat ditutup satu kali dengan tanggal setelah awal hubungan.');
                    $link->effective_to = $data['effective_to'];
                }
                $link->save();
            }
            $owner->touch();
            $this->audit->audit($actor, 'master.relation.'.($action ?? 'created'), $relation, $link->id, $data['reason'], $before,
                ['owner_id' => $id, 'state' => $link->only(['verification_status', 'effective_from', 'effective_to', 'verified_by', 'verified_at', 'revoked_by', 'revoked_at'])], $request);

            return $link->fresh();
        }, 3);
    }
}
