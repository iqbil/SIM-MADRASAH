<?php

namespace App\Services;

use App\Models\BkCategoryGrant;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Builder;

/** Navigation visibility only; domain endpoints must still authorize each action and object. */
class NavigationAccess
{
    private const ROLE_MENUS = [
        'super_admin' => ['academic.manage', 'attendance.manage', 'website.edit', 'reports.view', 'system.manage'],
        'operator' => ['academic.manage', 'attendance.manage', 'website.edit', 'reports.view', 'system.manage'],
        'kepala_madrasah' => ['academic.view', 'attendance.reports', 'reports.view'],
        'guru' => ['academic.view', 'teaching.own', 'students.attendance', 'questions.own'],
        'wali_kelas' => ['academic.view', 'students.attendance', 'class.own', 'bk.shared'],
        'petugas_tu' => ['letters.manage', 'website.edit'],
    ];

    private const PERMISSION_MENUS = [
        'attendance.manage', 'attendance.correct', 'payroll.rates',
        'questions.packages', 'questions.keys', 'reports.view', 'reports.export',
    ];

    /** Explicit mandates are required even for administrators and the head of the school. */
    private const MANDATE_MENUS = [
        'realizations.verify' => 'teaching.verify',
        'attendance.manage' => 'attendance.manage',
        'attendance.correct' => 'attendance.correct',
        'payroll.calculate' => 'payroll.calculate',
        'payroll.review' => 'payroll.review',
        'payroll.approve' => 'payroll.approve',
        'payroll.rates' => 'payroll.rates',
        'payroll.adjust' => 'payroll.adjust',
        'questions.packages' => 'questions.packages',
        'questions.keys' => 'questions.keys',
        'publication.publish' => 'website.publish',
        'reports.view' => 'reports.view',
        'reports.export' => 'reports.export',
    ];

    /** @return list<string> */
    public static function forUser(User $user): array
    {
        if (! $user->canSignIn() || $user->must_change_password) {
            return [];
        }

        $assignments = AccountAccess::activeGrants($user)
            ->whereHas('role', fn (Builder $query): Builder => $query->where('is_active', true))
            ->with('role.permissionAssignments.permission')->get();
        $roles = $assignments->map(fn (UserRole $grant): string => $grant->role->code)->all();
        $menus = [];
        foreach ($roles as $role) {
            $menus = [...$menus, ...(self::ROLE_MENUS[$role] ?? [])];
        }
        foreach ($assignments as $assignment) {
            foreach ($assignment->role->permissionAssignments as $permissionAssignment) {
                $code = $permissionAssignment->permission->code;
                if (in_array($code, self::PERMISSION_MENUS, true)) {
                    $menus[] = self::MANDATE_MENUS[$code];
                }
            }
        }

        $master = MasterDataAccess::options($user);
        foreach ($master['resources'] as $resource) {
            $menus[] = 'master.'.$resource.'.view';
        }
        if ($master['can_import']) {
            $menus[] = 'master.imports';
        }

        $today = now('Asia/Jakarta')->toDateString();
        if ($user->person->employee()->where('is_active', true)->where('joined_on', '<=', $today)
            ->where(fn (Builder $query): Builder => $query->whereNull('left_on')->orWhere('left_on', '>', $today))->exists()) {
            $menus = [...$menus, 'attendance.own', 'payroll.own'];
        }

        if (in_array('orang_tua_wali', $roles, true) && $user->person->guardian()->where('is_active', true)
            ->whereHas('studentRelationships', fn (Builder $query): Builder => $query
                ->where('verification_status', 'verified')->whereNotNull('verified_at')->whereNull('revoked_at')
                ->where('effective_from', '<=', $today)
                ->where(fn (Builder $query): Builder => $query->whereNull('effective_to')->orWhere('effective_to', '>', $today)))
            ->exists()) {
            $menus = [...$menus, 'guardian.portal', 'bk.shared'];
        }

        $at = now('UTC')->format('Y-m-d H:i:s.u');
        $mandates = $user->authorizationMandates()->whereNull('revoked_at')->whereNull('revoked_by')
            ->where('effective_from', '<=', $at)
            ->where(fn (Builder $query): Builder => $query->whereNull('effective_to')->orWhere('effective_to', '>', $at))
            ->with('permission')->get();
        foreach ($mandates as $mandate) {
            $menu = self::MANDATE_MENUS[$mandate->permission->code] ?? null;
            if ($menu !== null) {
                $menus[] = $menu;
            }
        }

        if (in_array('guru_bk', $roles, true)) {
            $grants = $user->bkCategoryGrants()->whereNull('revoked_at')->where('effective_from', '<=', $at)
                ->where(fn (Builder $query): Builder => $query->whereNull('effective_to')->orWhere('effective_to', '>', $at))
                ->whereHas('category', fn (Builder $query): Builder => $query->where('is_active', true))->get();
            if ($grants->contains(fn (BkCategoryGrant $grant): bool => $grant->can_read_internal || $grant->can_write)) {
                $menus[] = 'bk.internal';
            }
            if ($grants->contains(fn (BkCategoryGrant $grant): bool => $grant->can_share)) {
                $menus[] = 'bk.share';
            }
        }

        if ($user->documentCategoryGrants()->whereNull('revoked_at')->where('effective_from', '<=', $at)
            ->where(fn (Builder $query): Builder => $query->whereNull('effective_to')->orWhere('effective_to', '>', $at))
            ->whereHas('category', fn (Builder $query): Builder => $query->where('is_active', true))
            ->where(fn (Builder $query): Builder => $query->where('can_read', true)->orWhere('can_write', true))->exists()) {
            $menus[] = 'documents.access';
        }

        return array_values(array_unique($menus));
    }
}
