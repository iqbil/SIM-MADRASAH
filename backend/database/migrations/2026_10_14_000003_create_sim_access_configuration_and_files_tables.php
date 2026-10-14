<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedTinyInteger('singleton_key');
            $table->string('name', 200);
            $table->string('npsn', 20)->nullable();
            $table->string('nsm', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('timezone', 50);
            $table->foreignId('logo_file_id')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['singleton_key'], 'uq_institutions_singleton_key');
            $table->index(['logo_file_id'], 'ix_institutions_logo_file_id');
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->dateTime('effective_from', 6);
            $table->dateTime('effective_to', 6)->nullable();
            $table->foreignId('granted_by');
            $table->foreignId('revoked_by')->nullable();
            $table->text('revoke_reason')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['user_id', 'role_id', 'effective_from'], 'uq_user_roles_user_id_role_id_effective_from');
            $table->index(['user_id', 'effective_to'], 'ix_user_roles_user_id_effective_to');
            $table->index(['role_id'], 'ix_user_roles_role_id');
            $table->index(['granted_by'], 'ix_user_roles_granted_by');
            $table->index(['revoked_by'], 'ix_user_roles_revoked_by');
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['role_id', 'permission_id'], 'uq_role_permissions_role_id_permission_id');
            $table->index(['permission_id'], 'ix_role_permissions_permission_id');
            $table->index(['created_by'], 'ix_role_permissions_created_by');
        });

        Schema::create('authorization_mandates', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('permission_id');
            $table->string('scope_type', 30);
            $table->foreignId('semester_id')->nullable();
            $table->foreignId('class_period_id')->nullable();
            $table->foreignId('employee_id')->nullable();
            $table->dateTime('effective_from', 6);
            $table->dateTime('effective_to', 6)->nullable();
            $table->foreignId('granted_by');
            $table->dateTime('revoked_at', 6)->nullable();
            $table->foreignId('revoked_by')->nullable();
            $table->text('reason');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['user_id', 'permission_id', 'effective_to'], 'ix_authorization_mandates_user_id_permission_id_effective_to');
            $table->index(['permission_id'], 'ix_authorization_mandates_permission_id');
            $table->index(['semester_id'], 'ix_authorization_mandates_semester_id');
            $table->index(['class_period_id'], 'ix_authorization_mandates_class_period_id');
            $table->index(['employee_id'], 'ix_authorization_mandates_employee_id');
            $table->index(['granted_by'], 'ix_authorization_mandates_granted_by');
            $table->index(['revoked_by'], 'ix_authorization_mandates_revoked_by');
        });

        Schema::create('business_decisions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 20)->collation('utf8mb4_bin');
            $table->string('title', 255);
            $table->string('status', 30);
            $table->text('decision_text');
            $table->text('rationale')->nullable();
            $table->string('source_reference', 255);
            $table->dateTime('recorded_at', 6);
            $table->foreignId('owner_user_id')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('approved_at', 6)->nullable();
            $table->foreignId('supersedes_id')->nullable();
            $table->unsignedSmallInteger('version');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code', 'version'], 'uq_business_decisions_code_version');
            $table->index(['owner_user_id'], 'ix_business_decisions_owner_user_id');
            $table->index(['approved_by'], 'ix_business_decisions_approved_by');
            $table->index(['supersedes_id'], 'ix_business_decisions_supersedes_id');
        });

        Schema::create('system_settings', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('key', 120)->collation('utf8mb4_bin');
            $table->json('value');
            $table->text('description')->nullable();
            $table->foreignId('updated_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['key'], 'uq_system_settings_key');
            $table->index(['updated_by'], 'ix_system_settings_updated_by');
        });

        Schema::create('files', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('storage_disk', 50)->collation('utf8mb4_bin');
            $table->string('storage_path', 500)->collation('utf8mb4_bin');
            $table->string('original_name', 255);
            $table->string('mime_type', 150);
            $table->string('extension', 15);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->collation('utf8mb4_bin');
            $table->string('classification', 30);
            $table->string('scan_status', 30);
            $table->foreignId('uploaded_by');
            $table->foreignId('retention_policy_id')->nullable();
            $table->dateTime('expires_at', 6)->nullable();
            $table->dateTime('deleted_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['storage_disk', 'storage_path'], 'uq_files_storage_disk_storage_path');
            $table->index(['sha256'], 'ix_files_sha256');
            $table->index(['classification', 'created_at'], 'ix_files_classification_created_at');
            $table->index(['uploaded_by'], 'ix_files_uploaded_by');
            $table->index(['retention_policy_id'], 'ix_files_retention_policy_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');

        Schema::dropIfExists('system_settings');

        Schema::dropIfExists('business_decisions');

        Schema::dropIfExists('authorization_mandates');

        Schema::dropIfExists('role_permissions');

        Schema::dropIfExists('user_roles');

        Schema::dropIfExists('institutions');
    }
};
