@props(['mobile' => false])

@php
    $user = auth()->user();

    $namaPengguna = $user->nama ?? 'Administrator';

    $inisial = collect(preg_split('/\s+/', trim($namaPengguna)))
        ->filter()
        ->take(2)
        ->map(fn($bagian) => mb_strtoupper(mb_substr($bagian, 0, 1)))
        ->implode('');

    // [UPDATE] UI/UX: Mengubah background menjadi gradient gelap elegan (slate-900 ke slate-950)
    // Ditambahkan shadow-2xl untuk memberi kesan depth (kedalaman/mengambang) pada Sidebar
    // Ini menggantikan warna solid kaku menjadi Premium Dark Mode
    $wrapperClass = $mobile
        ? 'flex h-full w-60 flex-col border-r border-slate-800 bg-gradient-to-b from-slate-900 to-slate-950 shadow-2xl'
        : 'fixed inset-y-0 left-0 z-40 hidden h-screen w-60 flex-col border-r border-slate-800 bg-gradient-to-b from-slate-900 to-slate-950 shadow-2xl lg:flex';

    $sections = [
        [
            'label' => null,
            'id' => 'dashboard',
            'icon' => null,
            'items' => [
                [
                    'label' => 'Dashboard',
                    'icon' => 'bi-grid-1x2-fill',
                    'href' => route('dashboard'),
                    'match' => ['dashboard'],
                ],
            ],
        ],
        [
            'label' => 'Master Data',
            'id' => 'master_data',
            'icon' => null,
            'items' => [
                [
                    'label' => 'Kelola Barang',
                    'icon' => 'bi-circle',
                    'href' => route('barang.index'),
                    'match' => ['barang.*'],
                ],
                [
                    'label' => 'Kategori',
                    'icon' => 'bi-circle',
                    'href' => route('kategori.index'),
                    'match' => ['kategori.*'],
                ],
            ],
        ],
        [
            'label' => 'Transaksi',
            'id' => 'transaksi',
            'icon' => null,
            'items' => [
                [
                    'label' => 'Barang Masuk',
                    'icon' => 'bi-circle',
                    'href' => route('transaksi.masuk'),
                    'match' => ['transaksi.masuk'],
                ],
                [
                    'label' => 'Barang Keluar',
                    'icon' => 'bi-circle',
                    'href' => route('transaksi.keluar'),
                    'match' => ['transaksi.keluar'],
                ],
                [
                    'label' => 'Peminjaman',
                    'icon' => 'bi-circle',
                    'href' => route('peminjaman.index'),
                    'match' => ['peminjaman.*'],
                ],
            ],
        ],
        [
            'label' => 'Lainnya',
            'id' => 'lainnya',
            'icon' => null,
            'items' => [
                [
                    'label' => 'Laporan',
                    'icon' => 'bi-circle',
                    'href' => route('laporan.index'),
                    'match' => ['laporan.*'],
                ],
                [
                    'label' => 'Pengguna',
                    'icon' => 'bi-circle',
                    'href' => route('pengguna.index'),
                    'match' => ['pengguna.*'],
                ],
            ],
        ],
        [
            'label' => 'Publik',
            'id' => 'publik',
            'icon' => null,
            'items' => [
                [
                    'label' => 'Halaman Siswa',
                    'icon' => 'bi-circle',
                    'href' => route('siswa.pinjam'),
                    'match' => ['siswa.*'],
                    'blank' => true,
                ],
            ],
        ],
    ];
@endphp

