<!DOCTYPE html>
<html lang="id" class="h-full" x-data="guestLayout()" x-init="init()">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trim($__env->yieldContent('title', 'Website')) }} — {{ config('app.name', 'Shiro') }}</title>
    <meta name="description" content="@yield('meta_description', 'Website — Sistem Inventaris Laboratorium RPL SMKN 9 Malang')">
    <meta name="color-scheme" content="light dark">

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

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="h-full bg-gray-100 font-sans antialiased text-gray-800 dark:bg-gray-900 dark:text-gray-100">
    <main class="min-h-screen">
        <div class="pointer-events-none fixed right-4 top-4 z-[100] space-y-2">
            @if (session('sukses'))
                <div x-data="{ tampil: true }" x-cloak x-show="tampil" x-init="setTimeout(() => tampil = false, 3500)"
                    x-transition:enter="transition-transform transition-opacity duration-200 ease-out"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition-opacity duration-150 ease-in" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="pointer-events-auto flex max-w-sm items-start gap-2.5 rounded-lg border border-emerald-200 bg-white px-4 py-3 shadow-lg dark:border-emerald-900/40 dark:bg-gray-800">
                    <i class="bi bi-check-circle-fill mt-0.5 text-emerald-500"></i>

                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                            Berhasil
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ session('sukses') }}
                        </p>
                    </div>

                    <button type="button" class="ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
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
                    class="pointer-events-auto flex max-w-sm items-start gap-2.5 rounded-lg border border-red-200 bg-white px-4 py-3 shadow-lg dark:border-red-900/40 dark:bg-gray-800">
                    <i class="bi bi-exclamation-circle-fill mt-0.5 text-red-500"></i>

                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-red-700 dark:text-red-400">
                            Terjadi Kesalahan
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ session('galat') }}
                        </p>
                    </div>

                    <button type="button" class="ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        @click="tampil = false" aria-label="Tutup notifikasi galat" title="Tutup">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            @endif
        </div>

        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>
