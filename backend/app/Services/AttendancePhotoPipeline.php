<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;

/**
 * Pipeline bukti foto absensi (SRS FR-05-05): validasi gambar, perbaiki
 * orientasi EXIF, skala sisi terpanjang tanpa upscale, enkode WebP dengan
 * kualitas menurun hingga batas ukuran, hapus metadata. Memakai ekstensi GD
 * bila tersedia; jika tidak, biner ImageMagick (magick/convert) — jalur
 * dapat diatur lewat environment ATTENDANCE_IMAGEMAGICK_BIN.
 */
class AttendancePhotoPipeline
{
    /**
     * @return array{bytes: string, width: int, height: int, quality: int}
     */
    public function process(UploadedFile $upload, int $maxDimension, int $startQuality, int $maxBytes): array
    {
        $input = $upload->getContent();
        abort_unless($input !== '' && strlen($input) <= 10485760, 422, 'Berkas foto kosong atau melebihi 10 MiB.');

        if (function_exists('imagewebp') && function_exists('imagecreatefromstring')) {
            return $this->processWithGd($input, $maxDimension, $startQuality, $maxBytes);
        }

        return $this->processWithImagick($input, $maxDimension, $startQuality, $maxBytes);
    }

    /** @return array{bytes: string, width: int, height: int, quality: int} */
    private function processWithGd(string $input, int $maxDimension, int $startQuality, int $maxBytes): array
    {
        $image = @imagecreatefromstring($input);
        abort_unless($image !== false, 422, 'Berkas bukan gambar yang dapat dibaca.');
        $image = $this->applyExifOrientation($image, $input);
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxDimension / max($width, $height));
        if ($scale < 1) {
            $resized = imagecreatetruecolor((int) round($width * $scale), (int) round($height * $scale));
            imagecopyresampled($resized, $image, 0, 0, 0, 0, imagesx($resized), imagesy($resized), $width, $height);
            imagedestroy($image);
            $image = $resized;
        }
        $width = imagesx($image);
        $height = imagesy($image);
        for ($quality = max(1, min(100, $startQuality)); $quality >= 60; $quality--) {
            ob_start();
            imagewebp($image, null, $quality);
            $bytes = (string) ob_get_clean();
            if (strlen($bytes) <= $maxBytes) {
                imagedestroy($image);

                return ['bytes' => $bytes, 'width' => $width, 'height' => $height, 'quality' => $quality];
            }
        }
        imagedestroy($image);
        abort(422, 'Foto tetap melebihi batas ukuran setelah kompresi; ambil ulang dengan pencahayaan lebih sederhana.');
    }

    /** @return array{bytes: string, width: int, height: int, quality: int} */
    private function processWithImagick(string $input, int $maxDimension, int $startQuality, int $maxBytes): array
    {
        $binary = $this->imagickBinary();
        $probe = Process::input($input)->run([$binary, 'identify', '-format', '%w %h', '-']);
        abort_unless($probe->successful(), 422, 'Berkas bukan gambar yang dapat dibaca.');
        [$width, $height] = array_map('intval', explode(' ', trim($probe->output())));

        for ($quality = max(1, min(100, $startQuality)); $quality >= 60; $quality--) {
            $result = Process::input($input)->timeout(30)->run(
                [$binary, '-', '-auto-orient', '-resize', $maxDimension.'x'.$maxDimension.'>', '-strip', '-quality', (string) $quality, 'webp:-'],
            );
            $bytes = $result->output();
            if ($result->successful() && $bytes !== '' && strlen($bytes) <= $maxBytes) {
                $final = Process::input($bytes)->run([$binary, 'identify', '-format', '%w %h', '-']);

                return ['bytes' => $bytes,
                    'width' => $final->successful() ? (int) explode(' ', trim($final->output()))[0] : $width,
                    'height' => $final->successful() ? (int) explode(' ', trim($final->output()))[1] : $height,
                    'quality' => $quality];
            }
        }
        abort(422, 'Foto tetap melebihi batas ukuran setelah kompresi; ambil ulang dengan pencahayaan lebih sederhana.');
    }

    /** @param resource $image @return resource */
    private function applyExifOrientation($image, string $input)
    {
        if (! function_exists('exif_read_data') || ! str_starts_with($input, "\xFF\xD8")) {
            return $image;
        }
        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($input));
        $orientation = $exif['Orientation'] ?? 1;
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };
        if ($rotated !== null) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    private function imagickBinary(): string
    {
        $candidates = array_values(array_filter([
            env('ATTENDANCE_IMAGEMAGICK_BIN'),
            trim((string) Process::run('command -v magick')->output()),
            trim((string) Process::run('command -v convert')->output()),
            getenv('HOME') ? getenv('HOME').'/.config/FlyEnv/env/python/bin/magick' : null,
        ]));

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && is_executable($candidate)) {
                return $candidate;
            }
        }

        abort(500, 'Ekstensi pemrosesan gambar tidak tersedia; aktifkan GD atau pasang ImageMagick.');
    }
}
