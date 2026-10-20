<?php

namespace Tests\Unit;

use App\Models\BkNote;
use App\Models\EmployeeAttendanceCapture;
use App\Models\ExamPackageKey;
use App\Models\File;
use App\Models\PayrollLineSource;
use App\Models\QuestionKey;
use App\Models\QuestionVersion;
use App\Models\SubstituteAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DomainModelTest extends TestCase
{
    public function test_money_and_jp_are_decimal_strings_and_dates_keep_their_semantics(): void
    {
        $source = new PayrollLineSource([
            'quantity_snapshot' => '0.975',
            'rate_snapshot' => '25000.00',
            'amount_snapshot' => '24375.00',
            'source_date' => '2026-10-20',
        ]);
        $source->created_at = CarbonImmutable::parse('2026-10-20 00:01:02.123456', 'UTC');

        $this->assertSame('0.975', $source->quantity_snapshot);
        $this->assertSame('25000.00', $source->rate_snapshot);
        $this->assertSame('24375.00', $source->amount_snapshot);
        $this->assertInstanceOf(CarbonImmutable::class, $source->source_date);
        $this->assertSame('2026-10-20', $source->toArray()['source_date']);
        $this->assertSame('2026-10-20T00:01:02.123456Z', $source->toArray()['created_at']);
        $this->assertSame('Y-m-d H:i:s.u', $source->getDateFormat());
    }

    public function test_answer_keys_and_internal_bk_content_are_hidden_from_serialization(): void
    {
        $key = new QuestionKey(['correct_option_ids' => [11], 'answer_text' => 'Jawaban rahasia', 'explanation' => 'Pembahasan privat']);
        $packageKey = new ExamPackageKey(['correct_package_option_ids' => [22], 'correct_labels_snapshot' => ['B'], 'answer_snapshot' => 'Jawaban final']);
        $note = new BkNote(['internal_content' => 'Catatan konseling privat']);
        $version = new QuestionVersion(['body' => 'Naskah soal']);
        $version->setRelation('key', $key);

        $this->assertSame([11], $key->correct_option_ids);
        $this->assertArrayNotHasKey('answer_text', $key->toArray());
        $this->assertArrayNotHasKey('correct_option_ids', $key->toArray());
        $this->assertArrayNotHasKey('correct_labels_snapshot', $packageKey->toArray());
        $this->assertArrayNotHasKey('answer_snapshot', $packageKey->toArray());
        $this->assertArrayNotHasKey('internal_content', $note->toArray());
        $this->assertArrayNotHasKey('key', $version->toArray());
    }

    public function test_photo_paths_and_gps_are_available_to_services_but_hidden_in_arrays(): void
    {
        $file = new File(['storage_disk' => 'private', 'storage_path' => 'attendance/private.webp', 'mime_type' => 'image/webp']);
        $capture = new EmployeeAttendanceCapture(['latitude' => '-6.2000000', 'longitude' => '106.8166667', 'accuracy_meters' => '15.00', 'policy_snapshot' => ['requires_gps' => true]]);
        $capture->setRelation('photoFile', $file);

        $this->assertSame('-6.2000000', $capture->latitude);
        $this->assertSame('attendance/private.webp', $capture->photoFile->storage_path);
        $this->assertSame(['requires_gps' => true], $capture->policy_snapshot);
        foreach (['latitude', 'longitude', 'accuracy_meters', 'policy_snapshot', 'photo_file'] as $field) {
            $this->assertArrayNotHasKey($field, $capture->toArray());
        }
        $this->assertArrayNotHasKey('storage_path', $file->toArray());
        $this->assertArrayNotHasKey('storage_disk', $file->toArray());
    }

    public function test_user_credentials_are_hashed_and_account_controls_are_guarded(): void
    {
        $user = new User(['name' => 'Uji', 'username' => 'operator', 'password' => 'secret-value', 'status' => 'disabled', 'must_change_password' => false]);

        $this->assertTrue(Hash::check('secret-value', $user->password));
        $this->assertSame('active', $user->status);
        $this->assertFalse($user->isFillable('status'));
        $this->assertFalse($user->isFillable('must_change_password'));
        $this->assertFalse($user->isFillable('email_verified_at'));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_generated_markers_and_server_fields_cannot_be_mass_assigned(): void
    {
        $assignment = new SubstituteAssignment(['active_marker' => 1, 'assigned_by' => 99, 'reason' => 'Pengganti']);
        $capture = new EmployeeAttendanceCapture(['recorded_by' => 99, 'recorded_at' => '2026-10-20 00:00:00', 'event_type' => 'check_in']);

        $this->assertFalse($assignment->isFillable('active_marker'));
        $this->assertArrayNotHasKey('active_marker', $assignment->getAttributes());
        $this->assertArrayNotHasKey('assigned_by', $assignment->getAttributes());
        $this->assertArrayNotHasKey('recorded_by', $capture->getAttributes());
        $this->assertArrayNotHasKey('recorded_at', $capture->getAttributes());
        $this->assertSame('check_in', $capture->event_type);
    }
}
