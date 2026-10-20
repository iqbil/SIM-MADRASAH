<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SchoolClass> */
class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => '7-'.Str::upper(Str::random(12)),
            'name' => 'VII A',
            'grade' => 7,
            'is_active' => true,
        ];
    }
}
