<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Person;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AccountAccess;
use App\Services\AccountManagementService;
use Database\Seeders\AccountAccessSeeder;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class BootstrapSuperAdmin extends Command
{
    protected $signature = 'accounts:bootstrap {--email= : Email pemilik akun awal} {--name=Pengelola Utama} {--username=superadmin} {--credentials-file= : File privat untuk password awal}';

    protected $description = 'Membuat Super Admin pertama tanpa memberikan bypass akses data sensitif';

    public function handle(AccountManagementService $service): int
    {
        $email = mb_strtolower(trim((string) $this->option('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $this->error('Email valid wajib diisi.');

            return self::FAILURE;
        }
        $path = $this->option('credentials-file') ?: storage_path('app/private/bootstrap-credentials.txt');
        if (file_exists($path)) {
            $this->error('File kredensial sudah ada; simpan file tersebut dahulu. Tidak ada akun yang diubah.');

            return self::FAILURE;
        }
        $this->callSilent('db:seed', ['--class' => AccountAccessSeeder::class, '--force' => true]);
        $password = 'Aa1!'.Str::random(28);
        $created = DB::transaction(function () use ($email, $password, $service): bool {
            $role = Role::query()->where('code', 'super_admin')->lockForUpdate()->firstOrFail();
            if (UserRole::query()->where('role_id', $role->id)->exists()) {
                $this->error('Bootstrap sudah dilakukan. Gunakan pengelolaan akun untuk perubahan berikutnya.');

                return false;
            }
            if (User::query()->where('email', $email)->orWhere('username', $this->option('username'))->exists()) {
                throw new RuntimeException('Email/username sudah digunakan. Bootstrap tidak mengubah akun yang sudah ada.');
            }
            $person = Person::query()->create(['full_name' => $this->option('name'), 'contact_email' => $email]);
            $user = User::query()->forceCreate(['person_id' => $person->id, 'name' => $this->option('name'), 'username' => $this->option('username'),
                'email' => $email, 'email_verified_at' => now('UTC'), 'password' => $password, 'status' => 'active', 'must_change_password' => false]);
            UserRole::query()->forceCreate(['user_id' => $user->id, 'role_id' => $role->id, 'effective_from' => now('UTC'), 'granted_by' => $user->id]);
            foreach (Permission::query()->whereIn('code', array_keys(AccountAccess::PERMISSIONS))->get() as $permission) {
                RolePermission::query()->forceCreate(['role_id' => $role->id, 'permission_id' => $permission->id, 'created_by' => $user->id]);
            }
            $service->audit($user, 'accounts.bootstrap', 'user', $user->id, 'Bootstrap pemilik awal melalui CLI; email pemilik ditetapkan atas permintaan pemilik workspace.', null, ['username' => $user->username, 'role' => 'super_admin'], Request::create('/cli/accounts/bootstrap'));

            return true;
        }, 3);
        if (! $created) {
            return self::FAILURE;
        }
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        $handle = fopen($path, 'x');
        if ($handle === false) {
            throw new RuntimeException('Akun dibuat, tetapi file kredensial tidak dapat ditulis. Gunakan pemulihan lokal.');
        }
        chmod($path, 0600);
        fwrite($handle, "Akun awal SIM Madrasah\nEmail: ".$email."\nUsername: ".$this->option('username')."\nPassword: ".$password."\n\nSimpan secara privat. Login Google dapat digunakan dengan email yang sama.\n");
        fclose($handle);
        $this->info('Super Admin berhasil dibuat. Password disimpan pada file privat: '.$path);

        return self::SUCCESS;
    }
}
