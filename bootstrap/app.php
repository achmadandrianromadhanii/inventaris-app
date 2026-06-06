<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // [OPTIMASI LIGHTHOUSE]: Menambahkan kompresi GZIP untuk semua output agar super ringan
        $middleware->append(\App\Http\Middleware\GzipResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // [VERCEL DEBUGGING]: Paksa cetak teks error asli tanpa merender HTML View
        // agar kita tahu pasti letak class yang bentrok (case-sensitive) di Linux.
        if (isset($_ENV['VERCEL']) || getenv('VERCEL')) {
            $exceptions->render(function (\Throwable $e) {
                echo "<div style='font-family:monospace; padding: 20px; background: #ffebee; color: #b71c1c;'>";
                echo '<h2>🔴 VERCEL FATAL CRASH REPORT</h2>';
                echo '<b>Error Message:</b> '.$e->getMessage().'<br><br>';
                echo '<b>File:</b> '.$e->getFile().' pada baris '.$e->getLine().'<br><br>';
                echo '<b>Stack Trace:</b><br><pre>'.$e->getTraceAsString().'</pre>';
                echo '</div>';
                exit;
            });
        }
    })->create();

// [VERCEL OPTIMIZATION]: Vercel menggunakan sistem Read-Only (hanya bisa dibaca).
// Jika terdeteksi berjalan di Vercel, kita belokkan seluruh operasi penulisan file
// (Cache, Session, Views) ke folder /tmp/storage agar tidak terjadi "Crash 500".
if (isset($_ENV['VERCEL']) || getenv('VERCEL')) {
    $storagePath = $_ENV['APP_STORAGE'] ?? '/tmp/storage';
    $app->useStoragePath($storagePath);

    // Memastikan folder wajib Laravel terbentuk di /tmp Vercel agar tidak error saat boot
    $directories = [
        "{$storagePath}/app/public",
        "{$storagePath}/framework/cache/data",
        "{$storagePath}/framework/sessions",
        "{$storagePath}/framework/testing",
        "{$storagePath}/framework/views",
        "{$storagePath}/logs",
    ];

    foreach ($directories as $dir) {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

return $app;
