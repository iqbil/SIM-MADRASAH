<?php

namespace App\Services;

use App\Models\AuthorizationMandate;
use App\Models\ClassPeriod;
use App\Models\Employee;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Otorisasi modul Absensi Pegawai (SRS v1.3 Bab II + FR-05). Sumber kebenaran
 * tunggal token navigasi: NavigationAccess memancangkan attendance.own untuk
 * akun yang terhubung pegawai aktif, attendance.manage untuk
 * operator/super admin, attendance.correct hanya lewat mandat, dan
 * attendance.reports untuk kepala madrasah. Super admin bukan bypass mandat.
 */
class AttendanceDataAccess
{
    /** Izin absensi yang dapat diberikan lewat role/mandat (Bab II). @return array<string, string> */
    public static function managedPermissions(): array
    {
        return [
            'attendance.manage' => 'Mencatat izin/sakit/cuti/dinas luar dan fallback administratif absensi pegawai',
            'attendance.correct' => 'Menyetujui atau menolak koreksi absensi (mandat petugas)',
            'attendance.reports' => 'Membaca rekap kehadiran pegawai',
        ];
    }

    /** @return array<int, string> */
    public static function roles(User $user): array
    {
        return MasterDataAccess::roles($user);
    }

    /** @return array<int, string> */
    public static function navigation(User $user): array
    {
        return NavigationAccess::forUser($user);
    }

    /** Pegawai aktif milik akun; null bila akun bukan pegawai. */
    public static function employee(User $user): ?Employee
    {
        $employee = $user->person?->employee;
        if (! $employee || ! $employee->is_active) {
            return null;
        }
        $today = today('Asia/Jakarta')->toDateString();
        if (($employee->joined_on && $employee->joined_on->toDateString() > $today)
            || ($employee->left_on && $employee->left_on->toDateString() <= $today)) {
            return null;
        }

        return $employee;
    }

    public static function authorizeOwn(User $user): Employee
    {
        abort_unless($user->canSignIn() && ! $user->must_change_password, 403);
        $employee = self::employee($user);
        abort_unless($employee !== null, 403, 'Akun tidak terhubung ke profil pegawai aktif.');

        return $employee;
    }

    public static function allows(User $user, string $token): bool
    {
        return in_array($token, self::navigation($user), true);
    }

    public static function authorizeManage(User $user): void
    {
        abort_unless($user->canSignIn() && ! $user->must_change_password, 403);
        abort_unless(self::allows($user, 'attendance.manage'), 403, 'Akses pengelolaan absensi tidak diberikan untuk peran Anda.');
    }

    public static function canManagePolicy(User $user): bool
    {
        return array_intersect(self::roles($user), ['super_admin', 'operator']) !== []
            || in_array('attendance.manage', AccountAccess::rolePermissions($user), true)
            || AuthorizationMandate::activeFor($user, 'attendance.manage')->where('scope_type', 'institution')->exists();
    }

    public static function authorizePolicy(User $user): void
    {
        self::authorizeManage($user);
        abort_unless(self::canManagePolicy($user), 403, 'Pengaturan kebijakan dan titik absensi memerlukan kewenangan seluruh institusi.');
    }

    /** Mandat aktif permission attendance.correct milik pengguna; null bila tidak ada. @return array{id: int}|null */
    public static function correctMandate(User $user, ?ClassPeriod $period = null, ?string $date = null, ?int $employeeId = null): ?array
    {
        $query = AuthorizationMandate::activeFor($user, 'attendance.correct');
        if ($period !== null || $employeeId !== null) {
            $semesterId = $period === null ? null : Semester::query()->where('academic_year_id', $period->academic_year_id)
                ->where('start_date', '<=', $date ?? today('Asia/Jakarta')->toDateString())
                ->where('end_date', '>', $date ?? today('Asia/Jakarta')->toDateString())->value('id');
            AuthorizationMandate::covering($query, $semesterId, $period?->id, $employeeId);
        }
        $mandate = $query->first(['id']);

        return $mandate !== null ? ['id' => $mandate->id] : null;
    }

    public static function authorizeCorrect(User $user, ?ClassPeriod $period = null, ?string $date = null, ?int $employeeId = null): void
    {
        abort_unless($user->canSignIn() && ! $user->must_change_password, 403);
        abort_unless(self::allows($user, 'attendance.correct'), 403, 'Persetujuan koreksi absensi memerlukan mandat petugas.');
        if ($period !== null || $employeeId !== null) {
            abort_unless(in_array('attendance.correct', AccountAccess::rolePermissions($user), true)
                || self::correctMandate($user, $period, $date, $employeeId) !== null, 403, 'Koreksi berada di luar cakupan mandat Anda.');
        }
    }

    public static function authorizeRead(User $user): void
    {
        abort_unless($user->canSignIn() && ! $user->must_change_password, 403);
        abort_unless(self::allows($user, 'attendance.manage') || self::allows($user, 'attendance.reports'), 403, 'Akses rekap kehadiran tidak diberikan untuk peran Anda.');
    }

    public static function scopeCorrections(Builder $query, User $user, bool $student): Builder
    {
        if (in_array('attendance.correct', AccountAccess::rolePermissions($user), true)
            || (! $student && self::allows($user, 'attendance.manage'))) {
            return $query;
        }
        $mandates = AuthorizationMandate::activeFor($user, 'attendance.correct')->get();
        if ($mandates->contains('scope_type', 'institution')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($mandates, $student): void {
            $q->whereRaw('1 = 0');
            foreach ($mandates as $mandate) {
                if (! $student && $mandate->scope_type === 'employee') {
                    $q->orWhereHas('employeeAttendance.workDay', fn (Builder $d): Builder => $d->where('employee_id', $mandate->employee_id));
                } elseif ($student && $mandate->scope_type === 'class') {
                    $q->orWhereHas('studentDailyAttendance', fn (Builder $a): Builder => $a->where('class_period_id', $mandate->class_period_id));
                } elseif ($student && $mandate->scope_type === 'semester') {
                    $semester = Semester::query()->find($mandate->semester_id);
                    if ($semester !== null) {
                        $q->orWhereHas('studentDailyAttendance', fn (Builder $a): Builder => $a
                            ->where('attendance_date', '>=', $semester->start_date->toDateString())
                            ->where('attendance_date', '<', $semester->end_date->toDateString())
                            ->whereHas('classPeriod', fn (Builder $p): Builder => $p->where('academic_year_id', $semester->academic_year_id)));
                    }
                }
            }
        });
    }
}
