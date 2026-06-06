@php
    $user = auth()->user();

    $namaPengguna = $user->nama ?? 'Administrator';
    $emailPengguna = $user->email ?? 'admin@smkn9malang.sch.id';

    $inisial = collect(preg_split('/\s+/', trim($namaPengguna)))
        ->filter()
        ->take(2)
        ->map(fn($bagian) => mb_strtoupper(mb_substr($bagian, 0, 1)))
        ->implode('');
@endphp

<!-- Header diubah menjadi warna Dark Slate Gray (bg-slate-800) untuk tampilan lebih elegan -->
<header
    class="sticky top-0 z-30 flex h-14 items-center justify-between gap-3 border-b border-slate-700 bg-slate-800/95 backdrop-blur-md backdrop-saturate-150 px-4">
    <div class="flex min-w-0 items-center gap-3">
        <!-- Tombol sidebar ditampilkan di mobile -->
        <button type="button"
            class="inline-flex lg:hidden h-9 w-9 items-center justify-center rounded-md border border-slate-700 text-slate-300 hover:bg-slate-700"
            @click="openSidebar()" aria-label="Buka sidebar" title="Buka sidebar">
            <i class="bi bi-list text-lg" aria-hidden="true"></i>
        </button>

        <div class="min-w-0">
            <!-- Judul halaman menjadi text-slate-200 agar kontras dengan header gelap -->
            <p class="truncate text-sm font-medium text-slate-200">
                @yield('title', 'Dashboard')
            </p>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <div class="hidden text-right sm:block">
            <!-- Jam dan Tanggal disesuaikan warna teksnya -->
            <p class="font-mono text-sm font-medium text-slate-100" x-text="clockTime"></p>
            <p class="text-[11px] text-slate-400" x-text="clockDate"></p>
        </div>

        <div class="hidden h-4 w-px bg-slate-700 sm:block"></div>

        <!-- Tombol toggle dark mode disesuaikan dengan skema header gelap -->
        <button type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-700 text-slate-300 hover:bg-slate-700"
            @click="toggleDark()" :title="isDark ? 'Ubah ke mode terang' : 'Ubah ke mode gelap'"
            :aria-label="isDark ? 'Ubah ke mode terang' : 'Ubah ke mode gelap'">
            <i class="bi text-base" :class="isDark ? 'bi-sun' : 'bi-moon'" aria-hidden="true"></i>
        </button>

        <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false"
            @click.outside="open = false">
            <!-- Tombol profil admin disesuaikan (bg-slate-800) -->
            <button type="button"
                class="inline-flex items-center gap-2 rounded-md border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-sm hover:bg-slate-700"
                @click="open = !open" aria-haspopup="true" :aria-expanded="open ? 'true' : 'false'" aria-label="Menu pengguna">
                <img src="{{ asset('images/profile.webp') }}" alt="Admin Profile"
                     class="h-8 w-8 rounded-full object-cover shadow-sm"
                     loading="lazy" decoding="async">

                <!-- Nama profil disesuaikan warna teksnya -->
                <span
                    class="hidden max-w-[132px] truncate text-sm font-medium text-slate-200 md:block">
                    {{ $namaPengguna }}
                </span>

                <i class="bi bi-chevron-down text-[11px] text-slate-400 transition-transform duration-150"
                    :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
            </button>

            <div x-cloak x-show="open"
                x-transition:enter="transition-transform transition-opacity duration-100 ease-out"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition-opacity duration-75 ease-in" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute right-0 mt-2 w-48 origin-top-right rounded-2xl border border-gray-200 bg-white p-2 shadow-xl dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-100 px-2 py-2 dark:border-gray-700">
                    <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">
                        {{ $namaPengguna }}
                    </p>
                    <p class="truncate text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $emailPengguna }}
                    </p>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="pt-2">
                    @csrf

                    <button type="submit"
                        class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
                        <i class="bi bi-box-arrow-right text-sm" aria-hidden="true"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
