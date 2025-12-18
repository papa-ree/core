<?php

namespace Bale\Core\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController
{
    public function show($path)
    {
        $path = urldecode($path);

        $disk = Storage::disk('s3');

        if (!$disk->exists($path)) {
            abort(404);
        }

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
    }
}
