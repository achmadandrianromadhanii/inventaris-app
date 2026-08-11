<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GzipResponse
{
    /**
     * [OPTIMASI LIGHTHOUSE — GZIP Middleware]:
     * Mengompres response HTML/JSON agar ukuran transfer lebih kecil (~80% hemat bandwidth).
     * Hanya mengompres response yang memenuhi syarat:
     * - Bukan redirect (302/303) → redirect TIDAK boleh di-gzip, browser akan hang
     * - Bukan binary/stream → file download harus dikirim apa adanya
     * - Ukuran > 1KB → response kecil tidak perlu dikompresi
     * - Browser mendukung gzip (Accept-Encoding: gzip)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // [FIX LOGIN HANG]: Jangan pernah kompresi redirect response (302/303).
        // Browser mengharapkan header Location mentah tanpa encoding.
        if ($response->isRedirection() || $response->isInformational()) {
            return $response;
        }

        // Jangan kompresi file download atau streamed response
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        // Hanya kompresi jika browser mendukung gzip dan fungsi tersedia
        if (in_array('gzip', $request->getEncodings()) && function_exists('gzencode')) {
            $content = $response->getContent();

            // Hanya kompres jika konten berupa string dan cukup besar (> 1KB)
            if (is_string($content) && strlen($content) > 1024) {
                $compressed = gzencode($content, 6); // Level 6 = keseimbangan kecepatan vs ukuran
                $response->setContent($compressed);
                $response->headers->set('Content-Encoding', 'gzip');
                $response->headers->set('Vary', 'Accept-Encoding');
                $response->headers->set('Content-Length', (string) strlen($compressed));
            }
        }

        return $response;
    }
}
