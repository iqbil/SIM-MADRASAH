<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Otorisasi modul Akademik & Kelas (SRS v1.3 Bab II): operator/super admin
 * mengelola; kepala madrasah membaca; guru membaca penugasan miliknya dan
 * kelas yang diajarnya; wali kelas membaca kelas perwaliannya. Cakupan data
 * dibatasi lewat helper scope; menu tetap mengikuti NavigationAccess.
 */
class AcademicDataAccess
{
    private const VIEW_ROLES = ['super_admin', 'operator', 'kepala_madrasah', 'guru', 'wali_kelas'];

    /** @return array<string, string> */
    public static function catalog(): array
    {
        $permissions = [];
        foreach ([
            'years' => 'tahun ajaran & semester',
            'classes' => 'rombongan belajar',
            'enrollments' => 'keanggotaan & riwayat siswa',
            'calendar' => 'kalender akademik',
            'assignments' => 'penugasan mengajar & wali kelas',
            'schedules' => 'jadwal pelajaran & pertemuan',
            'substitutes' => 'penunjukan guru pengganti',
        ] as $resource => $label) {
            $permissions['academic.'.$resource.'.view'] = 'Melihat '.$label;
            $permissions['academic.'.$resource.'.manage'] = 'Mengelola '.$label;
        }

        return $permissions;
    }

    /** @return array<int, string> */
    public static function roles(User $user): array
    {
        return MasterDataAccess::roles($user);
    }

    /** @return array<int, string> */
    public static function permissions(User $user): array
    {
        if (! $user->canSignIn() || $user->must_change_password) {
            return [];
        }

        $roles = self::roles($user);
        $granted = AccountAccess::activeGrants($user)->whereHas('role', fn (Builder $query): Builder => $query->where('is_active', true))
            ->with('role.permissionAssignments.permission')->get()
            ->flatMap(fn ($grant) => $grant->role->permissionAssignments->pluck('permission.code'))
            ->filter(fn (string $code): bool => array_key_exists($code, self::catalog()))->unique()->values()->all();

        if (array_intersect($roles, ['super_admin', 'operator']) !== []) {
            return array_values(array_unique([...$granted, ...array_keys(self::catalog())]));
        }
        if (in_array('kepala_madrasah', $roles, true) || in_array('guru', $roles, true) || in_array('wali_kelas', $roles, true)) {
            $views = array_keys(array_filter(self::catalog(), fn (string $code): bool => str_ends_with($code, '.view'), ARRAY_FILTER_USE_KEY));

            return array_values(array_unique([...$granted, ...$views]));
        }

        return $granted;
    }

    public static function allows(User $user, string $resource, string $action = 'view'): bool
    {
        return in_array('academic.'.$resource.'.'.$action, self::permissions($user), true);
    }

    public static function authorize(User $user, string $resource, bool $write = false): void
    {
        abort_unless($user->canSignIn() && ! $user->must_change_password, 403);
        abort_unless(self::allows($user, $resource, $write ? 'manage' : 'view'), 403, 'Akses akademik tidak diberikan untuk peran Anda.');
    }

    /**
     * Cakupan rombongan belajar yang boleh dibaca pengguna. Null berarti
     * seluruh kelas (operator/kepala); array berarti dibatasi ke kelas
     * penugasan aktif guru/wali kelas pada tanggal hari ini.
     *
     * @return array<int, int>|null
     */
    public static function classPeriodScope(User $user): ?array
    {
        if (array_intersect(self::roles($user), ['super_admin', 'operator', 'kepala_madrasah']) !== []) {
            return null;
        }

        $employee = $user->person?->employee;
        $today = today('Asia/Jakarta')->toDateString();
        if (! $employee || ! $employee->is_active || ($employee->left_on && $employee->left_on->toDateString() <= $today)
            || ($employee->joined_on && $employee->joined_on->toDateString() > $today)) {
            return [];
        }
        $covers = fn (Builder $query): Builder => $query
            ->where('effective_from', '<=', $today)
            ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>', $today));

        $taught = $employee->teachingAssignments()->where('is_active', true)->where($covers)->pluck('class_period_id')->all();
        $homeroom = $employee->homeroomAssignments()->where($covers)->pluck('class_period_id')->all();

        return array_values(array_unique([...$taught, ...$homeroom]));
    }

    /** Pegawai yang identitasnya terhubung ke akun; null bila bukan pegawai. */
    public static function employeeId(User $user): ?int
    {
        $employee = $user->person?->employee;

        return $employee && $employee->is_active ? $employee->id : null;
    }

    /** Batasi daftar enrollment ke cakupan kelas pengguna. */
    public static function scopeEnrollments(Builder $query, User $user): Builder
    {
        $scope = self::classPeriodScope($user);

        return $scope === null ? $query : $query->whereHas('classPeriod', fn (Builder $q): Builder => $q->whereIn('id', $scope));
    }

    /** Batasi daftar penugasan mengajar ke milik guru bersangkutan. */
    public static function scopeAssignments(Builder $query, User $user): Builder
    {
        if (array_intersect(self::roles($user), ['super_admin', 'operator', 'kepala_madrasah']) !== []) {
            return $query;
        }

        return $query->where('employee_id', self::employeeId($user) ?? -1);
    }

    /**
     * Batasi daftar pertemuan ke cakupan guru/wali kelas: kelas yang diajari
     * atau diwalikan, pertemuan yang dia terjadwal, dan tugas menggantinya.
     */
    public static function scopeMeetings(Builder $query, User $user): Builder
    {
        if (array_intersect(self::roles($user), ['super_admin', 'operator', 'kepala_madrasah']) !== []) {
            return $query;
        }
        $scope = self::classPeriodScope($user);
        $employeeId = self::employeeId($user) ?? -1;

        return $query->where(fn (Builder $q): Builder => $q
            ->whereIn('class_period_id', $scope ?: [-1])
            ->orWhere('scheduled_employee_id', $employeeId)
            ->orWhereHas('substituteAssignments', fn (Builder $s): Builder => $s->whereNull('revoked_at')->where('substitute_employee_id', $employeeId)));
    }

    /**
     * Batasi entri jadwal ke cakupan guru/wali kelas: entri penugasannya
     * sendiri atau entri kelas yang diajari/diwalikan.
     */
    public static function scopeEntryVersions(Builder $query, User $user): Builder
    {
        if (array_intersect(self::roles($user), ['super_admin', 'operator', 'kepala_madrasah']) !== []) {
            return $query;
        }
        $scope = self::classPeriodScope($user);
        $employeeId = self::employeeId($user) ?? -1;

        return $query->whereHas('teachingAssignment', fn (Builder $a): Builder => $a
            ->where('employee_id', $employeeId)
            ->orWhereIn('class_period_id', $scope ?: [-1]));
    }
}
