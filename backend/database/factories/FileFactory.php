<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<File> */
class FileFactory extends Factory
{
    protected $model = File::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'storage_disk' => 'private',
            'storage_path' => 'test-fixtures/'.Str::uuid().'.webp',
            'original_name' => 'fixture.webp',
            'mime_type' => 'image/webp',
            'extension' => 'webp',
            'size_bytes' => 102400,
            'sha256' => hash('sha256', (string) Str::uuid()),
            'classification' => 'internal',
            'scan_status' => 'clean',
            'uploaded_by' => User::factory(),
        ];
    }
}
