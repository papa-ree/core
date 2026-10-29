<?php

namespace Bale\Core\Support;

use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Illuminate\Http\Response;

/**
 * Pembungkus tipis dompdf untuk seluruh package Bale.
 *
 * 🔴 Satu-satunya file yang boleh menyebut namespace `Barryvdh`.
 *
 * `barryvdh/laravel-dompdf` adalah dependensi `bale/core`. Package lain cukup
 * memanggil kelas ini, sehingga kalau mesin PDF diganti (dompdf -> Chromium)
 * yang berubah hanya satu file, bukan setiap pemanggil.
 *
 * 🔴 View yang dirender TIDAK boleh memakai Tailwind atau komponen Blade
 * ber-Alpine. dompdf hanya mengerti HTML + CSS dasar; kelas Tailwind tidak
 * punya stylesheet yang dimuat, dan hasilnya dokumen tanpa tata letak.
 */
final class Pdf
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function download(
        string $view,
        array $data = [],
        string $filename = 'document.pdf',
        string $paper = 'a4',
        string $orientation = 'portrait',
    ): Response {
        return DomPdf::loadView($view, $data)
            ->setPaper($paper, $orientation)
            ->download($filename);
    }
}
