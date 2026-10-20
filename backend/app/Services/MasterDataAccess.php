<?php

namespace App\Services;

use App\Models\HomeroomAssignment;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MasterDataAccess
{
    /** @return array<int, string> */
    public static function roles(User $user): array
    {
        return AccountAccess::activeGrants($user)->whereHas('role', fn (Builder $query): Builder => $query->where('is_active', true))
            ->with('role')->get()->pluck('role.code')->all();
    }

    public static function manages(User $user): bool
    {
        return $user->canSignIn() && count(array_intersect(self::roles($user), ['super_admin', 'operator'])) > 0;
    }

    public static function authorize(User $user, string $resource, bool $write = false): void
    {
        abort_unless($user->canSignIn(), 403);
        if (self::manages($user)) {
            return;
        }
        abort_unless(! $write && $resource === 'students' && count(self::classPeriods($user)) > 0, 403, 'Data master ini di luar kewenangan Anda.');
    }

    /** @return array<int, int> */
    public static function classPeriods(User $user): array
    {
        $employee = $user->person?->employee;
        $date = today('Asia/Jakarta')->toDateString();
        if (! $employee || ! $employee->is_active || ($employee->left_on && $employee->left_on->toDateString() <= $date) || ($employee->joined_on && $employee->joined_on->toDateString() > $date)) {
            return [];
        }
        $roles = self::roles($user);
        $scope = function (Builder $query) use ($date, $employee): Builder {
            return $query->where('employee_id', $employee->id)->where('effective_from', '<=', $date)
                ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>', $date))
                ->whereHas('classPeriod.academicYear', fn (Builder $q): Builder => $q->where('start_date', '<=', $date)->where('end_date', '>', $date))
                ->where(fn (Builder $q): Builder => $q->whereNull('semester_id')->orWhereHas('semester', fn (Builder $s): Builder => $s->where('start_date', '<=', $date)->where('end_date', '>', $date)));
        };
        $ids = [];
        if (in_array('guru', $roles, true)) {
            $ids = $scope(TeachingAssignment::query())->where('is_active', true)->pluck('class_period_id')->all();
        }
        if (in_array('wali_kelas', $roles, true)) {
            $ids = [...$ids, ...$scope(HomeroomAssignment::query())->pluck('class_period_id')->all()];
        }

        return array_values(array_unique($ids));
    }

    public static function scopeStudents(Builder $query, User $user): Builder
    {
        if (self::manages($user)) {
            return $query;
        }
        $date = today('Asia/Jakarta')->toDateString();

        return $query->whereNull('archived_at')->whereHas('enrollments', fn (Builder $q): Builder => $q->whereIn('class_period_id', self::classPeriods($user))
            ->where('effective_from', '<=', $date)->where(fn (Builder $end): Builder => $end->whereNull('effective_to')->orWhere('effective_to', '>', $date)));
    }
}
