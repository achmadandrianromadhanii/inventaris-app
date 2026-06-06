<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GzipResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // [OPTIMASI LIGHTHOUSE]:
        // Memastikan server melakukan kompresi GZIP secara otomatis pada respons HTML/JSON.
        // Ini akan memangkas ukuran "Document Request Latency" hingga 80%, sehingga halaman
        // termuat jauh lebih cepat (FCP kilat) dan menghapus peringatan dari Lighthouse.
        if (in_array('gzip', $request->getEncodings()) && function_exists('gzencode')) {
            $content = $response->getContent();
            // Hanya mengompres jika response adalah string dan ukurannya lumayan besar (>1KB)
            if (is_string($content) && strlen($content) > 1024) {
                $response->setContent(gzencode($content, 9));
                $response->headers->add([
                    'Content-Encoding' => 'gzip',
                    'Vary' => 'Accept-Encoding',
                    'Content-Length' => strlen($response->getContent()),
                ]);
            }
        }

        return $response;
    }
}
