<?php

namespace App\Services;

use Illuminate\Database\Migrations\Migrator;
use RuntimeException;

class GuardedMigrator extends Migrator
{
    private const IDENTITY_MIGRATION = '2026_10_14_000002_extend_users_for_sim_identity';

    /**
     * @param  array<object{migration: string}>  $migrations
     * @param  string[]|string  $paths
     * @param  array<string, mixed>  $options
     * @return string[]
     */
    protected function rollbackMigrations(array $migrations, mixed $paths, array $options): array
    {
        $includesIdentity = collect($migrations)->contains(
            fn (object $migration): bool => $migration->migration === self::IDENTITY_MIGRATION,
        );
        if ($includesIdentity && ! ($options['pretend'] ?? false)
            && isset($this->getMigrationFiles($paths)[self::IDENTITY_MIGRATION])) {
            $connection = $this->resolveConnection($this->getConnection());
            $schema = $connection->getSchemaBuilder();
            if ($schema->hasTable('users') && $schema->hasColumn('users', 'email')
                && $connection->table('users')->whereNull('email')->exists()) {
                throw new RuntimeException(
                    'Rollback dihentikan sebelum tabel dihapus: ada akun tanpa email sehingga users.email tidak dapat dikembalikan menjadi NOT NULL. '
                    .'Untuk memasang perubahan baru gunakan php artisan migrate. '
                    .'Jika memang perlu menurunkan schema akun, lengkapi email yang sah terlebih dahulu; jangan memakai email palsu.',
                );
            }
        }

        return parent::rollbackMigrations($migrations, $paths, $options);
    }
}
