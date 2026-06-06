<!-- START: MOBILE VIEW (Android Web App Native Feel) -->
<!-- 
  [PERHATIAN]: 
  Komponen ini murni digunakan KHUSUS untuk tampilan Mobile (layar kecil).
  Pada layar desktop (lg:), komponen ini secara paksa disembunyikan menggunakan 'lg:hidden'.
  Penggunaan properti GPU-Accelerated (transform, opacity) memastikan animasi berjalan 60FPS.
-->
<nav class="fixed bottom-0 left-0 right-0 z-[60] flex h-16 w-full items-center justify-around border-t border-slate-800/60 bg-slate-900/85 px-2 backdrop-blur-xl lg:hidden shadow-[0_-4px_25px_rgba(0,0,0,0.3)] pb-safe">
    
    <!-- Tab 1: Dashboard -->
    <a href="{{ route('dashboard') }}" class="group relative flex h-full w-full flex-col items-center justify-center gap-1 transition-all duration-300 active:scale-90">
        @php $isDashboard = request()->routeIs('dashboard'); @endphp
        <div class="relative flex flex-col items-center justify-center transition-transform duration-300 ease-out {{ $isDashboard ? '-translate-y-1' : '' }}">
            <i class="bi bi-grid-1x2-fill text-xl transition-colors duration-300 {{ $isDashboard ? 'text-indigo-400 drop-shadow-[0_0_8px_rgba(99,102,241,0.6)]' : 'text-slate-500 group-hover:text-slate-400' }}"></i>
            <span class="text-[10px] font-medium tracking-wide transition-colors duration-300 {{ $isDashboard ? 'text-indigo-300' : 'text-slate-500' }}">Home</span>
        </div>
        <!-- Indikator Aktif (Dot) -->
        <div class="absolute bottom-1 h-1 w-1 rounded-full transition-all duration-300 ease-out {{ $isDashboard ? 'scale-100 bg-indigo-400' : 'scale-0 bg-transparent' }}"></div>
    </a>

    <!-- Tab 2: Peminjaman -->
    <a href="{{ route('siswa.pinjam') }}" class="group relative flex h-full w-full flex-col items-center justify-center gap-1 transition-all duration-300 active:scale-90">
        @php $isPeminjaman = request()->routeIs('siswa.pinjam'); @endphp
        <div class="relative flex flex-col items-center justify-center transition-transform duration-300 ease-out {{ $isPeminjaman ? '-translate-y-1' : '' }}">
            <i class="bi bi-arrow-left-right text-xl transition-colors duration-300 {{ $isPeminjaman ? 'text-indigo-400 drop-shadow-[0_0_8px_rgba(99,102,241,0.6)]' : 'text-slate-500 group-hover:text-slate-400' }}"></i>
            <span class="text-[10px] font-medium tracking-wide transition-colors duration-300 {{ $isPeminjaman ? 'text-indigo-300' : 'text-slate-500' }}">Pinjam</span>
            
            <!-- Real-time Badge Peminjaman via Alpine Global State (layoutApp) -->
            <span x-cloak x-show="badgePeminjaman > 0" 
                  x-transition:enter="transition-all duration-300 ease-out"
                  x-transition:enter-start="opacity-0 scale-50"
                  x-transition:enter-end="opacity-100 scale-100"
                  class="absolute -right-2 -top-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white shadow-md shadow-red-500/40"
                  x-text="badgePeminjaman > 99 ? '99+' : badgePeminjaman">
            </span>
        </div>
        <!-- Indikator Aktif (Dot) -->
        <div class="absolute bottom-1 h-1 w-1 rounded-full transition-all duration-300 ease-out {{ $isPeminjaman ? 'scale-100 bg-indigo-400' : 'scale-0 bg-transparent' }}"></div>
    </a>

    <!-- Tab 3: Kelola Barang -->
    <a href="{{ route('barang.index') }}" class="group relative flex h-full w-full flex-col items-center justify-center gap-1 transition-all duration-300 active:scale-90">
        @php $isBarang = request()->routeIs('barang.*'); @endphp
        <div class="relative flex flex-col items-center justify-center transition-transform duration-300 ease-out {{ $isBarang ? '-translate-y-1' : '' }}">
            <i class="bi bi-box-seam-fill text-xl transition-colors duration-300 {{ $isBarang ? 'text-indigo-400 drop-shadow-[0_0_8px_rgba(99,102,241,0.6)]' : 'text-slate-500 group-hover:text-slate-400' }}"></i>
            <span class="text-[10px] font-medium tracking-wide transition-colors duration-300 {{ $isBarang ? 'text-indigo-300' : 'text-slate-500' }}">Barang</span>
            
            <!-- Real-time Badge Kelola Barang via Alpine Global State (layoutApp) -->
            <span x-cloak x-show="badgeKelolaBarang > 0" 
                  x-transition:enter="transition-all duration-300 ease-out"
                  x-transition:enter-start="opacity-0 scale-50"
                  x-transition:enter-end="opacity-100 scale-100"
                  class="absolute -right-2 -top-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-emerald-500 px-1 text-[9px] font-bold text-white shadow-md shadow-emerald-500/40"
                  x-text="badgeKelolaBarang > 99 ? '99+' : badgeKelolaBarang">
            </span>
        </div>
        <!-- Indikator Aktif (Dot) -->
        <div class="absolute bottom-1 h-1 w-1 rounded-full transition-all duration-300 ease-out {{ $isBarang ? 'scale-100 bg-indigo-400' : 'scale-0 bg-transparent' }}"></div>
    </a>

    <!-- Tab 4: Profile/Settings -->
    <a href="{{ route('profile.edit') }}" class="group relative flex h-full w-full flex-col items-center justify-center gap-1 transition-all duration-300 active:scale-90">
        @php $isProfile = request()->routeIs('profile.*'); @endphp
        <div class="relative flex flex-col items-center justify-center transition-transform duration-300 ease-out {{ $isProfile ? '-translate-y-1' : '' }}">
            <i class="bi bi-person-circle text-xl transition-colors duration-300 {{ $isProfile ? 'text-indigo-400 drop-shadow-[0_0_8px_rgba(99,102,241,0.6)]' : 'text-slate-500 group-hover:text-slate-400' }}"></i>
            <span class="text-[10px] font-medium tracking-wide transition-colors duration-300 {{ $isProfile ? 'text-indigo-300' : 'text-slate-500' }}">Profile</span>
        </div>
        <!-- Indikator Aktif (Dot) -->
        <div class="absolute bottom-1 h-1 w-1 rounded-full transition-all duration-300 ease-out {{ $isProfile ? 'scale-100 bg-indigo-400' : 'scale-0 bg-transparent' }}"></div>
    </a>

</nav>
<!-- END: MOBILE VIEW -->
