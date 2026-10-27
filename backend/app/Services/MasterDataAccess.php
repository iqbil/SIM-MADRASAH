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

    /** @return array<string, string> */
    public static function catalog(): array
    {
        $permissions = ['master.guardians.verify' => 'Memverifikasi hubungan wali dan siswa'];
        foreach (['institution' => 'identitas madrasah', 'students' => 'siswa', 'guardians' => 'wali', 'employees' => 'pegawai',
            'positions' => 'jabatan', 'employee-categories' => 'kategori pegawai', 'subjects' => 'mata pelajaran',
            'student-statuses' => 'status siswa', 'attendance-statuses' => 'status kehadiran'] as $resource => $label) {
            foreach (['view' => 'Melihat', 'manage' => 'Menambah dan mengubah', 'status' => 'Mengaktifkan dan menonaktifkan'] as $action => $verb) {
                if ($resource !== 'institution' || $action !== 'status') {
                    $permissions['master.'.$resource.'.'.$action] = $verb.' data '.$label;
                }
            }
            if (in_array($resource, ['students', 'guardians', 'employees', 'subjects'], true)) {
                $permissions['master.'.$resource.'.import'] = 'Mengimpor data '.$label;
            }
        }

        return $permissions;
    }

    /** @return array<int, string> */
    public static function permissions(User $user): array
    {
        if (! $user->canSignIn() || $user->must_change_password) {
            return [];
        }

        $roles = self::roles($user);
        $permissions = AccountAccess::activeGrants($user)->whereHas('role', fn (Builder $query): Builder => $query->where('is_active', true))
            ->with('role.permissionAssignments.permission')->get()->flatMap(fn ($grant) => $grant->role->permissionAssignments->pluck('permission.code'))
            ->filter(fn (string $code): bool => array_key_exists($code, self::catalog()))->unique()->values()->all();

        if (array_intersect($roles, ['super_admin', 'operator']) !== []) {
            return array_values(array_unique([...$permissions, ...array_keys(self::catalog())]));
        }

        return $permissions;
    }

    public static function allows(User $user, string $resource, string $action): bool
    {
        if ($resource === 'relations') {
            return self::manages($user);
        }
        $permissions = self::permissions($user);
        if (in_array('master.'.$resource.'.'.$action, $permissions, true)) {
            return true;
        }

        return $action === 'view' && in_array('master.'.$resource.'.manage', $permissions, true);
    }

    public static function manages(User $user, ?string $resource = null): bool
    {
        return $resource ? self::allows($user, $resource, 'manage') : collect(self::permissions($user))->contains(fn (string $code): bool => str_ends_with($code, '.manage'));
    }

    public static function authorize(User $user, string $resource, bool $write = false, ?string $action = null): void
    {
        $action ??= $write ? 'manage' : 'view';
        if ($resource === 'relations') {
            abort_unless(self::manages($user), 403, 'Data master ini di luar kewenangan Anda.');

            return;
        }
        abort_unless(self::allows($user, $resource, $action) || ($action === 'view' && $resource === 'students' && $user->canSignIn()
            && ! $user->must_change_password && count(self::classPeriods($user)) > 0), 403, 'Data master ini di luar kewenangan Anda.');
    }

    public static function authorizeRelation(User $user, string $relation, ?string $action = null, bool $write = false): void
    {
        abort_unless($user->canSignIn() && ! $user->must_change_password, 403, 'Data master ini di luar kewenangan Anda.');
        if ($relation === 'guardian-links') {
            if ($action === 'verify') {
                abort_unless(self::allows($user, 'guardians', 'verify') || self::allows($user, 'students', 'manage') || self::allows($user, 'guardians', 'manage'), 403, 'Data master ini di luar kewenangan Anda.');

                return;
            }
            if ($write) {
                abort_unless(self::allows($user, 'students', 'manage') || self::allows($user, 'guardians', 'manage'), 403, 'Data master ini di luar kewenangan Anda.');

                return;
            }
            abort_unless(self::allows($user, 'students', 'view') || self::allows($user, 'guardians', 'view') || self::allows($user, 'students', 'manage') || self::allows($user, 'guardians', 'manage') || self::allows($user, 'guardians', 'verify'), 403, 'Data master ini di luar kewenangan Anda.');

            return;
        }

        if (in_array($relation, ['categories', 'positions'], true)) {
            $required = $relation === 'categories' ? 'employee-categories' : 'positions';
            if ($write) {
                abort_unless(self::allows($user, 'employees', 'manage') || self::allows($user, $required, 'manage'), 403, 'Data master ini di luar kewenangan Anda.');

                return;
            }
            abort_unless(self::allows($user, 'employees', 'view') || self::allows($user, $required, 'view'), 403, 'Data master ini di luar kewenangan Anda.');

            return;
        }

        abort_unless(self::manages($user), 403, 'Data master ini di luar kewenangan Anda.');
    }

    public static function authorizeImport(User $user, string $resource): void
    {
        self::authorize($user, $resource, true);
        self::authorize($user, $resource, true, 'import');
    }

    /** @return array<string, mixed> */
    public static function options(User $user): array
    {
        $capabilities = [];
        $scope = self::classPeriods($user);
        foreach (array_keys(MasterDataCatalog::MODELS) as $resource) {
            $capabilities[$resource] = ['view' => self::allows($user, $resource, 'view') || ($resource === 'students' && $scope !== []),
                'manage' => self::allows($user, $resource, 'manage'), 'status' => self::allows($user, $resource, 'status')];
        }
        $imports = array_values(array_filter(['students', 'guardians', 'employees', 'subjects'], fn (string $resource): bool => self::allows($user, $resource, 'manage') && self::allows($user, $resource, 'import')));

        return ['can_manage' => self::manages($user), 'capabilities' => $capabilities,
            'resources' => array_keys(array_filter($capabilities, fn (array $item): bool => $item['view'])),
            'student_scope' => self::allows($user, 'students', 'view') ? 'all' : 'active_assignments',
            'can_verify_guardians' => self::allows($user, 'guardians', 'verify'), 'can_import' => $imports !== [], 'import_resources' => $imports];
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
        if (self::allows($user, 'students', 'view')) {
            return $query;
        }
        $date = today('Asia/Jakarta')->toDateString();

        return $query->whereNull('archived_at')->whereHas('enrollments', fn (Builder $q): Builder => $q->whereIn('class_period_id', self::classPeriods($user))
            ->where('effective_from', '<=', $date)->where(fn (Builder $end): Builder => $end->whereNull('effective_to')->orWhere('effective_to', '>', $date)));
    }
}
