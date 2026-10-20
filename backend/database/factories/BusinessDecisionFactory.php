<?php

namespace Database\Factories;

use App\Models\BusinessDecision;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<BusinessDecision> */
class BusinessDecisionFactory extends Factory
{
    protected $model = BusinessDecision::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => 'TEST-'.Str::upper(Str::random(12)),
            'title' => 'Kebijakan fixture pengujian',
            'status' => 'baseline',
            'decision_text' => 'Data sintetis khusus factory, bukan pengesahan operasional.',
            'source_reference' => 'Test factory',
            'recorded_at' => '2026-10-20 00:00:00',
            'version' => 1,
            'effective_from' => '2026-10-20',
        ];
    }
}
