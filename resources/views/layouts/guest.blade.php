<!DOCTYPE html>
<html lang="id" class="h-full overflow-y-scroll">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Menetapkan judul tab konstan tanpa nama halaman -->
    <title>myinventaris</title>
    <meta name="description" content="@yield('meta_description', 'Website — Sistem Inventaris Laboratorium RPL SMKN 9 Malang')">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#111827" media="(prefers-color-scheme: dark)">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Turbo Prefetch: halaman mulai dimuat saat hover link → klik terasa instan --}}
    <meta name="turbo-prefetch" content="eager">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Optimasi Performa Lighthouse: Preconnect Google Fonts agar dimuat cepat tanpa menyebabkan FOUT (Flash of Unstyled Text) yang merusak Speed Index -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script>
        (() => {
            const temaTersimpan = localStorage.getItem('tema');
            const gunakanDark =
                temaTersimpan === 'gelap' ||
                (temaTersimpan !== 'terang' && window.matchMedia('(prefers-color-scheme: dark)').matches);

            document.documentElement.classList.toggle('dark', gunakanDark);
            document.documentElement.style.colorScheme = gunakanDark ? 'dark' : 'light';
        })();

        function guestLayout() {
            return {
                isDark: document.documentElement.classList.contains('dark'),

                init() {
                    this.applyDark();
                },

                toggleDark() {
                    this.isDark = !this.isDark;
                    localStorage.setItem('tema', this.isDark ? 'gelap' : 'terang');
                    this.applyDark();
                },

                applyDark() {
                    document.documentElement.classList.toggle('dark', this.isDark);
                    document.documentElement.style.colorScheme = this.isDark ? 'dark' : 'light';
                },
            };
        }
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="h-full bg-slate-50 font-sans antialiased text-slate-800 dark:bg-gray-950 dark:text-gray-100">
    <div x-data="guestLayout()" x-init="init()" class="min-h-screen">
        <main>
        <div class="pointer-events-none fixed right-4 top-4 z-[100] space-y-2">
            @if (session('sukses'))
                <div x-data="{ tampil: true }" x-cloak x-show="tampil" x-init="setTimeout(() => tampil = false, 3500)"
                    x-transition:enter="transition-transform transition-opacity duration-200 ease-out"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition-opacity duration-150 ease-in" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="pointer-events-auto flex max-w-sm items-start gap-3 rounded-2xl border border-emerald-100 bg-white px-4 py-3 shadow-xl shadow-emerald-500/5 dark:border-emerald-900/40 dark:bg-gray-900">
                    <i class="bi bi-check-circle-fill mt-0.5 text-emerald-500"></i>

                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                            Berhasil
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ session('sukses') }}
                        </p>
                    </div>

                    <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah text-gray-400 menjadi 500 untuk rasio kontras warna, dan menambah area sentuh minimum 44px -->
                    <button type="button" class="ml-1 flex min-h-[44px] min-w-[44px] items-center justify-center text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        @click="tampil = false" aria-label="Tutup notifikasi sukses" title="Tutup">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            @endif

            @if (session('galat'))
                <div x-data="{ tampil: true }" x-cloak x-show="tampil" x-init="setTimeout(() => tampil = false, 3500)"
                    x-transition:enter="transition-transform transition-opacity duration-200 ease-out"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition-opacity duration-150 ease-in" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="pointer-events-auto flex max-w-sm items-start gap-3 rounded-2xl border border-red-100 bg-white px-4 py-3 shadow-xl shadow-red-500/5 dark:border-red-900/40 dark:bg-gray-900">
                    <i class="bi bi-exclamation-circle-fill mt-0.5 text-red-500"></i>

                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-red-700 dark:text-red-400">
                            Terjadi Kesalahan
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ session('galat') }}
                        </p>
                    </div>

                    <!-- [OPTIMASI LIGHTHOUSE - ACCESSIBILITY]: Mengubah text-gray-400 menjadi 500 untuk rasio kontras warna, dan menambah area sentuh minimum 44px -->
                    <button type="button" class="ml-1 flex min-h-[44px] min-w-[44px] items-center justify-center text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        @click="tampil = false" aria-label="Tutup notifikasi galat" title="Tutup">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            @endif
        </div>

        @yield('content')
        </main>
    </div>

    @stack('modals')
    @stack('scripts')
</body>

</html>
