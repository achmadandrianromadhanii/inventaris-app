<!DOCTYPE html>
<html lang="id" class="h-full overflow-y-scroll">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Menetapkan judul tab konstan tanpa nama halaman -->
    <title>myinventaris</title>
    <meta name="description" content="@yield('meta_description', 'Website Sistem Inventaris Laboratorium RPL SMKN 9 Malang')">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#111827" media="(prefers-color-scheme: dark)">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Turbo Prefetch: halaman mulai dimuat saat kursor HOVER di link (sebelum diklik).
         Hasilnya: saat klik, halaman sudah hampir selesai dimuat → terasa instan.
         Nilai "eager" = prefetch langsung saat hover. --}}
    <meta name="turbo-prefetch" content="eager">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Optimasi Performa Lighthouse: Preload & Preconnect Google Fonts (Menggantikan @import) -->
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

        function layoutApp() {
            return {
                isDark: document.documentElement.classList.contains('dark'),
                sidebarOpen: false,
                clockTime: '',
                clockDate: '',
                clockTimer: null,
                resizeHandler: null,
                mediaQuery: null,
                timeFormatter: new Intl.DateTimeFormat('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                }),
                dateFormatter: new Intl.DateTimeFormat('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                }),

                // [UPDATE] State untuk Notifikasi Real-Time Peminjaman & Barang Masuk
                badgePeminjaman: parseInt(localStorage.getItem('badgePeminjaman')) || 0,
                badgeKelolaBarang: parseInt(localStorage.getItem('badgeKelolaBarang')) || 0,
                notifikasiBaru: null,
                showNotifikasi: false,
                echoInstance: null,

                init() {
                    // Reset badge jika kita sedang membuka halaman terkait
                    if (window.location.pathname.startsWith('/peminjaman')) {
                        this.badgePeminjaman = 0;
                        localStorage.setItem('badgePeminjaman', '0');
                    }
                    if (window.location.pathname.startsWith('/barang')) {
                        this.badgeKelolaBarang = 0;
                        localStorage.setItem('badgeKelolaBarang', '0');
                    }

                    // Sinkronisasi badge antar tab browser
                    window.addEventListener('storage', (e) => {
                        if (e.key === 'badgePeminjaman') {
                            this.badgePeminjaman = parseInt(e.newValue) || 0;
                        }
                        if (e.key === 'badgeKelolaBarang') {
                            this.badgeKelolaBarang = parseInt(e.newValue) || 0;
                        }
                    });

                    this.applyDark();
                    this.updateClock();
                    this.initEcho(); // Inisialisasi listener WebSocket


                    this.clockTimer = window.setInterval(() => {
                        this.updateClock();
                    }, 1000);

                    this.mediaQuery = window.matchMedia('(min-width: 1024px)');

                    this.resizeHandler = () => {
                        if (this.mediaQuery.matches) {
                            this.sidebarOpen = false;
                            document.body.classList.remove('overflow-hidden');
                        }
                    };

                    window.addEventListener('resize', this.resizeHandler, {
                        passive: true
                    });



                    this.$watch('sidebarOpen', (value) => {
                        if (!this.mediaQuery.matches) {
                            document.body.classList.toggle('overflow-hidden', value);
                            return;
                        }

                        document.body.classList.remove('overflow-hidden');
                    });

                    this.$watch('isDark', (value) => {
                        localStorage.setItem('tema', value ? 'gelap' : 'terang');
                        this.applyDark();

                        if (Array.isArray(window.chartInstances)) {
                            for (const chart of window.chartInstances) {
                                if (chart && typeof chart.update === 'function') {
                                    chart.update('none');
                                }
                            }
                        }
                    });

                    window.chartInstances = Array.isArray(window.chartInstances) ? window.chartInstances : [];
                },

                toggleDark() {
                    this.isDark = !this.isDark;
                },

                destroy() {
                    if (this.clockTimer) window.clearInterval(this.clockTimer);
                    if (this.resizeHandler) window.removeEventListener('resize', this.resizeHandler);
                },

                applyDark() {
                    document.documentElement.classList.toggle('dark', this.isDark);
                    document.documentElement.style.colorScheme = this.isDark ? 'dark' : 'light';
                },

                updateClock() {
                    const now = new Date();

                    this.clockTime = this.timeFormatter.format(now);
                    this.clockDate = this.dateFormatter.format(now);
                },

                openSidebar() {
                    if (!this.mediaQuery.matches) {
                        this.sidebarOpen = true;
                    }
                },

                closeSidebar() {
                    this.sidebarOpen = false;
                },

                // [UPDATE] Mengaktifkan WebSocket Listener secara lazy-load tanpa meledakkan memori
                async initEcho() {
                    if (window.adminEchoInstance) {
                        this.echoInstance = window.adminEchoInstance;
                        this.bindEchoListener();
                        return;
                    }

                    if (window.isEchoInitializing) return;
                    window.isEchoInitializing = true;
                    
                    try {
                        const { Echo, Pusher } = await window.loadEcho();
                        window.Pusher = Pusher;
                        
                        window.adminEchoInstance = new Echo({
                            broadcaster: 'pusher',
                            // [MIGRASI PUSHER]: Menggunakan kredensial Pusher dari config/broadcasting.php
                            key: '{{ config("broadcasting.connections.pusher.key") }}',
                            cluster: '{{ config("broadcasting.connections.pusher.options.cluster", "mt1") }}',
                            forceTLS: true
                        });

                        this.echoInstance = window.adminEchoInstance;
                        this.bindEchoListener();
                    } catch (error) {
                        console.error('Gagal memuat sistem notifikasi real-time:', error);
                        window.isEchoInitializing = false;
                    }
                },

                bindEchoListener() {
                    // Cegah duplikasi event jika berpindah halaman dengan Turbo
                    this.echoInstance.channel('peminjaman').stopListening('.peminjaman.updated');

                    this.echoInstance.channel('peminjaman')
                        .listen('.peminjaman.updated', (e) => {
                            // Jika admin sedang membuka halaman peminjaman, kita abaikan penambahan angka badge
                            if (!window.location.pathname.startsWith('/peminjaman')) {
                                this.badgePeminjaman++;
                                localStorage.setItem('badgePeminjaman', this.badgePeminjaman);
                            }
                            
                            this.notifikasiBaru = e;
                            this.showNotifikasi = true;
                            setTimeout(() => this.showNotifikasi = false, 5000);
                        });

                    // Listener untuk event Inventaris (Barang Masuk)
                    this.echoInstance.channel('inventaris').stopListening('.inventaris.updated');

                    this.echoInstance.channel('inventaris')
                        .listen('.inventaris.updated', (e) => {
                            // Tambah badge ke menu "Kelola Barang" jika tipe-nya 'masuk'
                            // Kita hapus pengecekan URL agar user tetap tahu ada barang masuk 
                            // meskipun sedang berada di halaman kelola barang (agar mereka tahu harus refresh)
                            if (e.tipe === 'masuk') {
                                this.badgeKelolaBarang++;
                                localStorage.setItem('badgeKelolaBarang', this.badgeKelolaBarang);
                            }
                            
                            this.notifikasiBaru = e;
                            this.showNotifikasi = true;
                            setTimeout(() => this.showNotifikasi = false, 5000);
                        });
                },

                async handleGlobalPagination(event) {
                    const link = event.target.closest('nav[role="navigation"] a');
                    if (!link) return;

                    // Jangan intercept jika ada form unit yang perlu disave (di-handle khusus di unit.blade.php)
                    if (event.target.closest('.ajax-pagination a') || event.target.closest('[x-data*="simpanMassal"]')) return;

                    event.preventDefault();
                    const url = link.href;
                    if (!url || url === '#' || url === window.location.href) return;

                    const mainContent = document.querySelector('main');
                    if (!mainContent) {
                        window.location.href = url;
                        return;
                    }

                    // Animasi keluar
                    mainContent.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                    mainContent.style.opacity = '0.3';
                    mainContent.style.transform = 'translateY(10px)';

                    try {
                        const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!response.ok) throw new Error('Network error');
                        
                        const html = await response.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        const newMain = doc.querySelector('main');
                        if (newMain) {
                            mainContent.innerHTML = newMain.innerHTML;
                            window.history.pushState({}, '', url);
                        } else {
                            window.location.href = url;
                        }
                    } catch (e) {
                        console.error('AJAX Pagination gagal', e);
                        window.location.href = url; // Fallback
                    } finally {
                        // Reset scroll and animasi masuk
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        mainContent.style.opacity = '1';
                        mainContent.style.transform = 'translateY(0)';
                    }
                }
            };
        }
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Memaksa scrollbar vertikal selalu tampil untuk mencegah Layout Shift (CLS) */
        html {
            overflow-y: scroll !important;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="h-full bg-slate-50 font-sans antialiased text-slate-800 dark:bg-gray-950 dark:text-gray-100">
    <div x-data="layoutApp()" x-init="init()" @click="handleGlobalPagination($event)" class="h-full">
    <x-sidebar :mobile="false" />

    <div x-cloak class="fixed inset-0 z-50 lg:hidden"
        :class="sidebarOpen ? 'pointer-events-auto' : 'pointer-events-none'" @keydown.escape.window="closeSidebar()"
        aria-modal="true" role="dialog">
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity duration-200 ease-out"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-150 ease-in" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="absolute inset-0 bg-gray-900/60" @click="closeSidebar()"
            aria-hidden="true"></div>

        <div x-show="sidebarOpen" x-transition:enter="transition-transform duration-200 ease-out"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform duration-150 ease-in" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full" class="absolute inset-y-0 left-0 w-60 max-w-[85vw]" @click.stop>
            <x-sidebar :mobile="true" />
        </div>
    </div>

    <div class="flex min-h-screen flex-col lg:pl-60">
        <x-topbar />

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <div class="pointer-events-none fixed right-4 top-4 z-[100] space-y-2">
                
                <!-- [UPDATE] Notifikasi Pop-up Realtime (WebSocket) -->
                <div x-cloak x-show="showNotifikasi" 
                    x-transition:enter="transition-transform transition-opacity duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                    x-transition:enter-start="opacity-0 translate-x-10 scale-95"
                    x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                    x-transition:leave="transition-opacity transition-transform duration-200 ease-in"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                    class="pointer-events-auto flex max-w-sm items-start gap-3 rounded-2xl border border-indigo-100 bg-white px-4 py-3 shadow-2xl shadow-indigo-500/10 dark:border-indigo-900/40 dark:bg-gray-900">
                    
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-500/10">
                        <i class="bi bi-bell-fill text-indigo-500 dark:text-indigo-400"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-400" 
                               x-text="(notifikasiBaru?.aksi === 'pinjam' ? 'Ajuan Peminjaman Baru' : (notifikasiBaru?.tipe === 'masuk' ? 'Barang Masuk Baru' : (notifikasiBaru?.tipe === 'keluar' ? 'Barang Keluar' : 'Pembaruan Data')))"></p>
                            <span class="text-[10px] font-medium text-gray-500" x-text="notifikasiBaru?.waktu"></span>
                        </div>
                        <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300 line-clamp-2" x-text="notifikasiBaru?.pesan"></p>
                    </div>

                    <button type="button" class="ml-1 shrink-0 text-gray-400 transition-colors hover:text-gray-600 dark:hover:text-gray-200"
                        @click="showNotifikasi = false" aria-label="Tutup notifikasi" title="Tutup">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
                <!-- Akhir Notifikasi Realtime -->

                @if (session('sukses'))
                    <div x-data="{ tampil: true }" x-cloak x-show="tampil" x-init="setTimeout(() => tampil = false, 3500)"
                        x-transition:enter="transition-transform transition-opacity duration-200 ease-out"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition-opacity duration-150 ease-in"
                        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
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
                        x-transition:leave="transition-opacity duration-150 ease-in"
                        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
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

                        <button type="button" class="ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                            @click="tampil = false" aria-label="Tutup notifikasi galat" title="Tutup">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>
                @endif
                
                <!-- Toast dinamis dari AJAX -->
                <div x-data="{ toasts: [] }" 
                     @tampilkan-toast.window="
                        const t = { id: Date.now(), pesan: $event.detail.pesan, tipe: $event.detail.tipe };
                        toasts.push(t);
                        setTimeout(() => { toasts = toasts.filter(x => x.id !== t.id); }, 3500);
                     " class="flex flex-col gap-2">
                    <template x-for="toast in toasts" :key="toast.id">
                        <div x-show="true"
                            x-transition:enter="transition-transform transition-opacity duration-200 ease-out"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition-opacity duration-150 ease-in"
                            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                            class="pointer-events-auto flex max-w-sm items-start gap-3 rounded-2xl border bg-white px-4 py-3 shadow-xl dark:bg-gray-900"
                            :class="toast.tipe === 'success' ? 'border-emerald-100 shadow-emerald-500/5 dark:border-emerald-900/40' : 'border-red-100 shadow-red-500/5 dark:border-red-900/40'">
                            
                            <i class="bi mt-0.5" :class="toast.tipe === 'success' ? 'bi-check-circle-fill text-emerald-500' : 'bi-exclamation-circle-fill text-red-500'"></i>

                            <div class="min-w-0">
                                <p class="text-sm font-semibold" :class="toast.tipe === 'success' ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'" x-text="toast.tipe === 'success' ? 'Berhasil' : 'Terjadi Kesalahan'"></p>
                                <p class="text-sm text-gray-600 dark:text-gray-300" x-text="toast.pesan"></p>
                            </div>

                            <button type="button" class="ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                @click="toasts = toasts.filter(x => x.id !== toast.id)" aria-label="Tutup notifikasi" title="Tutup">
                                <i class="bi bi-x-lg text-xs"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            @yield('content')
        </main>
    </div>



    </div>

    @stack('modals')
    @stack('scripts')
</body>

</html>
