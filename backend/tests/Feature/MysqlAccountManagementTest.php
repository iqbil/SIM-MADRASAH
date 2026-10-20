<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\OAuthAccount;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AccountAccess;
use Database\Seeders\AccountAccessSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\MysqlAuthTestCase;

class MysqlAccountManagementTest extends MysqlAuthTestCase
{
    private function admin(): User
    {
        $this->seed(AccountAccessSeeder::class);
        $user = User::factory()->create();
        $role = Role::query()->where('code', 'super_admin')->firstOrFail();
        UserRole::query()->forceCreate(['user_id' => $user->id, 'role_id' => $role->id, 'effective_from' => now('UTC')->subSecond(), 'granted_by' => $user->id]);
        foreach (Permission::query()->whereIn('code', array_keys(AccountAccess::PERMISSIONS))->get() as $p) {
            RolePermission::query()->forceCreate(['role_id' => $role->id, 'permission_id' => $p->id, 'created_by' => $user->id]);
        }

        return $user;
    }

    private function headersFor(User $user): array
    {
        return ['Authorization' => 'Bearer '.$this->loginUser($user)['access_token']];
    }

    public function test_unprivileged_users_and_anonymous_requests_cannot_manage_accounts(): void
    {
        $this->admin();
        $this->getJson('/api/v1/accounts/users')->assertUnauthorized();
        $headers = $this->headersFor(User::factory()->create());
        foreach (['users', 'roles', 'people', 'audit'] as $path) {
            $this->getJson('/api/v1/accounts/'.$path, $headers)->assertForbidden();
        }
        $this->postJson('/api/v1/accounts/users', ['email' => 'someone@example.test'], $headers)->assertForbidden();
    }

