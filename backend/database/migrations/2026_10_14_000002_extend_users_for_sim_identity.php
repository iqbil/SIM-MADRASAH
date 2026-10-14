<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->whereRaw('CHAR_LENGTH(name) > 200 OR CHAR_LENGTH(email) > 254')->exists()) {
            throw new RuntimeException('Existing users have names/email longer than the SIM person/email limits; normalize those records before migrating.');
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('person_id')->nullable();
            $table->string('username', 100)->collation('utf8mb4_bin')->nullable();
            $table->string('email', 254)->nullable()->change();
            $table->dateTime('email_verified_at', 6)->nullable()->change();
            $table->string('status', 30);
            $table->dateTime('disabled_at', 6)->nullable();
            $table->dateTime('last_login_at', 6)->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->dateTime('temporary_password_expires_at', 6)->nullable();
            $table->dateTime('temporary_password_used_at', 6)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
        });

        DB::transaction(function (): void {
            $timestamp = now('UTC')->format('Y-m-d H:i:s.u');
            DB::table('users')->orderBy('id')->chunkById(500, function ($users) use ($timestamp): void {
                foreach ($users as $user) {
                    $personId = DB::table('persons')->insertGetId([
                        'full_name' => $user->name,
                        'contact_email' => $user->email,
                        'is_active' => true,
                        'created_at' => $user->created_at ?? $timestamp,
                        'updated_at' => $user->updated_at ?? $timestamp,
                    ]);
                    DB::table('users')->where('id', $user->id)->update([
                        'person_id' => $personId,
                        'username' => 'user_'.$user->id,
                        'status' => 'active',
                        'created_at' => $user->created_at ?? $timestamp,
                        'updated_at' => $user->updated_at ?? $timestamp,
                    ]);
                }
            });
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('person_id')->nullable(false)->change();
            $table->string('username', 100)->collation('utf8mb4_bin')->nullable(false)->change();
            $table->dateTime('created_at', 6)->nullable(false)->change();
            $table->dateTime('updated_at', 6)->nullable(false)->change();
            $table->unique('person_id', 'uq_users_person_id');
            $table->unique('username', 'uq_users_username');
        });
    }

    public function down(): void
    {
        if (DB::table('users')->whereNull('email')->exists()) {
            throw new RuntimeException('Cannot restore the original NOT NULL users.email while accounts without email exist; supply verified emails before rollback.');
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('uq_users_person_id');
            $table->dropUnique('uq_users_username');
            $table->dropColumn(['person_id', 'username', 'status', 'disabled_at', 'last_login_at', 'must_change_password', 'temporary_password_expires_at', 'temporary_password_used_at', 'lock_version']);
            $table->string('email', 255)->nullable(false)->change();
            $table->timestamp('email_verified_at')->nullable()->change();
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
        });
    }
};
