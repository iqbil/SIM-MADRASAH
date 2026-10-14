<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('SIM Madrasah domain migrations require MySQL 8.4 or newer; configure DB_CONNECTION=mysql.');
        }

        $version = DB::selectOne('SELECT VERSION() AS version')->version;
        if (str_contains(strtolower($version), 'mariadb') || version_compare($version, '8.4.0', '<')) {
            throw new RuntimeException('SIM Madrasah domain migrations require MySQL 8.4 or newer.');
        }

        Schema::create('persons', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('full_name', 200);
            $table->char('sex', 1)->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('contact_email', 254)->nullable();
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['full_name', 'birth_date'], 'ix_persons_full_name_birth_date');
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_roles_code');
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 120)->collation('utf8mb4_bin');
            $table->string('module', 50);
            $table->string('action', 50);
            $table->text('description')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_permissions_code');
            $table->index(['module', 'action'], 'ix_permissions_module_action');
        });

        Schema::create('student_statuses', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 30)->collation('utf8mb4_bin');
            $table->string('name', 100);
            $table->boolean('is_terminal')->default(false);
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_student_statuses_code');
        });

        Schema::create('employee_categories', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 30)->collation('utf8mb4_bin');
            $table->string('name', 100);
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_employee_categories_code');
        });

        Schema::create('positions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_positions_code');
        });

        Schema::create('subjects', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->string('name', 150);
            $table->string('group_name', 100)->nullable();
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_subjects_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');

        Schema::dropIfExists('positions');

        Schema::dropIfExists('employee_categories');

        Schema::dropIfExists('student_statuses');

        Schema::dropIfExists('permissions');

        Schema::dropIfExists('roles');

        Schema::dropIfExists('persons');
    }
};
