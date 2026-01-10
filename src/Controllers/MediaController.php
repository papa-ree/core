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
            // Langsung coba baca stream untuk mengurangi 1 round-trip 'exists' check
            // dan menghindari error 'UnableToCheckExistence' jika metadata bermasalah
            $stream = $disk->readStream($path);
            $mime = $disk->mimeType($path);

            return response()->stream(function () use ($stream) {
                ob_clean(); // FIX
                fpassthru($stream);
                fclose($stream);
            }, 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'max-age=3600, public',
            ]);

        } catch (\Throwable $th) {
            // Jika file tidak ada atau error lain, return 404
            // info($th->getMessage());
            abort(404);
        }
    }
}