<aside class="{{ $wrapperClass }}" aria-label="Sidebar navigasi">
    <!-- [UPDATE] UI/UX: Menambahkan group hover effect pada Header Logo -->
    <div class="group flex flex-col items-center border-b border-slate-800 px-4 py-6 transition-colors duration-300 hover:bg-slate-800/40 cursor-pointer">
        <!-- [UPDATE] UI/UX: Logo kini bereaksi secara halus (membesar dan naik sedikit) saat disentuh kursor -->
        <img src="{{ asset('images/logo.webp') }}" alt="Logo SMKN 9 Malang" 
            class="h-16 w-auto object-contain drop-shadow-xl transition-all duration-500 ease-out group-hover:scale-110 group-hover:-translate-y-1"
            width="52" height="80" fetchpriority="high" decoding="async" draggable="false">

        <p class="mt-4 text-center text-sm font-extrabold tracking-wide text-slate-200 transition-colors duration-300 group-hover:text-white">
            SMKN 9 Malang
        </p>

        <p class="text-center text-[10px] font-medium uppercase tracking-wider text-slate-500 transition-colors duration-300 group-hover:text-indigo-400">
            Inventaris Lab RPL
        </p>
    </div>

    <nav class="flex-1 space-y-4 overflow-y-auto px-3 py-5 scrollbar-thin scrollbar-track-transparent scrollbar-thumb-slate-800 hover:scrollbar-thumb-slate-600">
        @foreach ($sections as $section)
            @php
                $isSectionActive = collect($section['items'])->contains(fn($item) => request()->routeIs(...$item['match']));
                $hasPeminjaman = collect($section['items'])->contains(fn($item) => $item['label'] === 'Peminjaman');
                $hasKelolaBarang = collect($section['items'])->contains(fn($item) => $item['label'] === 'Kelola Barang');
            @endphp

            @if ($section['label'])
                <div x-data="{ 
                        open: localStorage.getItem('sidebar_{{ $section['id'] }}') === 'true' || (localStorage.getItem('sidebar_{{ $section['id'] }}') === null && {{ $isSectionActive ? 'true' : 'false' }}) 
                     }" 
                     x-init="$watch('open', value => localStorage.setItem('sidebar_{{ $section['id'] }}', value))"
                     class="flex flex-col">
                     
                    <!-- [UPDATE] UI/UX: Tombol Kategori Induk. Animasi translate-x-1 agar bergeser empuk ke kanan saat di-hover -->
                    <button type="button" @click="open = !open"
                        class="group flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-300 ease-out hover:translate-x-1 hover:bg-slate-800/60 hover:text-white {{ $isSectionActive ? 'text-slate-200' : 'text-slate-400' }}">
                        <div class="flex items-center gap-3">
                            @if ($section['icon'])
                                <!-- [UPDATE] UI/UX: Ikon induk membesar 110% saat menu disentuh -->
                                <i class="bi {{ $section['icon'] }} text-base transition-transform duration-300 ease-out group-hover:scale-110" aria-hidden="true"></i>
                            @endif
                            <span class="tracking-wide">{{ $section['label'] }}</span>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <!-- [UPDATE] Badge Induk: Muncul di kategori (misal Transaksi) jika ditutup dan ada notifikasi -->
                            @if($hasPeminjaman)
                                <span x-cloak x-show="!open && badgePeminjaman > 0" 
                                      x-transition:enter="transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                                      x-transition:enter-start="opacity-0 scale-50"
                                      x-transition:enter-end="opacity-100 scale-100"
                                      class="flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white shadow-lg shadow-red-500/40 dark:shadow-red-500/20"
                                      x-text="badgePeminjaman > 99 ? '99+' : badgePeminjaman">
                                </span>
                            @endif
                            @if($hasKelolaBarang)
                                <span x-cloak x-show="!open && badgeKelolaBarang > 0" 
                                      x-transition:enter="transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                                      x-transition:enter-start="opacity-0 scale-50"
                                      x-transition:enter-end="opacity-100 scale-100"
                                      class="flex h-5 min-w-[20px] items-center justify-center rounded-full bg-emerald-500 px-1.5 text-[10px] font-bold text-white shadow-lg shadow-emerald-500/40 dark:shadow-emerald-500/20"
                                      x-text="badgeKelolaBarang > 99 ? '99+' : badgeKelolaBarang">
                                </span>
                            @endif
                            <i class="bi bi-chevron-down text-[10px] transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]" :class="open ? 'rotate-180 text-indigo-400' : ''"></i>
                        </div>
                    </button>
                    
                    <!-- [UPDATE] UI/UX: Transisi buka/tutup menggunakan kurva elastis cubic-bezier agar terasa lebih alami/hidup -->
                    <div x-cloak x-show="open" 
                         x-transition:enter="transition-all ease-[cubic-bezier(0.34,1.56,0.64,1)] duration-400 origin-top"
                         x-transition:enter-start="opacity-0 scale-y-90 -translate-y-2"
                         x-transition:enter-end="opacity-100 scale-y-100 translate-y-0"
                         x-transition:leave="transition-all ease-in duration-200 origin-top"
                         x-transition:leave-start="opacity-100 scale-y-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-y-90 -translate-y-2"
                         class="mt-1.5 space-y-1 pl-1">
                        @foreach ($section['items'] as $item)
                            @php
                                $isActive = request()->routeIs(...$item['match']);
                            @endphp

                            <!-- [UPDATE] UI/UX: Sub-menu. 
                                 Status AKTIF: Memancarkan gradient neon (indigo ke cyan) dengan efek inset-shadow (Glow).
                                 Status MATI: Meredup tapi bisa bergeser halus (translate-x-1) saat disentuh.
                            -->
                            <a href="{{ $item['href'] }}"
                                @if (!empty($item['blank'])) target="_blank" rel="noopener noreferrer" @endif
                                @if ($mobile && empty($item['blank'])) @click="closeSidebar()" @endif
                                @if ($isActive) aria-current="page" @endif
                                class="group flex items-center gap-3 rounded-xl border-l-2 px-3 py-2.5 pl-8 text-sm font-medium transition-all duration-300 ease-out 
                                {{ $isActive 
                                    ? 'border-indigo-500 bg-gradient-to-r from-indigo-600/20 to-cyan-500/5 text-indigo-300 shadow-[inset_0px_1px_4px_rgba(255,255,255,0.05)]' 
                                    : 'border-transparent text-slate-400 hover:translate-x-1 hover:border-slate-700 hover:bg-slate-800/40 hover:text-slate-200' 
                                }}">
                                
                                <!-- [UPDATE] UI/UX: Ikon bulat kecil. Jika menu aktif, ikonnya akan berpendar cerah (Neon drop-shadow). -->
                                <i class="bi {{ $isActive && $item['icon'] === 'bi-circle' ? 'bi-circle-fill text-[10px] mt-0.5 text-indigo-400 drop-shadow-[0_0_8px_rgba(99,102,241,0.8)]' : $item['icon'] . ' text-sm transition-transform duration-300 group-hover:scale-110' }}" aria-hidden="true"></i>
                                
                                <div class="flex min-w-0 flex-1 items-center justify-between">
                                    <span class="truncate">{{ $item['label'] }}</span>
                                    
                                    <!-- [UPDATE] Badge Realtime Peminjaman & Kelola Barang -->
                                    @if($item['label'] === 'Peminjaman')
                                        <span x-cloak x-show="badgePeminjaman > 0" 
                                              x-transition:enter="transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                                              x-transition:enter-start="opacity-0 scale-50"
                                              x-transition:enter-end="opacity-100 scale-100"
                                              class="ml-2 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white shadow-lg shadow-red-500/40 dark:shadow-red-500/20"
                                              x-text="badgePeminjaman > 99 ? '99+' : badgePeminjaman">
                                        </span>
                                    @elseif($item['label'] === 'Kelola Barang')
                                        <span x-cloak x-show="badgeKelolaBarang > 0" 
                                              x-transition:enter="transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                                              x-transition:enter-start="opacity-0 scale-50"
                                              x-transition:enter-end="opacity-100 scale-100"
                                              class="ml-2 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-emerald-500 px-1.5 text-[10px] font-bold text-white shadow-lg shadow-emerald-500/40 dark:shadow-emerald-500/20"
                                              x-text="badgeKelolaBarang > 99 ? '99+' : badgeKelolaBarang">
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="mb-5 space-y-1">
                    @foreach ($section['items'] as $item)
                        @php
                            $isActive = request()->routeIs(...$item['match']);
                        @endphp

                        <!-- [UPDATE] UI/UX: Menu Utama (tanpa Dropdown) seperti Dashboard. Animasi sama dengan sub-menu (Neon Glow / Hover Slide) -->
                        <a href="{{ $item['href'] }}"
                            @if (!empty($item['blank'])) target="_blank" rel="noopener noreferrer" @endif
                            @if ($mobile && empty($item['blank'])) @click="closeSidebar()" @endif
                            @if ($isActive) aria-current="page" @endif
                            class="group flex items-center gap-3 rounded-xl border-l-2 px-3 py-3 text-sm font-bold transition-all duration-300 ease-out
                            {{ $isActive 
                                ? 'border-indigo-500 bg-gradient-to-r from-indigo-600/20 to-cyan-500/5 text-indigo-300 shadow-[inset_0px_1px_4px_rgba(255,255,255,0.05)]' 
                                : 'border-transparent text-slate-400 hover:translate-x-1 hover:border-slate-700 hover:bg-slate-800/40 hover:text-slate-200' 
                            }}">
                            <!-- [UPDATE] UI/UX: Ikon dashboard memiliki efek neon dan loncat (scale) halus saat di-hover. -->
                            <i class="bi {{ $item['icon'] }} text-lg transition-transform duration-300 group-hover:scale-110 {{ $isActive ? 'text-indigo-400 drop-shadow-[0_0_8px_rgba(99,102,241,0.8)]' : '' }}" aria-hidden="true"></i>
                            <span class="truncate tracking-wide">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </nav>
</aside>
