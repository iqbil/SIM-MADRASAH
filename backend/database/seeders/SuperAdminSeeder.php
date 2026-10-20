<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Person;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AccountAccess;
use App\Services\AccountManagementService;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    public function run(AccountManagementService $service): void
    {
        $this->call(AccountAccessSeeder::class);

        DB::transaction(function () use ($service): void {
            $role = Role::query()->where('code', 'super_admin')->lockForUpdate()->firstOrFail();
            if (UserRole::query()->where('role_id', $role->id)->exists()) {
                return;
            }

            $email = mb_strtolower(trim((string) config('accounts.initial_admin.email')));
            $username = (string) config('accounts.initial_admin.username');
            if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
                throw new RuntimeException('Email Super Admin awal tidak valid.');
            }
            if (User::query()->where('email', $email)->orWhere('username', $username)->exists()) {
                throw new RuntimeException('Email/username sudah digunakan. Seeder tidak mengubah akun yang sudah ada.');
            }

            $name = (string) config('accounts.initial_admin.name');
            $password = config('accounts.initial_admin.password');
            $person = Person::query()->create(['full_name' => $name, 'contact_email' => $email]);
            $user = User::query()->forceCreate([
                'person_id' => $person->id, 'name' => $name, 'username' => $username,
                'email' => $email, 'email_verified_at' => now('UTC'),
                'password' => filled($password) ? $password : 'Aa1!'.Str::random(28),
                'status' => 'active', 'must_change_password' => false,
            ]);
            UserRole::query()->forceCreate([
                'user_id' => $user->id, 'role_id' => $role->id,
                'effective_from' => now('UTC'), 'granted_by' => $user->id,
            ]);
            foreach (Permission::query()->whereIn('code', array_keys(AccountAccess::PERMISSIONS))->get() as $permission) {
                RolePermission::query()->forceCreate([
                    'role_id' => $role->id, 'permission_id' => $permission->id, 'created_by' => $user->id,
                ]);
            }
            $service->audit(
                $user, 'accounts.seeded', 'user', $user->id,
                'Pembuatan Super Admin awal melalui seeder.', null,
                ['username' => $user->username, 'role' => 'super_admin'],
                Request::create('/cli/db/seed'),
            );
        }, 3);
    }
}
