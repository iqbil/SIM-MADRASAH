<?php

namespace Tests\Unit;

use App\Services\AttendancePhotoPipeline;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AttendancePhotoPipelineTest extends TestCase
{
    public function test_invalid_types_and_excessive_pixels_are_rejected_before_decoding(): void
    {
        $largePng = "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.pack('NNCCCCC', 5000, 4001, 8, 2, 0, 0, 0).pack('N', 0);
        $samples = [
            ['fake.jpg', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7')],
            ['oversized.png', $largePng],
            ['broken.jpg', 'not an image'],
            ['empty.png', ''],
        ];
        foreach ($samples as [$name, $bytes]) {
            try {
                app(AttendancePhotoPipeline::class)->process(UploadedFile::fake()->createWithContent($name, $bytes), 1280, 80, 307200);
                $this->fail('Foto tidak valid tidak boleh diterima: '.$name);
            } catch (HttpException $error) {
                $this->assertSame(422, $error->getStatusCode());
            }
        }
    }
}
