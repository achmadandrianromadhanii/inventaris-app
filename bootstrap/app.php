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
        //
    })->create();

// [VERCEL OPTIMIZATION]: Vercel menggunakan sistem Read-Only (hanya bisa dibaca).
// Jika terdeteksi berjalan di Vercel, kita belokkan seluruh operasi penulisan file
// (Cache, Session, Views) ke folder /tmp/storage agar tidak terjadi "Crash 500".
if (isset($_ENV['VERCEL']) || getenv('VERCEL')) {
    $app->useStoragePath($_ENV['APP_STORAGE'] ?? '/tmp/storage');
}

return $app;
