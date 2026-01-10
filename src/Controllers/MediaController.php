<?php

namespace Bale\Core\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController
{
    public function show($path)
    {
        $path = urldecode($path);

        // Bersihkan path dari karakter null byte yang mungkin terselip
        $path = str_replace(chr(0), '', $path);

        $disk = Storage::disk('s3');

        try {
            // Langsung coba baca stream untuk mengurangi round-trip ke S3
            // Kita HILANGKAN $disk->mimeType() karena itu memicu request HEAD tambahan
            // yang bisa gagal jika S3 mengalami latency "read-after-write" pada metadata.
            $stream = $disk->readStream($path);

            // Tebak mime type dari ekstensi file secara manual (lokal) agar tidak request ke S3
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                'bmp' => 'image/bmp',
                'pdf' => 'application/pdf',
            ];
            $mime = $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';

            return response()->stream(function () use ($stream) {
                // ob_clean() kadang bermasalah di production jika buffer kosong/headers sent
                // Kita gunakan catch silent saja jika gagal clean
                try {
                    if (ob_get_level())
                        ob_clean();
                } catch (\Throwable $t) {
                }

                fpassthru($stream);
                fclose($stream);
            }, 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'max-age=31536000, public', // Cache agresif (1 tahun)
            ]);

        } catch (\Throwable $th) {
            // Log error sebenarnya untuk debugging di production
            \Illuminate\Support\Facades\Log::error("Media load failed for path: $path. Error: " . $th->getMessage());

            abort(404);
        }
    }
}
