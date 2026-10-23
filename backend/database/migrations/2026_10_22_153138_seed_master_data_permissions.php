<?php

use App\Models\User;
use App\Models\UserRole;
use App\Services\MasterDataAccess;
use Database\Seeders\AccountAccessSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            (new AccountAccessSeeder)->run();
            $grant = UserRole::query()->whereHas('role', fn ($query) => $query->whereIn('code', ['operator', 'super_admin']))->orderBy('id')->first();
            if ($grant && ($actor = User::query()->find($grant->granted_by))) {
                AccountAccessSeeder::seedOperatorMasterPermissions($actor);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $ids = DB::table('permissions')->whereIn('code', array_keys(MasterDataAccess::catalog()))->pluck('id');
            DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        });
    }
};
