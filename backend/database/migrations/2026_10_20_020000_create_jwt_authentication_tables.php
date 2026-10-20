<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_sessions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->uuid('public_id')->collation('utf8mb4_bin')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->char('access_jti_hash', 64)->collation('utf8mb4_bin');
            $table->string('login_method', 30);
            $table->dateTime('expires_at', 6);
            $table->dateTime('last_used_at', 6);
            $table->dateTime('revoked_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['user_id', 'revoked_at', 'expires_at'], 'ix_auth_sessions_user_expiry');
        });

        Schema::create('auth_refresh_tokens', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('auth_session_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->char('token_hash', 64)->collation('utf8mb4_bin')->unique();
            $table->dateTime('expires_at', 6);
            $table->dateTime('consumed_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index('expires_at');
        });

        Schema::create('oauth_accounts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->string('provider', 30)->collation('utf8mb4_bin');
            $table->string('provider_user_id', 255)->collation('utf8mb4_bin');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['provider', 'provider_user_id'], 'uq_oauth_provider_subject');
            $table->unique(['user_id', 'provider'], 'uq_oauth_user_provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_accounts');
        Schema::dropIfExists('auth_refresh_tokens');
        Schema::dropIfExists('auth_sessions');
    }
};
