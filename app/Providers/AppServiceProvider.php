<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // [VERCEL OPTIMIZATION]: Vercel berjalan di balik sistem Proxy berlapis.
        // Laravel sering bingung dan mengira ia berjalan di jalur HTTP biasa,
        // sehingga membuat URL gambar/CSS menjadi HTTP dan diblokir oleh browser (Mixed Content).
        // Kode ini memaksa Laravel untuk SELALU menggunakan HTTPS.
        if (isset($_ENV['VERCEL']) || getenv('VERCEL') || env('APP_ENV') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