    public function test_create_user_issues_a_temporary_password_and_preserves_identity_uniqueness(): void
    {
        $admin = $this->admin();
        $headers = $this->headersFor($admin);
        $person = Person::factory()->create();
        $role = Role::query()->where('code', 'guru')->firstOrFail();
        $body = ['name' => 'Guru Satu', 'username' => 'guru.satu', 'email' => null, 'person_id' => $person->id, 'role_ids' => [$role->id], 'reason' => 'Pembuatan akun guru terverifikasi'];
        $response = $this->postJson('/api/v1/accounts/users', $body, $headers)->assertCreated()->assertJsonPath('data.person_id', $person->id);
        $user = User::query()->findOrFail($response->json('data.id'));
        $this->assertTrue(Hash::check($response->json('temporary_password'), $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->temporary_password_expires_at->greaterThan(now('UTC')->addHours(23)));
        $this->assertDatabaseHas('user_roles', ['user_id' => $user->id, 'role_id' => $role->id, 'granted_by' => $admin->id]);
        $this->postJson('/api/v1/accounts/users', [...$body, 'username' => 'guru.dua'], $headers)->assertUnprocessable();
        $list = $this->getJson('/api/v1/accounts/users', $headers)->assertOk();
        $this->assertStringNotContainsString($response->json('temporary_password'), $list->getContent());
        $this->assertStringNotContainsString($response->json('temporary_password'), AuditLog::query()->get()->toJson());
    }

    public function test_roles_are_revoked_with_history_and_stale_updates_are_rejected(): void
    {
        $admin = $this->admin();
        $headers = $this->headersFor($admin);
        $user = User::factory()->create();
        $role = Role::query()->where('code', 'guru')->firstOrFail();
        UserRole::query()->forceCreate(['user_id' => $user->id, 'role_id' => $role->id, 'effective_from' => now('UTC')->subHour(), 'granted_by' => $admin->id]);
        $body = ['name' => $user->name, 'username' => $user->username, 'email' => $user->email, 'lock_version' => 0, 'role_ids' => [], 'reason' => 'Penugasan guru telah berakhir'];
        $this->putJson('/api/v1/accounts/users/'.$user->id, $body, $headers)->assertOk();
        $this->assertDatabaseHas('user_roles', ['user_id' => $user->id, 'role_id' => $role->id, 'revoked_by' => $admin->id]);
        $this->putJson('/api/v1/accounts/users/'.$user->id, $body, $headers)->assertConflict();
        $this->getJson('/api/v1/accounts/users/'.$user->id, $headers)->assertJsonCount(1, 'data.role_history')->assertJsonCount(0, 'data.roles');
    }

    public function test_disabling_user_revokes_bearer_and_refresh_tokens_and_is_audited(): void
    {
        $admin = $this->admin();
        $headers = $this->headersFor($admin);
        $user = User::factory()->create();
        $tokens = $this->loginUser($user);
        $this->postJson('/api/v1/accounts/users/'.$user->id.'/status', ['status' => 'disabled', 'lock_version' => 0, 'reason' => 'Akun dinonaktifkan karena tidak digunakan'], $headers)->assertOk();
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$tokens['access_token']])->assertUnauthorized();
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $tokens['refresh_token']])->assertUnauthorized();
        $this->assertDatabaseHas('audit_logs', ['event' => 'accounts.status', 'actor_user_id' => $admin->id, 'object_id' => (string) $user->id]);
    }

    public function test_reset_requires_identity_confirmation_and_temporary_password_is_one_use(): void
    {
        $admin = $this->admin();
        $headers = $this->headersFor($admin);
        $user = User::factory()->create();
        $data = ['lock_version' => 0, 'reason' => 'Identitas pengguna telah diperiksa langsung'];
        $this->postJson('/api/v1/accounts/users/'.$user->id.'/reset-password', $data, $headers)->assertUnprocessable();
        $r = $this->postJson('/api/v1/accounts/users/'.$user->id.'/reset-password', [...$data, 'identity_confirmed' => true], $headers)->assertOk();
        $login = ['login' => $user->username, 'password' => $r->json('temporary_password')];
        $this->postJson('/api/v1/auth/login', $login)->assertOk()->assertJsonPath('data.user.must_change_password', true);
        $this->postJson('/api/v1/auth/login', $login)->assertUnauthorized();
    }

    public function test_super_admin_and_self_disable_are_protected_without_sensitive_bypass(): void
    {
        $admin = $this->admin();
        $headers = $this->headersFor($admin);
        $this->postJson('/api/v1/accounts/users/'.$admin->id.'/status', ['status' => 'disabled', 'lock_version' => 0, 'reason' => 'Percobaan menonaktifkan akun sendiri'], $headers)->assertUnprocessable();
        $this->assertNotContains('bk.read', AccountAccess::permissions($admin));
        $role = Role::query()->where('code', 'super_admin')->firstOrFail();
        $this->putJson('/api/v1/accounts/roles/'.$role->id, ['code' => 'super_admin', 'name' => 'Super Admin', 'is_active' => false, 'permission_codes' => [], 'reason' => 'Percobaan mengubah role utama'], $headers)->assertUnprocessable();
        $this->putJson('/api/v1/accounts/users/'.$admin->id, ['name' => $admin->name, 'username' => $admin->username, 'email' => $admin->email, 'lock_version' => 0, 'role_ids' => [], 'reason' => 'Percobaan mencabut role sendiri'], $headers)->assertUnprocessable();
    }

    public function test_role_permission_assignment_is_real_and_a_revoked_grant_loses_access(): void
    {
        $admin = $this->admin();
        $headers = $this->headersFor($admin);
        $user = User::factory()->create();
        $r = $this->postJson('/api/v1/accounts/roles', ['code' => 'petugas_akun', 'name' => 'Petugas akun', 'description' => null, 'is_active' => true, 'permission_codes' => ['accounts.view'], 'reason' => 'Memberikan akses baca akun kepada petugas'], $headers)->assertCreated();
        $role = Role::query()->findOrFail($r->json('data.id'));
        $grant = UserRole::query()->forceCreate(['user_id' => $user->id, 'role_id' => $role->id, 'effective_from' => now('UTC')->subHour(), 'granted_by' => $admin->id]);
        $userHeaders = $this->headersFor($user);
        $this->getJson('/api/v1/accounts/users', $userHeaders)->assertOk();
        $this->postJson('/api/v1/accounts/users', [], $userHeaders)->assertForbidden();
        $grant->forceFill(['effective_to' => now('UTC'), 'revoked_by' => $admin->id, 'revoke_reason' => 'Penugasan selesai'])->save();
        $this->getJson('/api/v1/accounts/users', $userHeaders)->assertForbidden();
    }

    public function test_database_seeder_creates_one_google_eligible_owner_without_credentials_file(): void
    {
        config(['accounts.initial_admin.email' => 'owner@example.test', 'accounts.initial_admin.password' => 'Initial-password-123!']);
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $this->assertTrue(AccountAccess::isSuper($owner));
        $this->assertNotNull($owner->email_verified_at);
        $this->assertFalse($owner->must_change_password);
        $this->assertTrue($owner->canSignIn());
        $this->assertTrue(Hash::check('Initial-password-123!', $owner->password));
        $this->assertNotContains('bk.read', AccountAccess::permissions($owner));
        $this->assertDatabaseHas('audit_logs', ['event' => 'accounts.seeded', 'actor_user_id' => $owner->id]);
        $this->assertFileDoesNotExist(storage_path('app/private/bootstrap-credentials.txt'));

        $owner->forceFill(['password' => 'Changed-password-456!', 'status' => 'disabled', 'email_verified_at' => null])->save();
        $passwordHash = $owner->password;
        $this->seed(DatabaseSeeder::class);
        $owner->refresh();
        $this->assertSame($passwordHash, $owner->password);
        $this->assertSame('disabled', $owner->status);
        $this->assertNull($owner->email_verified_at);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('user_roles', 1);
        $this->assertSame(1, AuditLog::query()->where('event', 'accounts.seeded')->count());
    }

    public function test_owner_seeder_supports_google_login_without_a_configured_password(): void
    {
        config(['accounts.initial_admin.email' => 'owner@example.test', 'accounts.initial_admin.password' => null]);
        $this->seed(SuperAdminSeeder::class);
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $this->assertTrue($owner->canSignIn());
        $this->assertNotNull($owner->email_verified_at);
        $this->assertFalse($owner->must_change_password);
        $this->assertNotEmpty($owner->password);
        $this->assertFalse(Hash::check('', $owner->password));
    }

    public function test_owner_seeder_does_not_promote_an_existing_account_with_the_same_email(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.test']);
        config(['accounts.initial_admin.email' => $user->email]);
        try {
            $this->seed(SuperAdminSeeder::class);
            $this->fail('An existing user must not be promoted by the owner seeder.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Email/username sudah digunakan', $exception->getMessage());
        }
        $this->assertFalse(AccountAccess::isSuper($user));
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('user_roles', 0);
    }

    public function test_email_verification_and_google_unlink_are_explicit_and_audited(): void
    {
        $admin = $this->admin();
        $headers = $this->headersFor($admin);
        $user = User::factory()->unverified()->create();
        $body = ['reason' => 'Kepemilikan email sudah diperiksa langsung', 'lock_version' => 0];
        $this->postJson('/api/v1/accounts/users/'.$user->id.'/verify-email', $body, $headers)->assertUnprocessable();
        $this->postJson('/api/v1/accounts/users/'.$user->id.'/verify-email', [...$body, 'identity_confirmed' => true], $headers)->assertOk()->assertJsonPath('data.email_verified', true);
        OAuthAccount::factory()->create(['user_id' => $user->id]);
        $this->postJson('/api/v1/accounts/users/'.$user->id.'/unlink-google', [...$body, 'lock_version' => 1], $headers)->assertOk()->assertJsonPath('data.email_verified', false)->assertJsonPath('data.google_linked', false);
        $this->assertDatabaseHas('audit_logs', ['event' => 'accounts.verify-email']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'accounts.unlink-google']);
    }

    public function test_delegated_assignment_cannot_escalate_to_a_more_privileged_role(): void
    {
        $admin = $this->admin();
        $operator = User::factory()->create();
        $role = Role::query()->where('code', 'operator')->firstOrFail();
        UserRole::query()->forceCreate(['user_id' => $operator->id, 'role_id' => $role->id, 'effective_from' => now('UTC')->subSecond(), 'granted_by' => $admin->id]);
        foreach (Permission::query()->whereIn('code', ['accounts.view', 'accounts.update', 'accounts.assign'])->get() as $permission) {
            RolePermission::query()->forceCreate(['role_id' => $role->id, 'permission_id' => $permission->id, 'created_by' => $admin->id]);
        }
        $target = User::factory()->create();
        $super = Role::query()->where('code', 'super_admin')->firstOrFail();
        $body = ['name' => $target->name, 'username' => $target->username, 'email' => $target->email, 'role_ids' => [$super->id], 'lock_version' => 0, 'reason' => 'Percobaan memberikan hak yang lebih tinggi'];
        $this->putJson('/api/v1/accounts/users/'.$target->id, $body, $this->headersFor($operator))->assertForbidden();
        $custom = Role::query()->create(['code' => 'pengelola_lain', 'name' => 'Pengelola lain']);
        $permission = Permission::query()->where('code', 'accounts.roles')->firstOrFail();
        RolePermission::query()->forceCreate(['role_id' => $custom->id, 'permission_id' => $permission->id, 'created_by' => $admin->id]);
        $this->putJson('/api/v1/accounts/users/'.$target->id, [...$body, 'role_ids' => [$custom->id]], $this->headersFor($operator))->assertForbidden();
        $this->assertSame(0, $target->fresh()->lock_version);
    }

    public function test_new_role_may_have_no_account_management_permissions(): void
    {
        $admin = $this->admin();
        $this->postJson('/api/v1/accounts/roles', ['code' => 'petugas_kegiatan', 'name' => 'Petugas kegiatan', 'is_active' => true, 'permission_codes' => [], 'reason' => 'Membuat role tanpa izin administrasi akun'], $this->headersFor($admin))->assertCreated();
    }
}
