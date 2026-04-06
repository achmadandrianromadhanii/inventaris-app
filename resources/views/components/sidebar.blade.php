@props(['mobile' => false])

@php
    $user = auth()->user();

    $namaPengguna = $user->nama ?? 'Administrator';

    $inisial = collect(preg_split('/\s+/', trim($namaPengguna)))
        ->filter()
        ->take(2)
        ->map(fn($bagian) => mb_strtoupper(mb_substr($bagian, 0, 1)))
        ->implode('');

    $wrapperClass = $mobile
        ? 'flex h-full w-60 flex-col border-r border-gray-700/50 bg-gray-900 shadow-xl'
        : 'fixed inset-y-0 left-0 z-40 hidden h-screen w-60 flex-col border-r border-gray-700/50 bg-gray-900 lg:flex';

    $sections = [
        [
            'label' => null,
            'items' => [
                [
                    'label' => 'Dashboard',
                    'icon' => 'bi-grid-1x2-fill',
                    'href' => route('dashboard'),
                    'match' => ['dashboard'],
                ],
                [
                    'label' => 'Kelola Barang',
                    'icon' => 'bi-box-seam',
                    'href' => route('barang.index'),
                    'match' => ['barang.*'],
                ],
                [
                    'label' => 'Kategori',
                    'icon' => 'bi-tags',
                    'href' => route('kategori.index'),
                    'match' => ['kategori.*'],
                ],
            ],
        ],
        [
            'label' => 'Transaksi',
            'items' => [
                [
                    'label' => 'Barang Masuk',
                    'icon' => 'bi-arrow-down-circle',
                    'href' => route('transaksi.masuk'),
                    'match' => ['transaksi.masuk'],
                ],
                [
                    'label' => 'Barang Keluar',
                    'icon' => 'bi-arrow-up-circle',
                    'href' => route('transaksi.keluar'),
                    'match' => ['transaksi.keluar'],
                ],
                [
                    'label' => 'Peminjaman',
                    'icon' => 'bi-people',
                    'href' => route('peminjaman.index'),
                    'match' => ['peminjaman.*'],
                ],
            ],
        ],
        [
            'label' => 'Lainnya',
            'items' => [
                [
                    'label' => 'Laporan',
                    'icon' => 'bi-file-earmark-text',
                    'href' => route('laporan.index'),
                    'match' => ['laporan.*'],
                ],
                [
                    'label' => 'Pengguna',
                    'icon' => 'bi-person-gear',
                    'href' => route('pengguna.index'),
                    'match' => ['pengguna.*'],
                ],
            ],
        ],
        [
            'label' => 'Publik',
            'items' => [
                [
                    'label' => 'Halaman Siswa',
                    'icon' => 'bi-box-arrow-up-right',
                    'href' => route('siswa.pinjam'),
                    'match' => ['siswa.*'],
                    'blank' => true,
                ],
            ],
        ],
    ];
@endphp

<aside class="{{ $wrapperClass }}" aria-label="Sidebar navigasi">
    <div class="flex flex-col items-center border-b border-gray-700/70 px-4 py-4">
        <img src="{{ asset('images/logo-sekolah.png') }}" alt="Logo SMKN 9 Malang" class="h-14 w-14 object-contain"
            decoding="async" draggable="false">

        <p class="mt-2 text-center text-sm font-bold text-white">
            SMKN 9 Malang
        </p>

        <p class="text-center text-[10px] text-gray-400">
            Inventaris Lab RPL
        </p>
    </div>

    <nav class="flex-1 space-y-0.5 overflow-y-auto px-2 py-3">
        @foreach ($sections as $section)
            @if ($section['label'])
                <p class="px-1 pb-1 pt-3 text-[10px] font-semibold uppercase tracking-widest text-gray-600">
                    {{ $section['label'] }}
                </p>
            @endif

            @foreach ($section['items'] as $item)
                @php
                    $isActive = request()->routeIs(...$item['match']);
                @endphp

                <a href="{{ $item['href'] }}"
                    @if (!empty($item['blank'])) target="_blank" rel="noopener noreferrer" @endif
                    @if ($mobile && empty($item['blank'])) @click="closeSidebar()" @endif
                    @if ($isActive) aria-current="page" @endif
                    class="flex items-center gap-2.5 rounded-md border-l-2 px-3 py-2 text-sm transition-colors duration-150 ease-out {{ $isActive ? 'border-blue-500 bg-blue-600/20 text-blue-400' : 'border-transparent text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <i class="bi {{ $item['icon'] }} text-sm" aria-hidden="true"></i>
                    <span class="truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="border-t border-gray-700/70 px-3 py-3">
        <div class="flex items-center gap-2.5 rounded-lg bg-gray-800/60 px-2.5 py-2.5">
            <div
                class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">
                {{ $inisial ?: 'A' }}
            </div>

            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-white">
                    {{ $namaPengguna }}
                </p>
                <p class="truncate text-[11px] text-gray-400">
                    Administrator
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf

            <button type="submit"
                class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm text-red-400 transition-colors duration-150 ease-out hover:bg-red-900/20"
                title="Keluar dari aplikasi">
                <i class="bi bi-box-arrow-right text-sm" aria-hidden="true"></i>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>
