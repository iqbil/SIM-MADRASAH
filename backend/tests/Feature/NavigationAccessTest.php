<?php

namespace Tests\Feature;

use App\Models\AuthorizationMandate;
use App\Models\BkCategory;
use App\Models\BkCategoryGrant;
use App\Models\DocumentCategory;
use App\Models\DocumentCategoryGrant;
use App\Models\Employee;
use App\Models\Guardian;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\User;
use App\Models\UserRole;
use App\Services\NavigationAccess;
use Database\Seeders\AccountAccessSeeder;
use Database\Seeders\ReferenceDataSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\MysqlAuthTestCase;

class NavigationAccessTest extends MysqlAuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        $this->seed(AccountAccessSeeder::class);
    }

    private function grant(User $user, string $code): UserRole
    {
        return UserRole::query()->forceCreate([
            'user_id' => $user->id, 'role_id' => Role::query()->where('code', $code)->firstOrFail()->id,
            'effective_from' => now('UTC')->subHour(), 'granted_by' => $user->id,
        ]);
    }

    public static function roleMenus(): array
    {
        return [
            'super admin' => ['super_admin', ['master.manage', 'master.institution.view', 'master.students.view', 'master.guardians.view', 'master.employees.view', 'master.positions.view', 'master.employee-categories.view', 'master.subjects.view', 'master.student-statuses.view', 'master.attendance-statuses.view', 'master.imports', 'academic.manage', 'attendance.manage', 'website.edit', 'reports.view', 'system.manage']],
            'operator' => ['operator', ['master.manage', 'master.institution.view', 'master.students.view', 'master.guardians.view', 'master.employees.view', 'master.positions.view', 'master.employee-categories.view', 'master.subjects.view', 'master.student-statuses.view', 'master.attendance-statuses.view', 'master.imports', 'academic.manage', 'attendance.manage', 'website.edit', 'reports.view', 'system.manage']],
            'head' => ['kepala_madrasah', ['academic.view', 'attendance.reports', 'reports.view']],
            'teacher' => ['guru', ['academic.view', 'teaching.own', 'students.attendance', 'questions.own']],
            'homeroom teacher' => ['wali_kelas', ['academic.view', 'students.attendance', 'class.own', 'bk.shared']],
            'administration' => ['petugas_tu', ['letters.manage', 'website.edit']],
            'counselor without category grant' => ['guru_bk', []],
            'treasurer without mandate' => ['bendahara', []],
            'staff without employee identity' => ['tenaga_kependidikan', []],
            'guardian without verified relationship' => ['orang_tua_wali', []],
        ];
    }

    #[DataProvider('roleMenus')]
    public function test_default_role_menus_do_not_bypass_sensitive_access(string $role, array $expected): void
    {
        $user = User::factory()->create();
        $this->grant($user, $role);
        $this->assertEqualsCanonicalizing($expected, NavigationAccess::forUser($user));
    }

    public function test_api_returns_combined_active_roles_and_revocation_takes_effect_on_next_request(): void
    {
        $user = User::factory()->create();
        $teacher = $this->grant($user, 'guru');
        $this->grant($user, 'wali_kelas');
        $token = $this->loginUser($user)['access_token'];
        $headers = ['Authorization' => 'Bearer '.$token];
        $menus = $this->getJson('/api/v1/auth/me', $headers)->assertOk()->json('data.navigation');
        $this->assertContains('questions.own', $menus);
        $this->assertContains('class.own', $menus);
        $this->assertSame(count($menus), count(array_unique($menus)));
        $teacher->forceFill(['revoked_by' => $user->id, 'revoke_reason' => 'Penugasan selesai'])->save();
        $menus = $this->getJson('/api/v1/auth/me', $headers)->assertOk()->json('data.navigation');
        $this->assertNotContains('questions.own', $menus);
        $this->assertContains('class.own', $menus);
    }

    public function test_inactive_future_and_expired_role_assignments_are_excluded(): void
    {
        $user = User::factory()->create();
        $future = $this->grant($user, 'operator');
        $future->forceFill(['effective_from' => now('UTC')->addDay()])->save();
        $expired = $this->grant($user, 'guru');
        $expired->forceFill(['effective_to' => now('UTC')])->save();
        $this->grant($user, 'wali_kelas')->role->update(['is_active' => false]);
        $this->assertSame([], NavigationAccess::forUser($user));
    }

    public function test_personal_attendance_and_final_payroll_require_an_active_employee_identity(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['person_id' => $user->person_id]);
        $this->assertEqualsCanonicalizing(['attendance.own', 'payroll.own'], NavigationAccess::forUser($user));
        $employee->update(['left_on' => now('Asia/Jakarta')->toDateString()]);
        $this->assertSame([], NavigationAccess::forUser($user));
        $employee->update(['left_on' => null, 'is_active' => false]);
        $this->assertSame([], NavigationAccess::forUser($user));
    }

    public function test_payroll_and_publication_menus_need_live_explicit_mandates(): void
    {
        $user = User::factory()->create();
        foreach (['payroll.review', 'payroll.approve', 'publication.publish', 'realizations.verify'] as $code) {
            $permission = Permission::query()->create([
                'code' => $code, 'module' => explode('.', $code)[0], 'action' => explode('.', $code)[1], 'is_sensitive' => true,
            ]);
            $mandate = AuthorizationMandate::query()->forceCreate([
                'user_id' => $user->id, 'permission_id' => $permission->id, 'scope_type' => 'institution',
                'effective_from' => now('UTC')->subHour(), 'effective_to' => now('UTC')->addHour(),
                'granted_by' => $user->id, 'reason' => 'Mandat pengujian menu',
            ]);
            $menu = match ($code) {
                'publication.publish' => 'website.publish', 'realizations.verify' => 'teaching.verify', default => $code,
            };
            $this->assertContains($menu, NavigationAccess::forUser($user));
            $mandate->forceFill(['effective_from' => now('UTC')->addMinutes(5)])->save();
            $this->assertNotContains($menu, NavigationAccess::forUser($user));
            $mandate->forceFill(['effective_from' => now('UTC')->subHour(), 'revoked_at' => now('UTC')])->save();
            $this->assertNotContains($menu, NavigationAccess::forUser($user));
            $mandate->forceFill(['revoked_at' => null, 'effective_to' => now('UTC')])->save();
            $this->assertNotContains($menu, NavigationAccess::forUser($user));
        }
    }

    public function test_explicit_role_permissions_enable_question_keys_but_do_not_replace_approval_mandates(): void
    {
        $user = User::factory()->create();
        $grant = $this->grant($user, 'guru');
        foreach (['questions.keys', 'payroll.approve', 'publication.publish'] as $code) {
            $permission = Permission::query()->create([
                'code' => $code, 'module' => explode('.', $code)[0], 'action' => explode('.', $code)[1], 'is_sensitive' => true,
            ]);
            RolePermission::query()->forceCreate([
                'role_id' => $grant->role_id, 'permission_id' => $permission->id, 'created_by' => $user->id,
            ]);
        }
        $menus = NavigationAccess::forUser($user);
        $this->assertContains('questions.keys', $menus);
        $this->assertNotContains('payroll.approve', $menus);
        $this->assertNotContains('website.publish', $menus);
        RolePermission::query()->where('role_id', $grant->role_id)->delete();
        $this->assertNotContains('questions.keys', NavigationAccess::forUser($user));
    }

    public function test_bk_category_grants_and_document_grants_are_checked_separately(): void
    {
        $user = User::factory()->create();
        $this->grant($user, 'guru_bk');
        $category = BkCategory::query()->create(['code' => 'guidance', 'name' => 'Konseling', 'is_active' => true]);
        $grant = BkCategoryGrant::query()->forceCreate([
            'user_id' => $user->id, 'category_id' => $category->id, 'can_read_internal' => true, 'can_share' => false,
            'effective_from' => now('UTC')->subHour(), 'granted_by' => $user->id, 'reason' => 'Tugas konseling',
        ]);
        $this->assertContains('bk.internal', NavigationAccess::forUser($user));
        $this->assertNotContains('bk.share', NavigationAccess::forUser($user));
        $grant->forceFill(['can_share' => true])->save();
        $this->assertContains('bk.share', NavigationAccess::forUser($user));
        $category->update(['is_active' => false]);
        $this->assertNotContains('bk.internal', NavigationAccess::forUser($user));
        $category->update(['is_active' => true]);
        $grant->forceFill(['revoked_at' => now('UTC')])->save();
        $this->assertNotContains('bk.internal', NavigationAccess::forUser($user));

        $documentCategory = DocumentCategory::query()->create(['code' => 'internal', 'name' => 'Internal', 'access_class' => 'internal', 'is_active' => true]);
        $documentGrant = DocumentCategoryGrant::query()->forceCreate([
            'user_id' => $user->id, 'category_id' => $documentCategory->id, 'can_read' => true,
            'effective_from' => now('UTC')->subHour(), 'granted_by' => $user->id, 'reason' => 'Tugas arsip',
        ]);
        $this->assertContains('documents.access', NavigationAccess::forUser($user));
        $documentGrant->forceFill(['effective_to' => now('UTC')])->save();
        $this->assertNotContains('documents.access', NavigationAccess::forUser($user));
    }

    public function test_guardian_menus_require_a_current_verified_relationship_and_use_local_calendar_dates(): void
    {
        $this->travelTo(now('UTC')->setDate(2026, 10, 20)->setTime(18, 0));
        $this->seed(ReferenceDataSeeder::class);
        $user = User::factory()->create();
        $this->grant($user, 'orang_tua_wali');
        $guardian = Guardian::factory()->create(['person_id' => $user->person_id]);
        $relationship = StudentGuardian::query()->forceCreate([
            'student_id' => Student::factory()->create()->id, 'guardian_id' => $guardian->id,
            'relationship' => 'legal_guardian', 'verification_status' => 'pending',
            'effective_from' => '2026-10-21', 'created_by' => $user->id,
        ]);
        $this->assertSame([], NavigationAccess::forUser($user));
        $relationship->forceFill(['verification_status' => 'verified', 'verified_at' => now('UTC'), 'verified_by' => $user->id])->save();
        $this->assertContains('guardian.portal', NavigationAccess::forUser($user));
        $relationship->forceFill(['revoked_at' => now('UTC'), 'revoked_by' => $user->id])->save();
        $this->assertNotContains('guardian.portal', NavigationAccess::forUser($user));
    }

    public function test_disabled_accounts_and_temporary_password_sessions_have_no_module_navigation(): void
    {
        $user = User::factory()->create();
        $this->grant($user, 'super_admin');
        $user->forceFill(['must_change_password' => true])->save();
        $this->assertSame([], NavigationAccess::forUser($user));
        $user->forceFill(['must_change_password' => false, 'status' => 'disabled'])->save();
        $this->assertSame([], NavigationAccess::forUser($user));
    }
}
