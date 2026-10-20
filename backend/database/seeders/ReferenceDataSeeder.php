<?php

namespace Database\Seeders;

use App\Models\AttendanceStatus;
use App\Models\QuestionType;
use App\Models\StudentStatus;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['active', 'Aktif', false],
            ['graduated', 'Lulus', true],
            ['transferred', 'Pindah', true],
            ['withdrawn', 'Keluar', true],
        ] as [$code, $name, $isTerminal]) {
            StudentStatus::query()->firstOrCreate(['code' => $code], [
                'name' => $name,
                'is_terminal' => $isTerminal,
                'is_active' => true,
            ]);
        }

        foreach ([
            'employee' => ['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'cuti' => 'Cuti', 'dinas_luar' => 'Dinas luar'],
            'student' => ['hadir' => 'Hadir', 'sakit' => 'Sakit', 'izin' => 'Izin', 'alpa' => 'Alpa'],
        ] as $domain => $statuses) {
            foreach ($statuses as $code => $name) {
                AttendanceStatus::query()->firstOrCreate(['domain' => $domain, 'code' => $code], [
                    'name' => $name,
                    'is_active' => true,
                ]);
            }
        }

        foreach ([
            ['multiple_choice_single', 'Pilihan ganda satu jawaban', true],
            ['essay', 'Esai', false],
        ] as [$code, $name, $hasOptions]) {
            QuestionType::query()->firstOrCreate(['code' => $code], [
                'name' => $name,
                'has_options' => $hasOptions,
                'is_active' => true,
            ]);
        }
    }
}
